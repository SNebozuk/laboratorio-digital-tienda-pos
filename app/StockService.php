<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use DateTimeImmutable;
use PDO;

final class StockService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Reserva las variantes de un pedido al informar el pago.
     *
     * La operación adicional se ejecuta dentro de la misma transacción. Se usa
     * para guardar el registro del comprobante sin dejar una reserva huérfana.
     *
     * @param null|callable(PDO, array<string, mixed>): void $additionalOperation
     */
    public function reserveForReportedPayment(
        int $orderId,
        ?int $actorUserId = null,
        ?callable $additionalOperation = null
    ): void {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $orderId,
                $actorUserId,
                $additionalOperation
            ): void {
                $order = $this->lockedOrder($pdo, $orderId);
                $this->assertCanReportPayment($order);

                $oldStatus = (string) $order['status'];
                if ($order['stock_reserved_at'] === null) {
                    foreach ($this->orderItems($pdo, $orderId) as $item) {
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
                        $reserve->bindValue(
                            ':variant_id',
                            (int) $item['variant_id'],
                            PDO::PARAM_INT
                        );
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

                        $this->recordMovement(
                            $pdo,
                            (int) $item['variant_id'],
                            $orderId,
                            $actorUserId,
                            -$quantity,
                            0,
                            'payment_reported_stock',
                            (string) $order['public_number']
                        );
                    }
                }

                if ($additionalOperation !== null) {
                    $additionalOperation($pdo, $order);
                }

                $update = $pdo->prepare(
                    'UPDATE orders
                     SET status = :status,
                         stock_reserved_at = COALESCE(
                             stock_reserved_at,
                             CURRENT_TIMESTAMP
                         ),
                         rejection_deadline_at = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute([
                    'status' => 'payment_reported',
                    'id' => $orderId,
                ]);

                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'payment_reported',
                    $oldStatus,
                    'payment_reported',
                    'Comprobante recibido; stock reservado.'
                );
            }
        );
    }

    public function releaseOrderReservation(
        int $orderId,
        string $reason,
        ?int $actorUserId = null
    ): void {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $reason, $actorUserId): void {
                $order = $this->lockedOrder($pdo, $orderId);
                if ($order['stock_reserved_at'] === null) {
                    return;
                }

                foreach ($this->orderItems($pdo, $orderId) as $item) {
                    $quantity = (int) $item['quantity'];
                    $release = $pdo->prepare(
                        'UPDATE product_variants
                         SET stock_on_hand = stock_on_hand + :quantity,
                             stock_reserved = 0,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :variant_id'
                    );
                    $release->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                    $release->bindValue(
                        ':variant_id',
                        (int) $item['variant_id'],
                        PDO::PARAM_INT
                    );
                    $release->execute();
                    if ($release->rowCount() !== 1) {
                        throw new ConflictException(
                            'No se pudo liberar una reserva de stock inconsistente.'
                        );
                    }

                    $this->recordMovement(
                        $pdo,
                        (int) $item['variant_id'],
                        $orderId,
                        $actorUserId,
                        $quantity,
                        0,
                        $reason,
                        (string) $order['public_number']
                    );
                }

                $update = $pdo->prepare(
                    'UPDATE orders
                     SET stock_reserved_at = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute(['id' => $orderId]);
            }
        );
    }

    public function cancelOrder(
        int $orderId,
        string $reason,
        ?int $actorUserId = null,
        bool $notifyCustomer = true,
        bool $restoreStock = true
    ): void {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $reason, $actorUserId, $notifyCustomer, $restoreStock): void {
                $order = $this->lockedOrder($pdo, $orderId);
                if ($order['archived_at'] !== null) {
                    throw new ConflictException('Una venta archivada no se puede cancelar. Primero reabrila si necesitás corregirla.');
                }
                if ($order['status'] === 'cancelled') {
                    return;
                }
                if ($order['stock_reserved_at'] !== null) {
                    if ($restoreStock) {
                        $this->releaseWithinTransaction($pdo, $orderId, (string) $order['public_number'], $actorUserId, $reason);
                    } else {
                        $this->consumeReservationWithoutRestoring($pdo, $orderId, (string) $order['public_number'], $actorUserId, $reason);
                    }
                }

                if ($order['status'] === 'delivered' && $restoreStock) {
                    $this->restoreConsumedOrderWithinTransaction(
                        $pdo,
                        $orderId,
                        (string) $order['public_number'],
                        $actorUserId,
                        $reason
                    );
                }

                $this->cancelWithinTransaction(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    $reason,
                    'Pedido cancelado y stock liberado.',
                    $notifyCustomer
                );
            }
        );
    }

    public function consumeOrderReservation(
        int $orderId,
        ?int $actorUserId = null
    ): void {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $actorUserId): void {
                $order = $this->lockedOrder($pdo, $orderId);
                $oldStatus = (string) $order['status'];
                if (!in_array($oldStatus, ['paid_prepare', 'ready_pickup'], true)) {
                    throw new ConflictException(
                        'El pedido debe estar pagado o listo para entregar.'
                    );
                }
                if ($order['stock_reserved_at'] === null) {
                    throw new ConflictException('El pedido no tiene stock reservado.');
                }
                if (
                    $order['payment_method'] === 'cash'
                    && $order['payment_deadline_at'] !== null
                    && new DateTimeImmutable((string) $order['payment_deadline_at']) < new DateTimeImmutable()
                ) {
                    throw new ConflictException(
                        'La reserva de 2 horas para pago en efectivo ya venció.'
                    );
                }

                $update = $pdo->prepare(
                    'UPDATE orders
                     SET status = :status,
                         stock_reserved_at = NULL,
                         delivered_at = CURRENT_TIMESTAMP,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute([
                    'status' => 'delivered',
                    'id' => $orderId,
                ]);

                $this->recordEvent(
                    $pdo,
                    $orderId,
                    $actorUserId,
                    'order_delivered',
                    $oldStatus,
                    'delivered',
                    'Pedido entregado; reserva convertida en salida física.'
                );
            }
        );
    }

    /**
     * Cancela pedidos vencidos y libera solamente las reservas que correspondan.
     *
     * @return array{cancelled_without_reservation: int, released_reservations: int, released_cash_reservations: int}
     */
    public function expireOrders(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $timestamp = $now->format('Y-m-d H:i:s');

        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($timestamp): array {
                $pending = $pdo->prepare(
                    'SELECT id, public_number
                     FROM orders
                     WHERE status = :status
                       AND stock_reserved_at IS NULL
                       AND payment_deadline_at < :now'
                );
                $pending->execute([
                    'status' => 'pending_payment',
                    'now' => $timestamp,
                ]);
                $pendingOrders = $pending->fetchAll();

                foreach ($pendingOrders as $order) {
                    $this->cancelWithinTransaction(
                        $pdo,
                        (int) $order['id'],
                        null,
                        'payment_window_expired',
                        'El plazo para informar el pago venció.'
                    );
                }

                $cash = $pdo->prepare(
                    "SELECT id, public_number
                     FROM orders
                     WHERE payment_method = 'cash'
                       AND status IN ('pending_payment', 'ready_pickup')
                       AND stock_reserved_at IS NOT NULL
                       AND payment_deadline_at < :now"
                );
                $cash->execute(['now' => $timestamp]);
                $cashOrders = $cash->fetchAll();

                foreach ($cashOrders as $order) {
                    $this->releaseWithinTransaction(
                        $pdo,
                        (int) $order['id'],
                        (string) $order['public_number'],
                        null,
                        'cash_reservation_expired'
                    );
                    $this->cancelWithinTransaction(
                        $pdo,
                        (int) $order['id'],
                        null,
                        'cash_reservation_expired',
                        'Venció la reserva de 2 horas para pago en efectivo. Los productos volvieron al stock.'
                    );
                }

                $rejected = $pdo->prepare(
                    'SELECT id, public_number
                     FROM orders
                     WHERE status = :status
                       AND stock_reserved_at IS NOT NULL
                       AND rejection_deadline_at < :now'
                );
                $rejected->execute([
                    'status' => 'rejected',
                    'now' => $timestamp,
                ]);
                $rejectedOrders = $rejected->fetchAll();

                foreach ($rejectedOrders as $order) {
                    $this->releaseWithinTransaction(
                        $pdo,
                        (int) $order['id'],
                        (string) $order['public_number'],
                        null,
                        'rejected_payment_expired'
                    );
                    $this->cancelWithinTransaction(
                        $pdo,
                        (int) $order['id'],
                        null,
                        'rejected_payment_expired',
                        'Venció el plazo para enviar un nuevo comprobante.'
                    );
                }

                return [
                    'cancelled_without_reservation' => count($pendingOrders),
                    'released_reservations' => count($rejectedOrders),
                    'released_cash_reservations' => count($cashOrders),
                ];
            }
        );
    }

    /** @return array<string, mixed> */
    private function lockedOrder(PDO $pdo, int $orderId): array
    {
        $query = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $query->execute(['id' => $orderId]);
        $order = $query->fetch();

        if (!$order) {
            throw new ValidationException('El pedido no existe.');
        }

        return $order;
    }

    /** @param array<string, mixed> $order */
    private function assertCanReportPayment(array $order): void
    {
        if (!in_array($order['status'], ['pending_payment', 'rejected'], true)) {
            throw new ConflictException('Este pedido ya no admite comprobantes.');
        }

        $now = new DateTimeImmutable();
        $deadline = $order['status'] === 'rejected'
            ? $order['rejection_deadline_at']
            : $order['payment_deadline_at'];

        if ($deadline !== null && new DateTimeImmutable((string) $deadline) < $now) {
            throw new ConflictException('El plazo para informar el pago venció.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function orderItems(PDO $pdo, int $orderId): array
    {
        $query = $pdo->prepare(
            'SELECT
                oi.variant_id,
                oi.quantity,
                oi.product_name,
                oi.variant_name
             FROM order_items oi
             WHERE oi.order_id = :order_id
             ORDER BY oi.id'
        );
        $query->execute(['order_id' => $orderId]);
        $items = $query->fetchAll();

        if ($items === []) {
            throw new ConflictException('El pedido no contiene productos.');
        }

        return $items;
    }

    private function releaseWithinTransaction(
        PDO $pdo,
        int $orderId,
        string $publicNumber,
        ?int $actorUserId,
        string $reason
    ): void {
        foreach ($this->orderItems($pdo, $orderId) as $item) {
            $quantity = (int) $item['quantity'];
            $release = $pdo->prepare(
                'UPDATE product_variants
                 SET stock_on_hand = stock_on_hand + :quantity,
                     stock_reserved = 0,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :variant_id'
            );
            $release->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $release->bindValue(
                ':variant_id',
                (int) $item['variant_id'],
                PDO::PARAM_INT
            );
            $release->execute();
            if ($release->rowCount() !== 1) {
                throw new ConflictException(
                    'No se pudo liberar una reserva de stock inconsistente.'
                );
            }

            $this->recordMovement(
                $pdo,
                (int) $item['variant_id'],
                $orderId,
                $actorUserId,
                $quantity,
                0,
                $reason,
                $publicNumber
            );
        }

        $clear = $pdo->prepare(
            'UPDATE orders
             SET stock_reserved_at = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $clear->execute(['id' => $orderId]);
    }

    /** Cancela una reserva y descuenta definitivamente las unidades cuando no deben volver al stock. */
    private function consumeReservationWithoutRestoring(
        PDO $pdo,
        int $orderId,
        string $publicNumber,
        ?int $actorUserId,
        string $reason
    ): void {
        $clear = $pdo->prepare('UPDATE orders SET stock_reserved_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $clear->execute(['id' => $orderId]);
    }

    private function restoreConsumedOrderWithinTransaction(
        PDO $pdo,
        int $orderId,
        string $publicNumber,
        ?int $actorUserId,
        string $reason
    ): void {
        foreach ($this->orderItems($pdo, $orderId) as $item) {
            $quantity = (int) $item['quantity'];
            $restore = $pdo->prepare(
                'UPDATE product_variants
                 SET stock_on_hand = stock_on_hand + :quantity,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :variant_id'
            );
            $restore->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $restore->bindValue(':variant_id', (int) $item['variant_id'], PDO::PARAM_INT);
            $restore->execute();
            if ($restore->rowCount() !== 1) {
                throw new ConflictException('No se pudo restaurar el stock de una variante.');
            }

            $this->recordMovement(
                $pdo,
                (int) $item['variant_id'],
                $orderId,
                $actorUserId,
                $quantity,
                0,
                $reason . '_restore_stock',
                $publicNumber
            );
        }
    }

    private function cancelWithinTransaction(
        PDO $pdo,
        int $orderId,
        ?int $actorUserId,
        string $event,
        string $detail,
        bool $notifyCustomer = true
    ): void {
        $old = $pdo->prepare(
            'SELECT status, public_number, customer_name, customer_email, customer_phone
             FROM orders
             WHERE id = :id'
        );
        $old->execute(['id' => $orderId]);
        $order = $old->fetch();
        $oldStatus = (string) $order['status'];

        $cancel = $pdo->prepare(
            'UPDATE orders
             SET status = :status,
                 cancelled_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $cancel->execute(['status' => 'cancelled', 'id' => $orderId]);

        $this->recordEvent(
            $pdo,
            $orderId,
            $actorUserId,
            $event,
            $oldStatus,
            'cancelled',
            $detail
        );

        if ($notifyCustomer && !empty($order['customer_email'])) {
            $mail = $pdo->prepare(
                'INSERT INTO mail_queue(
                    order_id, recipient, subject, template, payload_json
                 ) VALUES(
                    :order_id, :recipient, :subject, :template, :payload_json
                 )'
            );
            $mail->execute([
                'order_id' => $orderId,
                'recipient' => $order['customer_email'],
                'subject' => 'Pedido cancelado · ' . $order['public_number'],
                'template' => 'order_cancelled',
                'payload_json' => json_encode([
                    'public_number' => $order['public_number'],
                    'customer_name' => $order['customer_name'],
                    'detail' => $detail,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
        }
        if (
            $notifyCustomer
            && (!empty($order['customer_phone']) || !empty($order['customer_email']))
        ) {
            $notification = $pdo->prepare(
                'INSERT INTO customer_notification_queue(
                    order_id, event_type, customer_phone, customer_email, payload_json
                 ) VALUES(
                    :order_id, :event_type, :customer_phone, :customer_email, :payload_json
                 )'
            );
            $notification->execute([
                'order_id' => $orderId,
                'event_type' => 'order_cancelled',
                'customer_phone' => $order['customer_phone'] ?: null,
                'customer_email' => $order['customer_email'] ?: null,
                'payload_json' => json_encode([
                    'public_number' => $order['public_number'],
                    'customer_name' => $order['customer_name'],
                    'detail' => $detail,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function recordMovement(
        PDO $pdo,
        int $variantId,
        int $orderId,
        ?int $actorUserId,
        int $onHandDelta,
        int $reservedDelta,
        string $reason,
        string $reference
    ): void {
        $insert = $pdo->prepare(
            'INSERT INTO stock_movements(
                variant_id, order_id, actor_user_id,
                on_hand_delta, reserved_delta, reason, reference
             ) VALUES(
                :variant_id, :order_id, :actor_user_id,
                :on_hand_delta, :reserved_delta, :reason, :reference
             )'
        );
        $insert->execute([
            'variant_id' => $variantId,
            'order_id' => $orderId,
            'actor_user_id' => $actorUserId,
            'on_hand_delta' => $onHandDelta,
            'reserved_delta' => $reservedDelta,
            'reason' => $reason,
            'reference' => $reference,
        ]);
    }

    private function recordEvent(
        PDO $pdo,
        int $orderId,
        ?int $actorUserId,
        string $event,
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
            'event_type' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'detail' => $detail,
        ]);
    }
}
