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
                             SET stock_reserved = stock_reserved + :quantity,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = :variant_id
                               AND active = 1
                               AND stock_on_hand - stock_reserved >= :quantity'
                        );
                        $reserve->execute([
                            'quantity' => $quantity,
                            'variant_id' => $item['variant_id'],
                        ]);
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
                            0,
                            $quantity,
                            'payment_proof_reservation',
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
                         SET stock_reserved = stock_reserved - :quantity,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :variant_id
                           AND stock_reserved >= :quantity'
                    );
                    $release->execute([
                        'quantity' => $quantity,
                        'variant_id' => $item['variant_id'],
                    ]);
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
                        0,
                        -$quantity,
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
        ?int $actorUserId = null
    ): void {
        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($orderId, $reason, $actorUserId): void {
                $order = $this->lockedOrder($pdo, $orderId);
                if ($order['status'] === 'cancelled') {
                    return;
                }
                if ($order['status'] === 'delivered') {
                    throw new ConflictException(
                        'Una venta entregada requiere una devolución, no una cancelación.'
                    );
                }

                if ($order['stock_reserved_at'] !== null) {
                    $this->releaseWithinTransaction(
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
                    'Pedido cancelado y stock liberado.'
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

                foreach ($this->orderItems($pdo, $orderId) as $item) {
                    $quantity = (int) $item['quantity'];
                    $consume = $pdo->prepare(
                        'UPDATE product_variants
                         SET stock_on_hand = stock_on_hand - :quantity,
                             stock_reserved = stock_reserved - :quantity,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :variant_id
                           AND stock_on_hand >= :quantity
                           AND stock_reserved >= :quantity'
                    );
                    $consume->execute([
                        'quantity' => $quantity,
                        'variant_id' => $item['variant_id'],
                    ]);
                    if ($consume->rowCount() !== 1) {
                        throw new ConflictException(
                            'No se pudo entregar un pedido con stock inconsistente.'
                        );
                    }

                    $this->recordMovement(
                        $pdo,
                        (int) $item['variant_id'],
                        $orderId,
                        $actorUserId,
                        -$quantity,
                        -$quantity,
                        'order_delivered',
                        (string) $order['public_number']
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
     * @return array{cancelled_without_reservation: int, released_reservations: int}
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
                 SET stock_reserved = stock_reserved - :quantity,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :variant_id
                   AND stock_reserved >= :quantity'
            );
            $release->execute([
                'quantity' => $quantity,
                'variant_id' => $item['variant_id'],
            ]);
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
                0,
                -$quantity,
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

    private function cancelWithinTransaction(
        PDO $pdo,
        int $orderId,
        ?int $actorUserId,
        string $event,
        string $detail
    ): void {
        $old = $pdo->prepare(
            'SELECT status, public_number, customer_name, customer_email
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

        if (!empty($order['customer_email'])) {
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
