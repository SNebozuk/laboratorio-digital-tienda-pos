<?php
declare(strict_types=1);
namespace LaboratorioDigital;
use PDO;
final class OrderTracking
{
    public const SCHEMA = 'CREATE TABLE IF NOT EXISTS order_tracking_links (order_id INTEGER PRIMARY KEY REFERENCES orders(id) ON DELETE CASCADE, token_hash TEXT NOT NULL UNIQUE, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)';
    public function __construct(private readonly PDO $pdo) {}
    public function issue(int $orderId, array $config): string
    {
        $token = bin2hex(random_bytes(32));
        $q = $this->pdo->prepare('INSERT INTO order_tracking_links(order_id, token_hash) VALUES(?, ?)');
        $q->execute([$orderId, hash('sha256', $token)]);
        $path = trim((string) ($config['public_store_path'] ?? '/v1'), '/');
        return rtrim((string) ($config['base_url'] ?? ''), '/') . ($path !== '' ? '/' . $path : '') . '/seguimiento.php?token=' . $token;
    }
    public function lookup(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $q = $this->pdo->prepare('SELECT o.* FROM orders o JOIN order_tracking_links l ON l.order_id=o.id WHERE l.token_hash=?');
        $q->execute([hash('sha256', $token)]);
        $main = $q->fetch();
        if (!$main) return null;
        $slots = $this->pdo->query('SELECT slot_number, order_numbers, customer_name FROM delivery_slots')->fetchAll(PDO::FETCH_UNIQUE);
        $active = [];
        $email = strtolower(trim((string) $main['customer_email']));
        $phone = self::phone((string) $main['customer_phone']);
        $q = $this->pdo->prepare("SELECT * FROM orders WHERE id<>? AND status<>'cancelled' AND (lower(trim(coalesce(customer_email,'')))=? OR coalesce(customer_email,'')='') ORDER BY created_at DESC, id DESC");
        $q->execute([$main['id'], $email]);
        foreach ($q->fetchAll() as $order) {
            $otherEmail = strtolower(trim((string) $order['customer_email']));
            $same = $email !== '' && $otherEmail === $email;
            if (!$same && $otherEmail === '' && strlen($phone) >= 8) $same = self::phone((string) $order['customer_phone']) === $phone;
            if ($same && (!$order['archived_at'] || $this->liveSlot($order, $slots))) $active[] = $this->present($order, $slots);
        }
        return ['order' => $this->present($main, $slots), 'others' => $active];
    }
    private static function phone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) === 13 && str_starts_with($digits, '549')) return '54' . substr($digits, 3);
        if (strlen($digits) === 10) return '54' . $digits;
        return $digits;
    }
    private function liveSlot(array $order, array $slots): ?array
    {
        $slot = $slots[(int) ($order['delivery_slot_number'] ?? 0)] ?? null;
        if (!$slot || $order['delivery_reopened_at']) return null;
        return in_array(trim($order['public_number']), array_map('trim', explode('/', $slot['order_numbers'])), true) ? $slot : null;
    }
    private function present(array $order, array $slots): array
    {
        $slot = $this->liveSlot($order, $slots);
        $state = 'Recibida';
        if ($order['status'] === 'cancelled') $state = 'Cancelada';
        elseif ($slot) $state = preg_match('/(?:✓|📦)\s*$/u', $slot['customer_name']) ? 'Listo para entregar' : 'En preparación';
        elseif ($order['archived_at']) $state = 'Archivada';
        $q = $this->pdo->prepare('SELECT product_name, variant_name, quantity, unit_price_cents, line_total_cents FROM order_items WHERE order_id=? ORDER BY id');
        $q->execute([$order['id']]);
        return ['number' => $order['public_number'], 'state' => $state, 'created_at' => $order['created_at'], 'total_cents' => (int) $order['total_cents'], 'discount_cents' => (int) $order['discount_cents'], 'items' => $q->fetchAll()];
    }
}
