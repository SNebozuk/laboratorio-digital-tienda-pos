<?php
declare(strict_types=1);
namespace LaboratorioDigital;

use PDO;
require_once __DIR__ . '/MercadoLibreService.php';

/** Business operations for the SSH-only Codex connector. Never returns configuration or tokens. */
final class AdminConnector
{
    private readonly MercadoLibreService $meli;
    private ?array $catalog = null;
    public function __construct(private readonly array $app)
    {
        $this->meli = new MercadoLibreService($app['pdo'], $app['config']);
    }

    public function call(string $tool, array $args): array
    {
        $this->catalog = null;
        $actor = $this->app['pdo']->query("SELECT id FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1")->fetchColumn();
        if ($actor === false) throw new \RuntimeException('No hay un administrador activo.');
        $local = ['ld_products_search', 'ld_products_get', 'ld_catalog_context', 'ld_meli_drafts_save'];
        $lock = null;
        if (!in_array($tool, $local, true)) {
            $lock = fopen($this->app['config']['storage_path'] . '/meli-oauth.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                if ($lock) fclose($lock);
                throw new \RuntimeException('MeLi está ocupado. Reintentá cuando termine la operación en curso.');
            }
        }
        try {
            $result = match ($tool) {
                'ld_products_search' => $this->search($args),
                'ld_products_get' => ['products' => array_map(fn ($id) => $this->product($id), $this->ids($args))],
                'ld_catalog_context' => ['categories' => $this->app['categories']->tree(),
                    'size_guide' => $this->app['settings']->sizeGuide(), 'meli_defaults' => $this->meli->defaults()],
                'ld_meli_drafts_save' => $this->saveDrafts($args, (int) $actor),
                'ld_meli_status' => $this->meli->status(),
                'ld_meli_categories' => $this->meli->discoverCategories((string) ($args['query'] ?? '')),
                'ld_meli_requirements' => $this->meli->productRequirements((string) ($args['category_id'] ?? '')),
                'ld_meli_price' => $this->price($args, (int) $actor),
                'ld_meli_validate' => $this->publications($args, false, (int) $actor),
                'ld_meli_publish' => $this->publications($args, true, (int) $actor),
                'ld_products_visibility' => $this->visibility($args, (int) $actor),
                'ld_meli_stock_sync' => $this->meli->synchronizeListingStock((string) ($args['item_id'] ?? ''), (int) $actor),
                default => throw new \InvalidArgumentException('Herramienta no permitida.'),
            };
            return $result;
        } finally { if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); } }
    }

    private function ids(array $args, int $max = 50): array
    {
        $ids = $args['product_ids'] ?? [];
        if (!is_array($ids) || !$ids || count($ids) > $max) throw new \InvalidArgumentException('Indicá entre 1 y ' . $max . ' productos.');
        foreach ($ids as $id) if (!is_int($id) || $id < 1) throw new \InvalidArgumentException('ID de producto inválido.');
        return array_values(array_unique($ids));
    }

    private function search(array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        $limit = max(1, min(100, (int) ($args['limit'] ?? 30)));
        $offset = max(0, (int) ($args['offset'] ?? 0));
        $rows = array_values(array_filter($this->catalog(), static function ($p) use ($query, $args): bool {
            if (isset($args['active']) && $p['active'] !== $args['active']) return false;
            if ($query === '') return true;
            $text = $p['name'] . ' ' . implode(' ', array_map(static fn ($v) => $v['name'] . ' ' . $v['sku'] . ' ' . $v['barcode'], $p['variants']));
            foreach (preg_split('/\s+/u', $query) as $word) if (mb_stripos($text, $word) === false) return false;
            return true;
        }));
        return ['total' => count($rows), 'offset' => $offset, 'products' => array_map(static fn ($p) => [
            'id' => $p['id'], 'name' => $p['name'], 'active' => $p['active'], 'category' => $p['category'],
            'has_meli_draft' => $p['meli'] !== null, 'publication_state' => $p['meli_publication']['state'],
            'variants' => array_map(static fn ($v) => array_intersect_key($v, array_flip(['id', 'name', 'active', 'price_cents', 'stock_on_hand'])), $p['variants']),
        ], array_slice($rows, $offset, $limit))];
    }

    private function product(int $id): array
    {
        $p = $this->catalog()[$id] ?? null;
        if (!$p) throw new \RuntimeException('El producto ' . $id . ' no existe.');
        $p['draft_revision'] = $this->revision($id);
        return $p;
    }

    private function catalog(): array
    {
        return $this->catalog ??= array_column($this->app['products']->adminCatalog(), null, 'id');
    }

    private function revision(int $id): string
    {
        $q = $this->app['pdo']->prepare('SELECT value FROM settings WHERE key=?');
        $q->execute(['meli_product_' . $id]);
        return hash('sha256', (string) $q->fetchColumn());
    }

    private function saveDrafts(array $args, int $actor): array
    {
        $entries = $args['entries'] ?? [];
        if (!is_array($entries) || !$entries || count($entries) > 50) throw new \InvalidArgumentException('El lote debe contener entre 1 y 50 fichas.');
        $changes = []; $seen = [];
        return Database::immediate($this->app['pdo'], function (PDO $pdo) use ($entries, $actor, &$changes, &$seen): array {
            foreach ($entries as $entry) {
                $id = $entry['product_id'] ?? 0;
                if (!is_int($id) || $id < 1 || isset($seen[$id])) throw new \InvalidArgumentException('ID inválido o repetido en el lote.');
                $seen[$id] = true;
                $product = $this->product($id);
                $before = $this->revision($id);
                if (!is_string($entry['expected_revision'] ?? null) || !hash_equals($before, $entry['expected_revision'])) throw new \RuntimeException('La ficha ' . $id . ' cambió. Volvé a consultarla antes de editar.');
                $patch = $entry['patch'] ?? null;
                if (!is_array($patch) || !$patch) throw new \InvalidArgumentException('Falta el contenido de la ficha.');
                $draft = $product['meli'] ?? [];
                foreach ($patch as $key => $value) {
                    if (in_array($key, ['attributes', 'sale_terms', 'required_attributes', 'size_grid_rows', 'size_equivalences'], true)
                        && is_array($value)) $draft[$key] = array_replace($draft[$key] ?? [], $value);
                    else $draft[$key] = $value;
                }
                $draft = MercadoLibreProductDraft::normalize($draft);
                $variantIds = array_column($product['variants'], 'id');
                if ($draft['variant_id'] && !in_array($draft['variant_id'], $variantIds, true)) throw new \RuntimeException('La variante seleccionada pertenece a otro producto.');
                foreach (array_keys($draft['size_grid_rows'] + $draft['size_equivalences']) as $variantId) {
                    if (!in_array($variantId, $variantIds, true)) throw new \RuntimeException('La guía contiene una variante de otro producto.');
                }
                MercadoLibreProductDraft::save($pdo, $id, $draft);
                $changes[] = ['product_id' => $id, 'before' => $before, 'draft_revision' => $this->revision($id)];
            }
            $this->audit('drafts_save', $actor, $changes);
            return ['ok' => true, 'products' => $changes];
        });
    }

    private function price(array $args, int $actor): array
    {
        $id = $args['product_id'] ?? 0;
        if (!is_int($id) || $id < 1) throw new \InvalidArgumentException('ID inválido.');
        $p = $this->product($id); $draft = $p['meli'];
        if (!$draft) throw new \RuntimeException('Completá primero la ficha.');
        if (($args['expected_revision'] ?? '') !== $p['draft_revision']) throw new \RuntimeException('La ficha cambió. Volvé a consultarla.');
        $variant = array_values(array_filter($p['variants'], static fn ($v) => $v['id'] === $draft['variant_id']))[0] ?? null;
        if (!$variant) throw new \RuntimeException('Elegí la variante de referencia.');
        $draft['pricing']['base_price_cents'] = $variant['price_cents'];
        $result = $this->meli->calculateProductPrice($draft);
        $saved = $this->saveDrafts(['entries' => [['product_id' => $id, 'expected_revision' => $p['draft_revision'], 'patch' => ['pricing' => $result['pricing']]]]], $actor);
        return $result + ['saved' => $saved];
    }

    private function publications(array $args, bool $publish, int $actor): array
    {
        $results = [];
        foreach ($this->ids($args, $publish ? 5 : 10) as $id) {
            $out = ['product_id' => $id, 'variants' => []];
            try {
                $variants = $this->meli->publicationVariants($id)['variants'];
                foreach ($variants as $variant) {
                    $validation = $this->meli->validatePublication($id, (int) $variant['id']);
                    $out['variants'][] = ['variant_id' => $variant['id'], 'validation' => $validation];
                    if (!$validation['valid']) throw new \RuntimeException('MeLi rechazó el talle ' . $variant['name'] . '.');
                }
                if ($publish) {
                    foreach ($variants as $variant) $out['published'][] = $this->meli->publishProduct($id, (int) $variant['id']);
                    $this->meli->finishPublication($id);
                }
                $out['ok'] = true;
            } catch (\Throwable $error) {
                $out['ok'] = false; $out['error'] = $this->safeError($error);
                if ($publish) $this->meli->publicationError($id, $out['error']);
            }
            $results[] = $out;
            if ($publish) $this->audit('publish', $actor, [$out]);
        }
        return ['ok' => !in_array(false, array_column($results, 'ok'), true), 'products' => $results];
    }

    private function visibility(array $args, int $actor): array
    {
        if (!is_bool($args['active'] ?? null)) throw new \InvalidArgumentException('Visibilidad inválida.');
        $ids = $this->ids($args);
        $result = $this->meli->setProductsVisibility($ids, $args['active']);
        $this->audit('visibility', $actor, ['product_ids' => $ids, 'active' => $args['active']]);
        return $result;
    }

    private function audit(string $operation, int $actor, array $changes): void
    {
        $this->app['pdo']->prepare('INSERT INTO settings(key,value) VALUES(?,?)')->execute([
            'admin_connector_audit_' . bin2hex(random_bytes(12)),
            json_encode(['source' => 'codex_ssh', 'operation' => $operation, 'actor_id' => $actor, 'at' => gmdate('c'), 'changes' => $changes], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function safeError(\Throwable $error): string
    {
        return ($error instanceof \RuntimeException || $error instanceof \InvalidArgumentException) && !$error instanceof \PDOException
            ? $error->getMessage() : 'No se confirmó la operación. Consultá el estado antes de reintentar.';
    }
}
