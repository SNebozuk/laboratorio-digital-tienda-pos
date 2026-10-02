<?php
declare(strict_types=1);
namespace LaboratorioDigital;
require dirname(__DIR__) . '/app/Http.php';
require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/ProductService.php';
require dirname(__DIR__) . '/app/MercadoLibreStockSync.php';
function check(bool $condition, string $message): void { if (!$condition) throw new \RuntimeException($message); }
function rejects(callable $fn): void { try { $fn(); } catch (\RuntimeException) { return; } throw new \RuntimeException('Expected failure'); }
$db = new \PDO('sqlite::memory:', null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
$db->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
$db->exec("INSERT INTO users(id,name,email,password_hash,role) VALUES(0,'Test','test@example.test','fake','admin')");
$db->exec("INSERT INTO products(id,name) VALUES(1,'Papel'); INSERT INTO product_variants(id,product_id,name,sku,price_cents,stock_on_hand,stock_specified) VALUES(1,1,'Unit','TEST',100000,60,1)");
$sync = new MercadoLibreStockSync($db, new ProductService($db));
$remote = ['quantity' => 80, 'version' => '7']; $writes = [];
$read = static function () use (&$remote): array { return $remote; };
$write = static function ($quantity, $version) use (&$writes, &$remote): int {
    check($version === $remote['version'], 'Writes use confirmed remote version');
    $writes[] = $quantity; $remote = ['quantity' => $quantity, 'version' => (string) ((int) $version + 1)]; return 200;
};
rejects(fn () => $sync->synchronize(1, 'MLA123', 0, $read, $write));
$result = $sync->synchronize(1, 'MLA123', 0, $read, $write, true);
check($result['stock'] === 60 && $writes === [60], 'Manual edit starts synchronization without overwriting local 60 with old remote 80');
$db->exec('UPDATE product_variants SET stock_on_hand=50 WHERE id=1');
$remote['quantity'] = 58;
$result = $sync->synchronize(1, 'MLA123', 0, $read, $write, true);
check($result['stock'] === 48 && $remote['quantity'] === 48, 'Remote sales are incorporated during subsequent manual edits');
$db->exec('UPDATE product_variants SET stock_on_hand=45 WHERE id=1');
rejects(fn () => $sync->synchronize(1, 'MLA123', 0, $read, static fn () => 409, true));
$result = $sync->synchronize(1, 'MLA123', 0, $read, $write, true);
check($result['stock'] === 45, 'Version conflicts can be reconciled without double discounts');
$db->exec('UPDATE product_variants SET stock_on_hand=40 WHERE id=1');
rejects(fn () => $sync->synchronize(1, 'MLA123', 0, $read, static fn () => 0, true));
$count = count($writes);
rejects(fn () => $sync->synchronize(1, 'MLA123', 0, $read, $write, true));
check(count($writes) === $count, 'Uncertain writes cannot be repeated');
echo "MeLi manual stock tests passed\n";
