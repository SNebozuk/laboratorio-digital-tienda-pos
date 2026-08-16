<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

/** Ubicaciones fijas para el armado físico de pedidos. */
final class DeliveryService
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function slots(): array
    {
        return $this->pdo->query('SELECT * FROM delivery_slots ORDER BY slot_number')->fetchAll();
    }

    /** @return array<string, mixed> */
    public function saveSlot(int $slot, array $input, int $actorUserId): array
    {
        $this->assertSlot($slot);
        $expected = max(0, (int) ($input['revision'] ?? 0));
        $location = $this->clean($input['location'] ?? '');
        $orders = $this->clean($input['order_numbers'] ?? '');
        $customer = $this->clean($input['customer_name'] ?? '');
        $transfers = $this->clean($input['transfers'] ?? '');

        return Database::immediate($this->pdo, function (PDO $pdo) use ($slot, $expected, $location, $orders, $customer, $transfers, $actorUserId): array {
            $existing = $this->findSlot($pdo, $slot);
            $revision = (int) ($existing['revision'] ?? 0);
            if ($revision !== $expected) {
                throw new ConflictException('Esta ubicación fue actualizada por otra persona. Se recargó la información para evitar sobrescribirla.');
            }
            if ($location === '' && $orders === '' && $customer === '' && $transfers === '') {
                if ($existing) $pdo->prepare('DELETE FROM delivery_slots WHERE slot_number = :slot')->execute(['slot' => $slot]);
                return ['slot_number' => $slot, 'revision' => 0, 'location' => '', 'order_numbers' => '', 'customer_name' => '', 'transfers' => ''];
            }
            if ($existing) {
                $statement = $pdo->prepare('UPDATE delivery_slots SET location = :location, order_numbers = :orders, customer_name = :customer, transfers = :transfers, revision = revision + 1, updated_by = :user, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot AND revision = :revision');
                $statement->execute(compact('location', 'orders', 'customer', 'transfers') + ['user' => $actorUserId, 'slot' => $slot, 'revision' => $revision]);
            } else {
                $statement = $pdo->prepare('INSERT INTO delivery_slots(slot_number, location, order_numbers, customer_name, transfers, revision, updated_by) VALUES(:slot, :location, :orders, :customer, :transfers, 1, :user)');
                $statement->execute(compact('location', 'orders', 'customer', 'transfers') + ['slot' => $slot, 'user' => $actorUserId]);
            }
            return $this->findSlot($pdo, $slot) ?: throw new \RuntimeException('No se pudo guardar la ubicación.');
        });
    }

    /** @return array<string, mixed> */
    public function copyOrder(int $orderId, int $slot, int $actorUserId): array
    {
        $this->assertSlot($slot);
        return Database::immediate($this->pdo, function (PDO $pdo) use ($orderId, $slot, $actorUserId): array {
            $orderQuery = $pdo->prepare('SELECT id, public_number, customer_name, delivery_slot_number FROM orders WHERE id = :id');
            $orderQuery->execute(['id' => $orderId]);
            $order = $orderQuery->fetch();
            if (!$order) throw new ValidationException('La venta no existe.');
            if ($order['delivery_slot_number'] !== null) throw new ConflictException('Esta venta ya fue copiada a Entregas.');
            $existing = $this->findSlot($pdo, $slot);
            $hasOrder = trim((string) ($existing['order_numbers'] ?? '')) !== '';
            $orderNumbers = $hasOrder ? trim((string) $existing['order_numbers']) . ' / ' . $order['public_number'] : (string) $order['public_number'];
            $customer = $hasOrder
                ? $this->setMarker((string) ($existing['customer_name'] ?? ''), 'AGREGAR')
                : trim((string) $order['customer_name']) . ' · ARMAR';
            if ($existing) {
                $pdo->prepare('UPDATE delivery_slots SET order_numbers = :orders, customer_name = :customer, revision = revision + 1, updated_by = :user, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot')
                    ->execute(['orders' => $orderNumbers, 'customer' => $customer, 'user' => $actorUserId, 'slot' => $slot]);
            } else {
                $pdo->prepare('INSERT INTO delivery_slots(slot_number, order_numbers, customer_name, revision, updated_by) VALUES(:slot, :orders, :customer, 1, :user)')
                    ->execute(['slot' => $slot, 'orders' => $orderNumbers, 'customer' => $customer, 'user' => $actorUserId]);
            }
            $updated = $pdo->prepare('UPDATE orders SET delivery_slot_number = :slot, delivery_copied_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND delivery_slot_number IS NULL');
            $updated->execute(['slot' => $slot, 'id' => $orderId]);
            if ($updated->rowCount() !== 1) throw new ConflictException('Esta venta fue copiada por otra persona.');
            return ['slot' => $this->findSlot($pdo, $slot), 'order_id' => $orderId];
        });
    }

    private function assertSlot(int $slot): void { if ($slot < 1 || $slot > 100) throw new ValidationException('Elegí una ubicación entre 1 y 100.'); }
    private function clean(mixed $value): string { $text = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''); return function_exists('mb_substr') ? mb_substr($text, 0, 500) : substr($text, 0, 500); }
    /** @return array<string, mixed>|false */
    private function findSlot(PDO $pdo, int $slot): array|false { $q = $pdo->prepare('SELECT * FROM delivery_slots WHERE slot_number = :slot'); $q->execute(['slot' => $slot]); return $q->fetch(); }
    private function setMarker(string $value, string $marker): string { $base = trim(preg_replace('/\s*·?\s*(ARMAR|AGREGAR)\s*$/iu', '', $value) ?? ''); return trim($base . ' · ' . $marker); }
}
