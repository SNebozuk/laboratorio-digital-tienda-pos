<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use DateInterval;
use DateTimeImmutable;
use PDO;

final class OrderService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly PDO $pdo,
        private readonly StockService $stock,
        private readonly array $config
    ) {
    }

    /**
     * @param array{name?: mixed, email?: mixed, phone?: mixed} $customer
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function createWebOrder(
        array $customer,
        array $items,
        string $channel = 'web',
        string $paymentMethod = 'bank_transfer',
        bool $surpriseUnlocked = false
    ): array {
        if (empty($this->config['orders_enabled'])) {
            throw new ConflictException(
                'La nueva tienda todavía no está recibiendo pedidos.'
            );
        }
        if ($this->cartMaintenanceEnabled()) {
            throw new ConflictException(
                'Estamos realizando trabajos de mantenimiento. Podés recorrer la tienda, pero el carrito está temporalmente pausado.'
            );
        }
        if (!in_array($channel, ['web', 'whatsapp'], true)) {
            throw new ValidationException('El canal del pedido no es válido.');
        }
        $paymentMethod = strtolower(trim($paymentMethod));
        if (!in_array($paymentMethod, ['bank_transfer', 'cash'], true)) {
            throw new ValidationException('Elegí transferencia o efectivo como forma de pago.');
        }

        $customerName = $this->normalizeCustomerName((string) ($customer['name'] ?? ''));
        $customerEmail = trim((string) ($customer['email'] ?? ''));
        $customerEmail = $customerEmail !== '' ? strtolower($customerEmail) : null;
        $customerPhone = preg_replace(
            '/\D+/',
            '',
            (string) ($customer['phone'] ?? '')
        ) ?: null;

        if (!$this->isValidCustomerFullName($customerName)) {
            throw new ValidationException('Por favor, escribí tu nombre y apellido completos, tal como querés que figuren en el pedido.');
        }
        if ($customerPhone === null || strlen($customerPhone) < 8) {
            throw new ValidationException('Ingresá un WhatsApp válido.');
        }
        if (
            $customerEmail !== null
            && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
        ) {
            throw new ValidationException('Ingresá un email válido.');
        }

        $quantities = $this->normalizeItems($items);
        // El efectivo se reserva durante seis horas. Las transferencias se
        // coordinan por WhatsApp y no dependen de una carga de comprobante.
        $deadline = $paymentMethod === 'cash'
            ? (new DateTimeImmutable())->add(new DateInterval('PT6H'))->format('Y-m-d H:i:s')
            : null;
        $uploadToken = bin2hex(random_bytes(24));
        $tokenHash = hash('sha256', $uploadToken);

        $result = Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $quantities,
                $channel,
                $customerName,
                $customerEmail,
                $customerPhone,
                $deadline,
                $tokenHash,
                $uploadToken,
                $paymentMethod,
                $surpriseUnlocked
            ): array {
                $publicNumber = $this->newPublicNumber($pdo);
                $resolvedItems = $this->resolveItems($pdo, $quantities);
                $subtotal = array_sum(array_column($resolvedItems, 'line_total_cents'));
                $rewardSettings = $this->rewardSettings($pdo);
                $units = array_sum($quantities);
                $quantityPercent = $rewardSettings['quantity_enabled'] && $units >= $rewardSettings['quantity_units']
                    ? $rewardSettings['quantity_percent'] : 0;
                $surprisePercent = $surpriseUnlocked && $rewardSettings['surprise_enabled']
                    ? $rewardSettings['surprise_percent'] : 0;
                $discountPercent = max($quantityPercent, $surprisePercent);
                $discountType = $discountPercent === 0 ? '' : ($surprisePercent >= $quantityPercent ? 'surprise' : 'quantity');
                $discountCents = (int) round($subtotal * $discountPercent / 100);
                $total = $subtotal - $discountCents;

                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders(
                        public_number, channel, status,
                        customer_name, customer_email, customer_phone,
                        subtotal_cents, discount_type, discount_percent, discount_cents, total_cents, payment_method,
                        payment_deadline_at, upload_token_hash
                     ) VALUES(
                        :public_number, :channel, :status,
                        :customer_name, :customer_email, :customer_phone,
                        :subtotal_cents, :discount_type, :discount_percent, :discount_cents, :total_cents, :payment_method,
                        :payment_deadline_at, :upload_token_hash
                     )'
                );
                $insertOrder->execute([
                    'public_number' => $publicNumber,
                    'channel' => $channel,
                    'status' => 'pending_payment',
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'subtotal_cents' => $subtotal,
                    'discount_type' => $discountType,
                    'discount_percent' => $discountPercent,
                    'discount_cents' => $discountCents,
                    'total_cents' => $total,
                    'payment_method' => $paymentMethod,
                    'payment_deadline_at' => $deadline,
                    'upload_token_hash' => $tokenHash,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $paymentUrl = $this->publicPaymentUrl($orderId, $uploadToken);
                $this->insertOrderItems($pdo, $orderId, $resolvedItems);
                $this->reserveCashItems(
                    $pdo,
                    $orderId,
                    $publicNumber,
                    $resolvedItems
                );
                $this->recordEvent(
                    $pdo,
                    $orderId,
                    null,
                    'order_created',
                    null,
                    'pending_payment',
                    $paymentMethod === 'cash'
                        ? 'Pedido en efectivo; stock reservado durante 2 horas.'
                        : 'Pedido creado sin reserva. El stock se reservará al informar el pago.'
                );
                $mailPayload = [
                    'public_number' => $publicNumber,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'total_cents' => $total,
                    'payment_method' => $paymentMethod,
                    'payment_deadline_at' => $deadline,
                    'payment_url' => $paymentUrl,
                    'items' => $resolvedItems,
                ];
                $salesEmail = trim((string) ($this->config['sales_notification_email']
                    ?? 'ventas@laboratorio-digital.com.ar'));
                if (
                    !empty($this->config['mail_enabled'])
                    && filter_var($salesEmail, FILTER_VALIDATE_EMAIL)
                ) {
                    $internalPayload = $mailPayload;
                    $internalPayload['audience'] = 'internal';
                    $this->queueOrderMail(
                        $pdo,
                        $orderId,
                        $salesEmail,
                        'Nueva venta ' . $publicNumber . ' · ' . $customerName,
                        'order_created',
                        $internalPayload
                    );
                }
                if (
                    !empty($this->config['mail_enabled'])
                    && $customerEmail !== null
                ) {
                    $customerPayload = $mailPayload;
                    $customerPayload['audience'] = 'customer';
                    $this->queueOrderMail(
                        $pdo,
                        $orderId,
                        $customerEmail,
                        'Recibimos tu pedido ' . $publicNumber . ' · Laboratorio Digital',
                        'order_created',
                        $customerPayload
                    );
                }

                return [
                    'id' => $orderId,
                    'public_number' => $publicNumber,
                    'customer_name' => $customerName,
                    'status' => 'pending_payment',
                    'total_cents' => $total,
                    'payment_method' => $paymentMethod,
                    'payment_deadline_at' => $deadline,
                    'payment_url' => $paymentUrl,
                    'stock_reserved' => true,
                    'items' => $resolvedItems,
                ];
            }
        );

        $result['upload_token'] = $uploadToken;
        $result['bank'] = [
            'holder' => $this->stringSetting('bank_holder', 'Laboratorio Digital'),
            'alias' => $this->stringSetting('bank_alias', ''),
            'cbu' => $this->stringSetting('bank_cbu', ''),
        ];
        $result['pickup_address'] = $this->stringSetting('pickup_address', '');

        return $result;
    }

    /** @return array{surprise_enabled: bool, surprise_percent: int, quantity_enabled: bool, quantity_units: int, quantity_percent: int} */
    private function rewardSettings(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT key, value FROM settings WHERE key IN ('reward_surprise_enabled', 'reward_surprise_percent', 'reward_quantity_enabled', 'reward_quantity_units', 'reward_quantity_percent')")->fetchAll();
        $values = [];
        foreach ($rows as $row) $values[(string) $row['key']] = (string) $row['value'];
        return [
            'surprise_enabled' => in_array($values['reward_surprise_enabled'] ?? '1', ['1', 'true', 'on'], true),
            'surprise_percent' => max(0, min(100, (int) ($values['reward_surprise_percent'] ?? 5))),
            'quantity_enabled' => in_array($values['reward_quantity_enabled'] ?? '1', ['1', 'true', 'on'], true),
            'quantity_units' => max(1, (int) ($values['reward_quantity_units'] ?? 20)),
            'quantity_percent' => max(0, min(100, (int) ($values['reward_quantity_percent'] ?? 3))),
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function createPosSale(
        array $items,
        string $customerName,
        string $customerPhone,
        string $paymentMethod,
        int $actorUserId
    ): array {
        $quantities = $this->normalizeItems($items);
        $customerName = $this->normalizeCustomerName($customerName) ?: 'Consumidor final';
        $customerPhone = preg_replace('/\D+/', '', $customerPhone) ?: '';
        // Las ventas rápidas sin cliente identificado no requieren seguimiento
        // en la lista diaria. Se conservan en la base de datos, pero quedan
        // archivadas desde su creación.
        $archiveConsumerFinal = $this->normalizeCustomerName($customerName) === 'CONSUMIDOR FINAL';
        if (!$archiveConsumerFinal && strlen($customerPhone) < 8) {
            throw new ValidationException('Ingresá el WhatsApp del cliente para registrar esta venta.');
        }
        $paymentMethod = trim($paymentMethod);
        if ($paymentMethod === '') {
            throw new ValidationException('Elegí un medio de pago.');
        }
        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $quantities,
                $customerName,
                $customerPhone,
                $archiveConsumerFinal,
                $paymentMethod,
                $actorUserId
            ): array {
                $publicNumber = $this->newPublicNumber($pdo);
                $resolvedItems = $this->resolveItems($pdo, $quantities);
                $total = array_sum(array_column($resolvedItems, 'line_total_cents'));

                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders(
                        public_number, channel, status,
                        customer_name, customer_phone, subtotal_cents, total_cents,
                        payment_method, delivered_at, created_by, archived_at, archived_by
                     ) VALUES(
                        :public_number, :channel, :status,
                        :customer_name, :customer_phone, :subtotal_cents, :total_cents,
                        :payment_method, CURRENT_TIMESTAMP, :created_by, :archived_at, :archived_by
                     )'
                );
                $insertOrder->execute([
                    'public_number' => $publicNumber,
                    'channel' => 'pos',
                    'status' => 'delivered',
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone ?: null,
                    'subtotal_cents' => $total,
                    'total_cents' => $total,
                    'payment_method' => $paymentMethod,
                    'created_by' => $actorUserId,
                    'archived_at' => $archiveConsumerFinal ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null,
                    'archived_by' => $archiveConsumerFinal ? $actorUserId : null,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $this->insertOrderItems($pdo, $orderId, $resolvedItems);

                foreach ($resolvedItems as $item) {
                    $quantity = (int) $item['quantity'];
                    $decrement = $pdo->prepare(
                        'UPDATE product_variants
                         SET stock_on_hand = stock_on_hand - :quantity,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :variant_id
                           AND stock_on_hand >= :quantity'
                    );
                    $decrement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                    $decrement->bindValue(
                        ':variant_id',
                        (int) $item['variant_id'],
                        PDO::PARAM_INT
                    );
                    $decrement->execute();
                    if ($decrement->rowCount() !== 1) {
                        throw new ConflictException(
                            'Stock insuficiente de '
                            . $item['product_name']
                            . ' · '
                            . $item['variant_name']
                            . '.'
                        );
                    }

                    $movement = $pdo->prepare(
                        'INSERT INTO stock_movements(
                            variant_id, order_id, actor_user_id,
                            on_hand_delta, reserved_delta, reason, reference
                         ) VALUES(
                            :variant_id, :order_id, :actor_user_id,
                            :on_hand_delta, 0, :reason, :reference
                         )'
                    );
                    $movement->execute([
                        'variant_id' => $item['variant_id'],
                        'order_id' => $orderId,
                        'actor_user_id' => $actorUserId,
                        'on_hand_delta' => -$quantity,
                        'reason' => 'pos_sale',
                        'reference' => $publicNumber,
                    ]);
                }

                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'pos_sale_completed',
                    null,
                    'delivered',
                    'Venta de mostrador cobrada y stock descontado.'
                );

                return [
                    'id' => $orderId,
                    'public_number' => $publicNumber,
                    'status' => 'delivered',
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone ?: null,
                    'archived_at' => $archiveConsumerFinal ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null,
                    'payment_method' => $paymentMethod,
                    'total_cents' => $total,
                    'items' => $resolvedItems,
                ];
            }
        );
    }

    private function normalizeCustomerName(string $value): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if ($name === '') {
            return '';
        }
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($name, 'UTF-8');
        }
        return strtoupper($name);
    }

    private function isValidCustomerFullName(string $name): bool
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        if (count($parts) < 2) {
            return false;
        }
        foreach ($parts as $part) {
            if (!preg_match("/^\\p{L}[\\p{L}'’.-]{1,}$/u", $part)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Reemplaza los productos de un pedido web activo.
     *
     * Si el pedido ya reservó stock, ajusta solamente la diferencia entre la
     * composición anterior y la nueva dentro de la misma transacción.
     *
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function updateWebOrderItems(
        int $orderId,
        array $items,
        int $actorUserId
    ): array {
        $quantities = $this->normalizeItems($items);

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $orderId,
                $quantities,
                $actorUserId
            ): void {
                $query = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
                $query->execute(['id' => $orderId]);
                $order = $query->fetch();

                if (!$order) {
                    throw new ValidationException('El pedido no existe.');
                }
                if (!in_array($order['channel'], ['web', 'whatsapp'], true)) {
                    throw new ConflictException(
                        'Las ventas de mostrador se corrigen mediante una devolución.'
                    );
                }
                if (!in_array(
                    $order['status'],
                    [
                        'pending_payment',
                        'payment_reported',
                        'rejected',
                    ],
                    true
                )) {
                    throw new ConflictException(
                        'Solo se pueden editar productos antes de aprobar el pago.'
                    );
                }

                $currentQuery = $pdo->prepare(
                    'SELECT variant_id, quantity
                     FROM order_items
                     WHERE order_id = :order_id'
                );
                $currentQuery->execute(['order_id' => $orderId]);
                $currentQuantities = [];
                foreach ($currentQuery->fetchAll() as $currentItem) {
                    $currentQuantities[(int) $currentItem['variant_id']]
                        = (int) $currentItem['quantity'];
                }

                $hasReservation = $order['stock_reserved_at'] !== null;
                $reservationCredit = $hasReservation ? $currentQuantities : [];
                $resolvedItems = $this->resolveItems(
                    $pdo,
                    $quantities,
                    $reservationCredit
                );
                $total = array_sum(
                    array_column($resolvedItems, 'line_total_cents')
                );

                if ($hasReservation) {
                    $variantIds = array_unique(array_merge(
                        array_keys($currentQuantities),
                        array_keys($quantities)
                    ));

                    foreach ($variantIds as $variantId) {
                        $previous = (int) ($currentQuantities[$variantId] ?? 0);
                        $next = (int) ($quantities[$variantId] ?? 0);
                        $delta = $next - $previous;
                        if ($delta === 0) {
                            continue;
                        }

                        if ($delta > 0) {
                            $adjust = $pdo->prepare(
                                'UPDATE product_variants
                                 SET stock_on_hand = stock_on_hand - :delta,
                                     updated_at = CURRENT_TIMESTAMP
                                 WHERE id = :variant_id
                                   AND active = 1
                                   AND stock_on_hand >= :delta'
                            );
                            $adjust->bindValue(':delta', $delta, PDO::PARAM_INT);
                            $adjust->bindValue(
                                ':variant_id',
                                $variantId,
                                PDO::PARAM_INT
                            );
                            $adjust->execute();
                        } else {
                            $release = abs($delta);
                            $adjust = $pdo->prepare(
                                'UPDATE product_variants
                                 SET stock_on_hand = stock_on_hand + :release,
                                     updated_at = CURRENT_TIMESTAMP
                                 WHERE id = :variant_id'
                            );
                            $adjust->bindValue(
                                ':release',
                                $release,
                                PDO::PARAM_INT
                            );
                            $adjust->bindValue(
                                ':variant_id',
                                $variantId,
                                PDO::PARAM_INT
                            );
                            $adjust->execute();
                        }

                        if ($adjust->rowCount() !== 1) {
                            throw new ConflictException(
                                'No se pudo ajustar el stock reservado. Actualizá la pantalla e intentá nuevamente.'
                            );
                        }

                        $this->recordStockMovement(
                            $pdo,
                            (int) $variantId,
                            $orderId,
                            $actorUserId,
                            $delta,
                            (string) $order['public_number']
                        );
                    }
                }

                $deleteItems = $pdo->prepare(
                    'DELETE FROM order_items WHERE order_id = :order_id'
                );
                $deleteItems->execute(['order_id' => $orderId]);
                $this->insertOrderItems($pdo, $orderId, $resolvedItems);

                $updateOrder = $pdo->prepare(
                    'UPDATE orders
                     SET subtotal_cents = :subtotal_cents,
                         discount_cents = ROUND(:subtotal_cents * discount_percent / 100.0),
                         total_cents = :subtotal_cents - ROUND(:subtotal_cents * discount_percent / 100.0),
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $updateOrder->execute([
                    'subtotal_cents' => $total,
                    'id' => $orderId,
                ]);

                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'order_items_updated',
                    (string) $order['status'],
                    (string) $order['status'],
                    'Productos actualizados desde administración.'
                );
            }
        );

        return $this->orderDetail($orderId);
    }

    public function reviewPayment(
        int $orderId,
        string $decision,
        int $actorUserId,
        string $note = ''
    ): void {
        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new ValidationException('La decisión no es válida.');
        }

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $orderId,
                $decision,
                $actorUserId,
                $note
            ): void {
                $query = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
                $query->execute(['id' => $orderId]);
                $order = $query->fetch();
                if (!$order || $order['status'] !== 'payment_reported') {
                    throw new ConflictException(
                        'El pedido no tiene un pago pendiente de revisión.'
                    );
                }

                $proof = $pdo->prepare(
                    "SELECT id FROM payment_proofs
                     WHERE order_id = :order_id AND status = 'reported'
                     ORDER BY id DESC LIMIT 1"
                );
                $proof->execute(['order_id' => $orderId]);
                $proofId = $proof->fetchColumn();
                if (!$proofId) {
                    throw new ConflictException('No se encontró el comprobante informado.');
                }

                if ($decision === 'approve') {
                    $newStatus = 'paid_prepare';
                    $proofStatus = 'approved';
                    $deadline = null;
                    $event = 'payment_approved';
                    $detail = 'Pago aprobado; pedido listo para preparar.';
                    $template = 'payment_approved';
                    $subject = 'Pago aprobado · ' . $order['public_number'];
                    $nextTokenHash = (string) $order['upload_token_hash'];
                    $paymentUrl = null;
                } else {
                    $minutes = max(
                        15,
                        $this->integerSetting('rejected_retry_minutes', 120)
                    );
                    $newStatus = 'rejected';
                    $proofStatus = 'rejected';
                    $deadline = (new DateTimeImmutable())
                        ->add(new DateInterval('PT' . $minutes . 'M'))
                        ->format('Y-m-d H:i:s');
                    $event = 'payment_rejected';
                    $detail = 'Comprobante rechazado; reserva mantenida durante el plazo de reintento.';
                    $template = 'payment_rejected';
                    $subject = 'Necesitamos otro comprobante · ' . $order['public_number'];
                    $nextUploadToken = bin2hex(random_bytes(24));
                    $nextTokenHash = hash('sha256', $nextUploadToken);
                    $paymentUrl = $this->publicPaymentUrl(
                        $orderId,
                        $nextUploadToken
                    );
                }

                $updateProof = $pdo->prepare(
                    'UPDATE payment_proofs
                     SET status = :status,
                         reviewed_by = :reviewed_by,
                         reviewed_at = CURRENT_TIMESTAMP,
                         review_note = :review_note
                     WHERE id = :id'
                );
                $updateProof->execute([
                    'status' => $proofStatus,
                    'reviewed_by' => $actorUserId,
                    'review_note' => trim($note) ?: null,
                    'id' => $proofId,
                ]);

                $updateOrder = $pdo->prepare(
                    'UPDATE orders
                     SET status = :status,
                         rejection_deadline_at = :rejection_deadline_at,
                         upload_token_hash = :upload_token_hash,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $updateOrder->execute([
                    'status' => $newStatus,
                    'rejection_deadline_at' => $deadline,
                    'upload_token_hash' => $nextTokenHash,
                    'id' => $orderId,
                ]);

                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    $event,
                    'payment_reported',
                    $newStatus,
                    $detail
                );

            }
        );
    }

    public function markReady(int $orderId, int $actorUserId): void
    {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $actorUserId): void {
                $query = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
                $query->execute(['id' => $orderId]);
                $order = $query->fetch();
                $cashReserved = $order
                    && $order['payment_method'] === 'cash'
                    && $order['status'] === 'pending_payment'
                    && $order['stock_reserved_at'] !== null
                    && new DateTimeImmutable((string) $order['payment_deadline_at']) >= new DateTimeImmutable();
                if (!$order || ($order['status'] !== 'paid_prepare' && !$cashReserved)) {
                    throw new ConflictException(
                        'Solo un pedido pagado o reservado para efectivo puede marcarse como listo.'
                    );
                }
                $oldStatus = (string) $order['status'];

                $update = $pdo->prepare(
                    'UPDATE orders
                     SET status = :status, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute(['status' => 'ready_pickup', 'id' => $orderId]);
                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'order_ready',
                    $oldStatus,
                    'ready_pickup',
                    $cashReserved
                        ? 'Pedido en efectivo listo para retirar dentro de su reserva de 2 horas.'
                        : 'Pedido listo para retirar.'
                );

            }
        );
    }

    public function deliver(int $orderId, int $actorUserId): void
    {
        $this->stock->consumeOrderReservation($orderId, $actorUserId);
    }

    public function cancel(
        int $orderId,
        int $actorUserId,
        string $reason = 'manual_cancellation',
        bool $notifyCustomer = true,
        bool $restoreStock = true
    ): void {
        $this->stock->cancelOrder($orderId, $reason, $actorUserId, $notifyCustomer, $restoreStock);
    }

    public function archive(int $orderId, int $actorUserId): void
    {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $actorUserId): void {
                $query = $pdo->prepare('SELECT status, archived_at FROM orders WHERE id = :id');
                $query->execute(['id' => $orderId]);
                $order = $query->fetch();
                if (!$order) {
                    throw new ValidationException('La venta no existe.');
                }
                if ($order['archived_at'] !== null) {
                    return;
                }

                $update = $pdo->prepare(
                    'UPDATE orders
                     SET archived_at = CURRENT_TIMESTAMP,
                         archived_by = :actor_user_id,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute(['actor_user_id' => $actorUserId, 'id' => $orderId]);
                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'order_archived',
                    (string) $order['status'],
                    (string) $order['status'],
                    'Venta archivada.'
                );
            }
        );
    }

    public function reopen(int $orderId, int $actorUserId): void
    {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $actorUserId): void {
                $query = $pdo->prepare('SELECT status, archived_at FROM orders WHERE id = :id');
                $query->execute(['id' => $orderId]);
                $order = $query->fetch();
                if (!$order) {
                    throw new ValidationException('La venta no existe.');
                }
                if ((string) $order['status'] !== 'cancelled' && $order['archived_at'] === null) {
                    throw new ValidationException('La venta ya está abierta.');
                }
                $oldStatus = (string) $order['status'];
                $update = $pdo->prepare(
                    'UPDATE orders
                     SET status = :status,
                         archived_at = NULL,
                         archived_by = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute(['status' => 'pending_payment', 'id' => $orderId]);
                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'order_reopened',
                    $oldStatus,
                    'pending_payment',
                    'Venta reabierta.'
                );
            }
        );
    }

    /** @return list<array<string, mixed>> */
    public function recentOrders(int $limit = 100, bool $includeArchived = false): array
    {
        $limit = max(1, min(500, $limit));
        $query = $this->pdo->query(
            'SELECT
                o.*,
                COUNT(oi.id) AS line_count,
                COALESCE(SUM(oi.quantity), 0) AS unit_count,
                (
                    SELECT pp.id
                    FROM payment_proofs pp
                    WHERE pp.order_id = o.id
                    ORDER BY pp.id DESC
                    LIMIT 1
                ) AS payment_proof_id,
                (
                    SELECT pp.ai_status
                    FROM payment_proofs pp
                    WHERE pp.order_id = o.id
                    ORDER BY pp.id DESC
                    LIMIT 1
                ) AS payment_ai_status,
                (
                    SELECT pp.ai_risk_level
                    FROM payment_proofs pp
                    WHERE pp.order_id = o.id
                    ORDER BY pp.id DESC
                    LIMIT 1
                ) AS payment_ai_risk_level,
                (
                    SELECT pp.ai_summary
                    FROM payment_proofs pp
                    WHERE pp.order_id = o.id
                    ORDER BY pp.id DESC
                    LIMIT 1
                ) AS payment_ai_summary
             FROM orders o
              LEFT JOIN order_items oi ON oi.order_id = o.id
             ' . ($includeArchived ? '' : "WHERE o.archived_at IS NULL AND o.status <> 'cancelled'") . '
             GROUP BY o.id
             ORDER BY o.id DESC
             LIMIT ' . $limit
        );

        return $query->fetchAll();
    }

    public function openOrderCount(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM orders WHERE archived_at IS NULL AND status <> 'cancelled'"
        )->fetchColumn();
    }

    /** @return array<string, mixed> */
    public function orderDetail(int $orderId): array
    {
        $query = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $query->execute(['id' => $orderId]);
        $order = $query->fetch();
        if (!$order) {
            throw new ValidationException('El pedido no existe.');
        }

        $items = $this->pdo->prepare(
            'SELECT oi.*, COALESCE(v.image_path, p.image_path) AS image_path
             FROM order_items oi
             LEFT JOIN product_variants v ON v.id = oi.variant_id
             LEFT JOIN products p ON p.id = v.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id'
        );
        $items->execute(['order_id' => $orderId]);
        $order['items'] = $items->fetchAll();

        $proof = $this->pdo->prepare(
            'SELECT
                id,
                original_name,
                mime_type,
                size_bytes,
                status,
                review_note,
                ai_status,
                ai_risk_level,
                ai_summary,
                created_at,
                reviewed_at
             FROM payment_proofs
             WHERE order_id = :order_id
             ORDER BY id DESC
             LIMIT 1'
        );
        $proof->execute(['order_id' => $orderId]);
        $order['payment_proof'] = $proof->fetch() ?: null;

        $events = $this->pdo->prepare(
            'SELECT oe.*, u.name AS actor_name
             FROM order_events oe
             LEFT JOIN users u ON u.id = oe.actor_user_id
             WHERE oe.order_id = :order_id
             ORDER BY oe.id'
        );
        $events->execute(['order_id' => $orderId]);
        $order['events'] = $events->fetchAll();

        return $order;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<int, int>
     */
    private function normalizeItems(array $items): array
    {
        if ($items === [] || count($items) > 200) {
            throw new ValidationException('El pedido debe contener productos.');
        }

        $quantities = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new ValidationException('Hay un producto inválido.');
            }
            $variantId = filter_var(
                $item['variant_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $quantity = filter_var(
                $item['quantity'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 9999]]
            );
            if ($variantId === false || $quantity === false) {
                throw new ValidationException('Las cantidades del pedido no son válidas.');
            }
            $quantities[$variantId] = ($quantities[$variantId] ?? 0) + $quantity;
        }

        return $quantities;
    }

    /**
     * @param array<int, int> $quantities
     * @return list<array<string, mixed>>
     */
    private function resolveItems(
        PDO $pdo,
        array $quantities,
        array $reservationCredit = []
    ): array
    {
        $query = $pdo->prepare(
            'SELECT
                v.id AS variant_id,
                v.name AS variant_name,
                CASE WHEN substr(v.sku, 1, 8) = \'__AUTO__\' THEN \'\' ELSE v.sku END AS sku,
                v.price_cents,
                v.stock_on_hand,
                v.stock_reserved,
                p.name AS product_name
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.id = :variant_id
               AND v.active = 1
               AND p.active = 1'
        );

        $resolved = [];
        foreach ($quantities as $variantId => $quantity) {
            $query->execute(['variant_id' => $variantId]);
            $variant = $query->fetch();
            if (!$variant) {
                throw new ValidationException('Uno de los productos ya no está disponible.');
            }
            $available = (int) $variant['stock_on_hand']
                + (int) ($reservationCredit[$variantId] ?? 0);
            if ($available < $quantity) {
                throw new ConflictException(
                    'Solo quedan '
                    . $available
                    . ' unidades de '
                    . $variant['product_name']
                    . ' · '
                    . $variant['variant_name']
                    . '.'
                );
            }

            $price = (int) $variant['price_cents'];
            $resolved[] = [
                'variant_id' => (int) $variant['variant_id'],
                'product_name' => $variant['product_name'],
                'variant_name' => $variant['variant_name'],
                'sku' => $variant['sku'],
                'quantity' => $quantity,
                'unit_price_cents' => $price,
                'line_total_cents' => $price * $quantity,
                'available_stock' => $available,
            ];
        }

        return $resolved;
    }

    /** @param list<array<string, mixed>> $items */
    private function reserveCashItems(
        PDO $pdo,
        int $orderId,
        string $publicNumber,
        array $items
    ): void {
        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $reserve = $pdo->prepare(
                'UPDATE product_variants
                 SET stock_on_hand = stock_on_hand - :quantity,
                     stock_reserved = 0,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :variant_id
                   AND active = 1
                   AND stock_on_hand >= :quantity'
            );
            $reserve->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $reserve->bindValue(':variant_id', (int) $item['variant_id'], PDO::PARAM_INT);
            $reserve->execute();
            if ($reserve->rowCount() !== 1) {
                throw new ConflictException(
                    'Ya no hay stock suficiente de '
                    . $item['product_name']
                    . ' · '
                    . $item['variant_name']
                    . '.'
                );
            }

            $movement = $pdo->prepare(
                'INSERT INTO stock_movements(
                    variant_id, order_id, actor_user_id,
                    on_hand_delta, reserved_delta, reason, reference
                 ) VALUES(
                    :variant_id, :order_id, NULL,
                    :on_hand_delta, 0, :reason, :reference
                 )'
            );
            $movement->execute([
                'variant_id' => (int) $item['variant_id'],
                'order_id' => $orderId,
                'on_hand_delta' => -$quantity,
                'reason' => 'web_order_stock',
                'reference' => $publicNumber,
            ]);
        }

        $update = $pdo->prepare(
            'UPDATE orders
             SET stock_reserved_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $update->execute(['id' => $orderId]);
    }

    /** @param list<array<string, mixed>> $items */
    private function insertOrderItems(PDO $pdo, int $orderId, array $items): void
    {
        $insert = $pdo->prepare(
            'INSERT INTO order_items(
                order_id, variant_id, product_name, variant_name,
                sku, quantity, unit_price_cents, line_total_cents
             ) VALUES(
                :order_id, :variant_id, :product_name, :variant_name,
                :sku, :quantity, :unit_price_cents, :line_total_cents
             )'
        );

        foreach ($items as $item) {
            $insert->execute([
                'order_id' => $orderId,
                'variant_id' => $item['variant_id'],
                'product_name' => $item['product_name'],
                'variant_name' => $item['variant_name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'unit_price_cents' => $item['unit_price_cents'],
                'line_total_cents' => $item['line_total_cents'],
            ]);
        }
    }

    private function newPublicNumber(PDO $pdo): string
    {
        $lastNumber = (int) $pdo->query(
            "SELECT COALESCE(MAX(CAST(SUBSTR(public_number, 2) AS INTEGER)), 0)
             FROM orders
             WHERE public_number GLOB '#[0-9]*'
               AND SUBSTR(public_number, 2) NOT GLOB '*[^0-9]*'"
        )->fetchColumn();

        $numberFloor = $this->integerSetting('order_number_floor', 9999);

        return '#' . (string) max(10000, $numberFloor + 1, $lastNumber + 1);
    }

    private function integerSetting(string $key, int $default): int
    {
        return (int) $this->stringSetting($key, (string) $default);
    }

    private function cartMaintenanceEnabled(): bool
    {
        return in_array(
            strtolower($this->stringSetting('cart_maintenance_enabled', '0')),
            ['1', 'true', 'on'],
            true
        );
    }

    private function stringSetting(string $key, string $default): string
    {
        $query = $this->pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $query->execute(['key' => $key]);
        $value = $query->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    private function publicPaymentUrl(int $orderId, string $uploadToken): string
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        $storePath = trim(
            (string) ($this->config['public_store_path'] ?? '/v1'),
            '/'
        );
        $path = ($storePath !== '' ? '/' . $storePath : '')
            . '/payment.php?'
            . http_build_query([
                'order' => $orderId,
                'token' => $uploadToken,
            ], '', '&', PHP_QUERY_RFC3986);

        return $baseUrl !== '' ? $baseUrl . $path : $path;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function queueOrderMail(
        PDO $pdo,
        int $orderId,
        string $recipient,
        string $subject,
        string $template,
        array $payload
    ): void {
        $insert = $pdo->prepare(
            'INSERT INTO mail_queue(
                order_id, recipient, subject, template, payload_json
             ) VALUES(
                :order_id, :recipient, :subject, :template, :payload_json
             )'
        );
        $insert->execute([
            'order_id' => $orderId,
            'recipient' => $recipient,
            'subject' => $subject,
            'template' => $template,
            'payload_json' => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ]);
    }

    private function recordEvent(
        PDO $pdo,
        int $orderId,
        ?int $actorUserId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        string $detail
    ): void {
        $insert = $pdo->prepare(
            'INSERT INTO order_events(
                order_id, actor_user_id, event_type,
                from_status, to_status, detail
             ) VALUES(
                :order_id, :actor_user_id, :event_type,
                :from_status, :to_status, :detail
             )'
        );
        $insert->execute([
            'order_id' => $orderId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'detail' => $detail,
        ]);
    }

    private function recordStockMovement(
        PDO $pdo,
        int $variantId,
        int $orderId,
        int $actorUserId,
        int $quantityDelta,
        string $reference
    ): void {
        $insert = $pdo->prepare(
            'INSERT INTO stock_movements(
                variant_id, order_id, actor_user_id,
                on_hand_delta, reserved_delta, reason, reference
             ) VALUES(
                :variant_id, :order_id, :actor_user_id,
                :on_hand_delta, 0, :reason, :reference
             )'
        );
        $insert->execute([
            'variant_id' => $variantId,
            'order_id' => $orderId,
            'actor_user_id' => $actorUserId,
            'on_hand_delta' => -$quantityDelta,
            'reason' => 'order_edit_stock',
            'reference' => $reference,
        ]);
    }
}
