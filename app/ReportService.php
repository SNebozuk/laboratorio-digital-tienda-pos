<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class ReportService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        return [
            'summary' => $this->summary(),
            'status_counts' => $this->statusCounts(),
            'daily_sales' => $this->dailySales(),
            'top_products' => $this->topProducts(),
            'low_stock' => $this->lowStock(),
            'recent_movements' => $this->recentMovements(),
        ];
    }

    /** @return array<string, int> */
    private function summary(): array
    {
        $sales = $this->pdo->query(
            "SELECT
                COUNT(*) AS sale_count,
                COALESCE(SUM(total_cents), 0) AS sale_total_cents
             FROM orders
             WHERE status = 'delivered'
               AND date(
                    COALESCE(delivered_at, created_at),
                    'localtime'
               ) = date('now', 'localtime')"
        )->fetch();

        $pending = $this->pdo->query(
            "SELECT COUNT(*)
             FROM orders
             WHERE status IN (
                'pending_payment',
                'payment_reported',
                'rejected',
                'paid_prepare',
                'ready_pickup'
             )"
        )->fetchColumn();

        $reserved = $this->pdo->query(
            'SELECT COALESCE(SUM(stock_reserved), 0)
             FROM product_variants'
        )->fetchColumn();

        $lowStock = $this->pdo->query(
            'SELECT COUNT(*)
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE p.active = 1
               AND v.active = 1
               AND v.stock_on_hand - v.stock_reserved <= v.min_stock'
        )->fetchColumn();

        return [
            'today_sale_count' => (int) ($sales['sale_count'] ?? 0),
            'today_sale_total_cents' => (int) ($sales['sale_total_cents'] ?? 0),
            'active_order_count' => (int) $pending,
            'reserved_unit_count' => (int) $reserved,
            'low_stock_variant_count' => (int) $lowStock,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function statusCounts(): array
    {
        return $this->pdo->query(
            'SELECT status, COUNT(*) AS order_count
             FROM orders
             GROUP BY status
             ORDER BY order_count DESC, status'
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    private function dailySales(): array
    {
        return $this->pdo->query(
            "SELECT
                date(
                    COALESCE(delivered_at, created_at),
                    'localtime'
                ) AS sale_day,
                COUNT(*) AS sale_count,
                COALESCE(SUM(total_cents), 0) AS total_cents
             FROM orders
             WHERE status = 'delivered'
               AND datetime(
                    COALESCE(delivered_at, created_at)
               ) >= datetime('now', '-13 days')
             GROUP BY sale_day
             ORDER BY sale_day DESC"
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    private function topProducts(): array
    {
        return $this->pdo->query(
            "SELECT
                oi.product_name,
                oi.variant_name,
                SUM(oi.quantity) AS units,
                SUM(oi.line_total_cents) AS total_cents
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.status = 'delivered'
               AND datetime(
                    COALESCE(o.delivered_at, o.created_at)
               ) >= datetime('now', '-29 days')
             GROUP BY oi.product_name, oi.variant_name
             ORDER BY units DESC, total_cents DESC
             LIMIT 15"
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    private function lowStock(): array
    {
        return $this->pdo->query(
            'SELECT
                p.name AS product_name,
                v.name AS variant_name,
                v.stock_on_hand,
                v.stock_reserved,
                v.stock_on_hand - v.stock_reserved AS available_stock,
                v.min_stock
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE p.active = 1
               AND v.active = 1
               AND v.stock_on_hand - v.stock_reserved <= v.min_stock
             ORDER BY available_stock, p.name, v.sort_order, v.name
             LIMIT 50'
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    private function recentMovements(): array
    {
        return $this->pdo->query(
            'SELECT
                sm.id,
                sm.on_hand_delta,
                sm.reserved_delta,
                sm.reason,
                sm.reference,
                sm.created_at,
                p.name AS product_name,
                v.name AS variant_name,
                u.name AS actor_name
             FROM stock_movements sm
             JOIN product_variants v ON v.id = sm.variant_id
             JOIN products p ON p.id = v.product_id
             LEFT JOIN users u ON u.id = sm.actor_user_id
             ORDER BY sm.id DESC
             LIMIT 50'
        )->fetchAll();
    }
}
