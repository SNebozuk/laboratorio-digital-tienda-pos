<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

/** Reconciles changes since the last observation; remote writes require a stock version. */
final class MercadoLibreStockSync
{
    public function __construct(private readonly PDO $pdo, private readonly ProductService $products) {}

    public function synchronize(int $variantId, string $itemId, int $actorId, callable $read, callable $write, bool $manualChange = false): array
    {
        $remote = $read();
        $key = 'meli_stock_' . $variantId;
        $query = $this->pdo->prepare('SELECT value FROM settings WHERE key=?');
        $query->execute([$key]);
        $state = json_decode((string) $query->fetchColumn(), true);
        if ($state && ($state['item_id'] ?? '') !== $itemId) throw new \RuntimeException('La vinculación de stock cambió. Revisá el producto.');
        if (!empty($state['pending'])) {
            // A timeout or interrupted process must never repeat a potentially applied write.
            throw new \RuntimeException('Hay un envío de stock sin confirmar. Revisá el stock en ambos canales antes de reconciliarlo.');
        }
        $delta = $state ? $remote['quantity'] - $state['remote_quantity'] : 0;
        $target = $this->products->applyMercadoLibreStockChange($variantId, $delta, $actorId, $itemId,
            function (PDO $pdo, int $quantity) use ($state, $remote, $key, $itemId, $manualChange): void {
                if (!$state && !$manualChange && $quantity !== $remote['quantity']) {
                    throw new \RuntimeException('El stock inicial difiere entre tienda y Meli. Igualá los valores antes de iniciar la sincronización.');
                }
                $this->save($key, ['item_id' => $itemId, 'remote_quantity' => $remote['quantity'],
                    'pending' => $quantity !== $remote['quantity'], 'target' => $quantity, 'version' => $remote['version']]);
            });
        if ($target !== $remote['quantity']) {
            // The pending marker survives transport errors and crashes.
            $code = $write($target, $remote['version']);
            if (!in_array($code, [200, 204], true)) {
                if ($code >= 400 && $code < 500) {
                    $this->save($key, ['item_id' => $itemId, 'remote_quantity' => $remote['quantity'], 'pending' => false]);
                }
                throw new \RuntimeException($code === 409
                    ? 'Meli cambió el stock durante la operación. Volvé a sincronizar para incorporar ese cambio.'
                    : 'Meli no confirmó el cambio de stock. Revisá el resultado antes de reintentar.');
            }
            $this->save($key, ['item_id' => $itemId, 'remote_quantity' => $target, 'pending' => false]);
        }
        return ['ok' => true, 'stock' => $target, 'local_delta' => $delta,
            'message' => 'Stock sincronizado: ' . $target . ' paquetes.'];
    }

    private function save(string $key, array $value): void
    {
        $query = $this->pdo->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=CURRENT_TIMESTAMP');
        $query->execute([$key, json_encode($value, JSON_THROW_ON_ERROR)]);
    }
}
