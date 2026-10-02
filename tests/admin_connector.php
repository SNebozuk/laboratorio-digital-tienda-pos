<?php
declare(strict_types=1);
namespace LaboratorioDigital;
require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/Http.php';
require dirname(__DIR__) . '/app/AdminConnector.php';

function check(bool $ok, string $message): void { if (!$ok) throw new \RuntimeException($message); }
function rejects(callable $call): void {
    try { $call(); } catch (\RuntimeException|\InvalidArgumentException $error) { return; }
    throw new \RuntimeException('Se aceptó una escritura inválida.');
}
$pdo = new \PDO('sqlite::memory:', null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
$pdo->exec("CREATE TABLE users(id INTEGER,role TEXT,active INTEGER); INSERT INTO users VALUES(7,'admin',1); CREATE TABLE settings(key TEXT PRIMARY KEY,value TEXT,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
foreach ([1, 2] as $id) MercadoLibreProductDraft::save($pdo, $id, ['family_name' => 'Remera negra', 'attributes' => ['BRAND' => 'Generic', 'COLOR' => 'Negro'], 'pictures' => ['https://example.org/one.jpg', 'https://example.org/two.jpg']]);
$products = new class($pdo) {
    public function __construct(private \PDO $pdo) {}
    public function adminCatalog(): array {
        $rows = [];
        foreach ([1, 2] as $id) {
            $q = $this->pdo->prepare('SELECT value FROM settings WHERE key=?'); $q->execute(['meli_product_' . $id]);
            $rows[] = ['id' => $id, 'name' => 'Remera negra algodón ' . $id, 'active' => $id === 1, 'category' => ['id' => 1],
                'meli' => json_decode($q->fetchColumn(), true), 'meli_publication' => ['state' => 'idle'],
                'variants' => [['id' => $id, 'name' => 'Talle 4', 'active' => true, 'sku' => '', 'barcode' => '', 'price_cents' => 550000, 'stock_on_hand' => 7]]];
        }
        return $rows;
    }
};
$connector = new AdminConnector(['pdo' => $pdo, 'products' => $products, 'config' => ['storage_path' => sys_get_temp_dir(), 'meli_client_secret' => 'NEVER-RETURN-THIS']]);
$get = fn () => $connector->call('ld_products_get', ['product_ids' => [1, 2]])['products'];
$patch = fn ($id, $rev, $data) => ['product_id' => $id, 'expected_revision' => $rev, 'patch' => $data];
$save = fn ($entries) => $connector->call('ld_meli_drafts_save', ['entries' => $entries]);
$original = $get();
check(!str_contains(json_encode($original), 'NEVER-RETURN-THIS'), 'Se filtró configuración.');
check($connector->call('ld_products_search', ['query' => 'negra remera', 'active' => true])['total'] === 1, 'Búsqueda o filtro incorrecto.');
$save([$patch(1, $original[0]['draft_revision'], ['attributes' => ['MODEL' => 'Infantil'], 'pictures' => ['https://example.org/new.jpg']])]);
$current = $get();
check($current[0]['meli']['attributes']['BRAND'] === 'Generic' && $current[0]['meli']['attributes']['MODEL'] === 'Infantil', 'No preservó atributos.');
check(count($current[0]['meli']['pictures']) === 1, 'No reemplazó la lista de fotos.');
check($current[0]['variants'] === $original[0]['variants'], 'Se alteraron precios o stock.');
$auditCount = (int) $pdo->query("SELECT count(*) FROM settings WHERE key LIKE 'admin_connector_audit_%'")->fetchColumn();
rejects(fn () => $save([$patch(1, $current[0]['draft_revision'], ['description' => 'NO GUARDAR']), $patch(2, str_repeat('0',64), ['description' => 'Conflicto'])]));
check($get() === $current, 'El lote con conflicto no se revirtió.');
check((int) $pdo->query("SELECT count(*) FROM settings WHERE key LIKE 'admin_connector_audit_%'")->fetchColumn() === $auditCount, 'Auditoría del lote revertido.');
rejects(fn () => $save([$patch(1, $current[0]['draft_revision'], ['variant_id' => 2])]));
rejects(fn () => $save([$patch(1, $current[0]['draft_revision'], ['size_grid_rows' => [2 => '123:1']])]));
rejects(fn () => $save([$patch(1, $current[0]['draft_revision'], ['pictures' => ['http://example.org/photo.jpg']])]));
rejects(fn () => $save([$patch(1, $current[0]['draft_revision'], ['description' => 'Primero']), $patch(1, $current[0]['draft_revision'], ['description' => 'Duplicado'])]));
check($get() === $current, 'Una escritura rechazada alteró las fichas.');
rejects(fn () => $connector->call('arbitrary_sql', []));
check(!str_contains($connector->safeError(new \PDOException('SQL SECRET')), 'SECRET'), 'Error expuso SQL.');
$audit = json_decode($pdo->query("SELECT value FROM settings WHERE key LIKE 'admin_connector_audit_%' LIMIT 1")->fetchColumn(), true);
check($audit['actor_id'] === 7 && $audit['source'] === 'codex_ssh', 'Falta auditoría.');
$pdo->exec('UPDATE users SET active=0');
rejects(fn () => $get());
echo "Admin connector: OK\n";
