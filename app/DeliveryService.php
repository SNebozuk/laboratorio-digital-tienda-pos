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
        $slots = $this->pdo->query('SELECT * FROM delivery_slots ORDER BY slot_number')->fetchAll();
        $orders = $this->pdo->query(
            'SELECT id, public_number, customer_name, total_cents, delivery_slot_number
             FROM orders
             WHERE delivery_slot_number IS NOT NULL AND delivery_reopened_at IS NULL
             ORDER BY id ASC'
        )->fetchAll();
        $bySlot = [];
        foreach ($orders as $order) {
            $bySlot[(int) $order['delivery_slot_number']][] = $order;
        }
        foreach ($slots as &$slot) {
            $slot['orders'] = $bySlot[(int) $slot['slot_number']] ?? [];
        }
        unset($slot);
        return $slots;
    }

    /** @return array<string, mixed> */
    public function saveSlot(int $slot, array $input, int $actorUserId): array
    {
        $this->assertSlot($slot);
        $expected = max(0, (int) ($input['revision'] ?? 0));
        $location = $this->normalizeLocation($input['location'] ?? '');
        $orders = $this->clean($input['order_numbers'] ?? '');
        $customer = $this->upper($input['customer_name'] ?? '');
        $transfers = $this->clean($input['transfers'] ?? '');
        $cashDue = $this->clean($input['cash_due'] ?? '');

        return Database::immediate($this->pdo, function (PDO $pdo) use ($slot, $expected, $location, $orders, $customer, $transfers, $cashDue, $actorUserId): array {
            $existing = $this->findSlot($pdo, $slot);
            $revision = (int) ($existing['revision'] ?? 0);
            if ($revision !== $expected) {
                throw new ConflictException('Esta ubicación fue actualizada por otra persona. Se recargó la información para evitar sobrescribirla.');
            }
            if ($location === '' && $orders === '' && $customer === '' && $transfers === '' && $cashDue === '') {
                if ($existing) $pdo->prepare('DELETE FROM delivery_slots WHERE slot_number = :slot')->execute(['slot' => $slot]);
                return ['slot_number' => $slot, 'revision' => 0, 'location' => '', 'order_numbers' => '', 'customer_name' => '', 'transfers' => ''];
            }
            if ($existing) {
                $statement = $pdo->prepare('UPDATE delivery_slots SET location = :location, order_numbers = :orders, customer_name = :customer, transfers = :transfers, cash_due = :cash_due, revision = revision + 1, updated_by = :user, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot AND revision = :revision');
                $statement->execute(['location' => $location, 'orders' => $orders, 'customer' => $customer, 'transfers' => $transfers, 'cash_due' => $cashDue, 'user' => $actorUserId, 'slot' => $slot, 'revision' => $revision]);
            } else {
                $statement = $pdo->prepare('INSERT INTO delivery_slots(slot_number, location, order_numbers, customer_name, transfers, cash_due, revision, updated_by) VALUES(:slot, :location, :orders, :customer, :transfers, :cash_due, 1, :user)');
                $statement->execute(['location' => $location, 'orders' => $orders, 'customer' => $customer, 'transfers' => $transfers, 'cash_due' => $cashDue, 'slot' => $slot, 'user' => $actorUserId]);
            }
            return $this->findSlot($pdo, $slot) ?: throw new \RuntimeException('No se pudo guardar la ubicación.');
        });
    }

    /** @return array<string, mixed> */
    public function copyOrder(int $orderId, int $slot, int $actorUserId): array
    {
        return $this->copyOrders([$orderId], $slot, $actorUserId);
    }

    /** @param list<int> $orderIds @return array<string, mixed> */
    public function copyOrders(array $orderIds, int $slot, int $actorUserId): array
    {
        $this->assertSlot($slot);
        $ids = array_values(array_unique(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) throw new ValidationException('Elegí al menos una venta para pasar a Entregas.');
        return Database::immediate($this->pdo, function (PDO $pdo) use ($ids, $slot, $actorUserId): array {
            $orderQuery = $pdo->prepare('SELECT id, public_number, customer_name, total_cents, status, archived_at, delivery_slot_number, delivery_reopened_at FROM orders WHERE id = :id');
            $orders = [];
            foreach ($ids as $orderId) {
                $orderQuery->execute(['id' => $orderId]);
                $order = $orderQuery->fetch();
                if (!$order) throw new ValidationException('Una de las ventas ya no existe.');
                if ((string) $order['status'] === 'cancelled') throw new ValidationException('No se puede pasar una venta cancelada a Entregas.');
                if ($order['archived_at'] !== null && $order['delivery_reopened_at'] === null) throw new ConflictException('Una de las ventas ya está archivada.');
                if ($order['delivery_slot_number'] !== null && $order['delivery_reopened_at'] === null) throw new ConflictException('Una de las ventas ya fue copiada a Entregas.');
                $orders[] = $order;
            }
            $existing = $this->findSlot($pdo, $slot);
            $hasOrder = trim((string) ($existing['order_numbers'] ?? '')) !== '';
            $newNumbers = implode(' / ', array_map(static fn (array $order): string => (string) $order['public_number'], $orders));
            $orderNumbers = $hasOrder ? trim((string) $existing['order_numbers']) . ' / ' . $newNumbers : $newNumbers;
            $customer = $hasOrder
                ? $this->setMarker((string) ($existing['customer_name'] ?? ''), 'AGREGAR')
                : trim((string) $orders[0]['customer_name']) . ' · ARMAR';
            $total = array_sum(array_map(static fn (array $order): int => (int) $order['total_cents'], $orders));
            if ($existing) {
                $pdo->prepare('UPDATE delivery_slots SET order_numbers = :orders, customer_name = :customer, order_total_cents = order_total_cents + :total, revision = revision + 1, updated_by = :user, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot')
                    ->execute(['orders' => $orderNumbers, 'customer' => $customer, 'total' => $total, 'user' => $actorUserId, 'slot' => $slot]);
            } else {
                $pdo->prepare('INSERT INTO delivery_slots(slot_number, order_numbers, customer_name, order_total_cents, revision, updated_by) VALUES(:slot, :orders, :customer, :total, 1, :user)')
                    ->execute(['slot' => $slot, 'orders' => $orderNumbers, 'customer' => $customer, 'total' => $total, 'user' => $actorUserId]);
            }
            $updated = $pdo->prepare('UPDATE orders SET delivery_slot_number = :slot, delivery_copied_at = CURRENT_TIMESTAMP, delivery_reopened_at = NULL, archived_at = CURRENT_TIMESTAMP, archived_by = :user, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND (delivery_slot_number IS NULL OR delivery_reopened_at IS NOT NULL)');
            foreach ($ids as $orderId) {
                $updated->execute(['slot' => $slot, 'user' => $actorUserId, 'id' => $orderId]);
                if ($updated->rowCount() !== 1) throw new ConflictException('Una venta fue copiada por otra persona.');
            }
            return ['slot' => $this->findSlot($pdo, $slot), 'order_ids' => $ids];
        });
    }

    public function deleteSlot(int $slot): void
    {
        $this->assertSlot($slot);
        Database::immediate($this->pdo, static function (PDO $pdo) use ($slot): void {
            // Vaciar una ubicación devuelve sus ventas a Lista de Ventas: una
            // orden no puede permanecer archivada si ya no está en Entregas.
            $pdo->prepare('UPDATE orders SET delivery_slot_number = NULL, delivery_copied_at = NULL, delivery_reopened_at = CURRENT_TIMESTAMP, archived_at = NULL, archived_by = NULL, updated_at = CURRENT_TIMESTAMP WHERE delivery_slot_number = :slot')
                ->execute(['slot' => $slot]);
            $pdo->prepare('DELETE FROM delivery_slots WHERE slot_number = :slot')->execute(['slot' => $slot]);
        });
    }

    /** Libera una venta reabierta para que pueda ubicarse nuevamente. */
    public function unassignOrder(int $orderId): void
    {
        Database::immediate($this->pdo, function (PDO $pdo) use ($orderId): void {
            $query = $pdo->prepare('SELECT public_number, total_cents, delivery_slot_number FROM orders WHERE id = :id');
            $query->execute(['id' => $orderId]);
            $order = $query->fetch();
            if (!$order || $order['delivery_slot_number'] === null) return;
            $slot = (int) $order['delivery_slot_number'];
            $existing = $this->findSlot($pdo, $slot);
            if ($existing) {
                $numbers = array_values(array_filter(array_map('trim', explode('/', (string) $existing['order_numbers'])), static fn (string $number): bool => $number !== '' && $number !== (string) $order['public_number']));
                $remaining = implode(' / ', $numbers);
                $total = max(0, (int) $existing['order_total_cents'] - (int) $order['total_cents']);
                $customer = $remaining === '' ? preg_replace('/\s*·?\s*(ARMAR|AGREGAR)\s*$/iu', '', (string) $existing['customer_name']) : (string) $existing['customer_name'];
                $pdo->prepare('UPDATE delivery_slots SET order_numbers = :orders, customer_name = :customer, order_total_cents = :total, revision = revision + 1, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot')
                    ->execute(['orders' => $remaining, 'customer' => trim((string) $customer), 'total' => $total, 'slot' => $slot]);
            }
            $pdo->prepare('UPDATE orders SET delivery_slot_number = NULL, delivery_copied_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute(['id' => $orderId]);
        });
    }

    /** Devuelve una venta desde Entregas a Lista de Ventas sin duplicarla. */
    public function returnOrderToSales(int $orderId): bool
    {
        return Database::immediate($this->pdo, function (PDO $pdo) use ($orderId): bool {
            $query = $pdo->prepare('SELECT public_number, total_cents, delivery_slot_number FROM orders WHERE id = :id');
            $query->execute(['id' => $orderId]);
            $order = $query->fetch();
            if (!$order || $order['delivery_slot_number'] === null) {
                return false;
            }
            $slot = (int) $order['delivery_slot_number'];
            $existing = $this->findSlot($pdo, $slot);
            if ($existing) {
                $numbers = array_values(array_filter(array_map('trim', explode('/', (string) $existing['order_numbers'])), static fn (string $number): bool => $number !== '' && $number !== (string) $order['public_number']));
                $remaining = implode(' / ', $numbers);
                $total = max(0, (int) $existing['order_total_cents'] - (int) $order['total_cents']);
                $customer = $remaining === ''
                    ? preg_replace('/\s*·?\s*(ARMAR|AGREGAR)\s*$/iu', '', (string) $existing['customer_name'])
                    : (string) $existing['customer_name'];
                $pdo->prepare('UPDATE delivery_slots SET order_numbers = :orders, customer_name = :customer, order_total_cents = :total, revision = revision + 1, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot')
                    ->execute(['orders' => $remaining, 'customer' => trim((string) $customer), 'total' => $total, 'slot' => $slot]);
            }
            $pdo->prepare('UPDATE orders SET delivery_slot_number = NULL, delivery_copied_at = NULL, delivery_reopened_at = CURRENT_TIMESTAMP, archived_at = NULL, archived_by = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute(['id' => $orderId]);
            return true;
        });
    }

    /** Habilita una única nueva ubicación sin tocar la planilla existente. */
    public function allowReopenedOrder(int $orderId): void
    {
        $this->pdo->prepare('UPDATE orders SET delivery_reopened_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND delivery_slot_number IS NOT NULL')
            ->execute(['id' => $orderId]);
    }

    private function assertSlot(int $slot): void { if ($slot < 1 || $slot > 100) throw new ValidationException('Elegí una ubicación entre 1 y 100.'); }
    private function clean(mixed $value): string { $text = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''); return function_exists('mb_substr') ? mb_substr($text, 0, 500) : substr($text, 0, 500); }
    private function upper(mixed $value): string
    {
        $text = $this->clean($value);
        return function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text);
    }
    private function normalizeLocation(mixed $value): string
    {
        $text = $this->clean($value);
        if ($text === '') return '';
        if (!preg_match('/^([a-z])\s*([0-9]{1,4})$/i', $text, $match)) {
            throw new ValidationException('La ubicación debe llevar una letra y un número, por ejemplo A1 o B12.');
        }
        return strtoupper($match[1]) . $match[2];
    }
    /** @return array<string, mixed>|false */
    private function findSlot(PDO $pdo, int $slot): array|false { $q = $pdo->prepare('SELECT * FROM delivery_slots WHERE slot_number = :slot'); $q->execute(['slot' => $slot]); return $q->fetch(); }
    private function setMarker(string $value, string $marker): string { $base = trim(preg_replace('/\s*·?\s*(ARMAR|AGREGAR)\s*$/iu', '', $value) ?? ''); return trim($base . ' · ' . $marker); }
}
