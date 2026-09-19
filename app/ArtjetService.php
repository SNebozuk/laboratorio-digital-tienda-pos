<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class ArtjetService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductImageService $images,
        private readonly string $root
    )
    {
    }

    /** @return list<array<string, mixed>> */
    public function adminList(): array
    {
        $rows = $this->pdo->query(
            'SELECT p.id, p.name, p.description AS ld_description, p.image_path,
                    p.active, c.name AS category_name,
                    v.id AS variant_id, v.name AS variant_name, v.sku,
                    v.image_path AS variant_image_path, v.price_cents,
                    v.stock_on_hand, v.active AS variant_active,
                    s.source_url, s.store_title, s.store_description,
                    s.artjet_category, s.artjet_subcategory,
                    s.primary_image_path, s.additional_images_json,
                    s.technical_info, s.match_status, s.sync_description,
                    s.sync_images, s.publish_store, s.last_synced_at, s.updated_at
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             JOIN product_variants v ON v.product_id = p.id
             LEFT JOIN artjet_product_sync s ON s.product_id = p.id
             WHERE p.deleted_at IS NULL AND p.active = 1
               AND (LOWER(p.name) LIKE "%artjet%" OR LOWER(p.name) LIKE "%art-jet%")
             ORDER BY CASE WHEN UPPER(p.name) LIKE "TINTA%" THEN 1 ELSE 0 END,
                      p.name, v.sort_order, v.name'
        )->fetchAll();

        $products = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (!isset($products[$id])) {
                $inferred = $this->inferMetadata((string) $row['name']);
                $storedImages = json_decode((string) ($row['additional_images_json'] ?? '[]'), true);
                $products[$id] = [
                    'product_id' => $id,
                    'ld_name' => (string) $row['name'],
                    'ld_category' => (string) ($row['category_name'] ?? 'Sin categoría'),
                    'ld_image_path' => (string) ($row['image_path'] ?? ''),
                    'source_match' => $this->commercialName((string) $row['name']),
                    'source_url' => (string) ($row['source_url'] ?: $inferred['source_url']),
                    'store_title' => (string) ($row['store_title'] ?: $this->commercialName((string) $row['name'])),
                    'store_description' => (string) ($row['store_description'] ?? ''),
                    'artjet_category' => (string) ($row['artjet_category'] ?: $inferred['category']),
                    'artjet_subcategory' => (string) ($row['artjet_subcategory'] ?: $inferred['subcategory']),
                    'primary_image_path' => (string) ($row['primary_image_path'] ?? ''),
                    'additional_images' => is_array($storedImages) ? array_values(array_filter($storedImages, 'is_string')) : [],
                    'technical_info' => (string) ($row['technical_info'] ?? ''),
                    'match_status' => (string) ($row['match_status'] ?: 'review'),
                    'sync_description' => (bool) ($row['sync_description'] ?? false),
                    'sync_images' => (bool) ($row['sync_images'] ?? false),
                    'publish_store' => (bool) ($row['publish_store'] ?? false),
                    'last_synced_at' => $row['last_synced_at'],
                    'updated_at' => $row['updated_at'],
                    'variants' => [],
                ];
            }
            $products[$id]['variants'][] = [
                'id' => (int) $row['variant_id'],
                'name' => (string) $row['variant_name'],
                'sku' => (string) $row['sku'],
                'image_path' => (string) ($row['variant_image_path'] ?? ''),
                'price_cents' => (int) $row['price_cents'],
                'stock_on_hand' => (int) $row['stock_on_hand'],
                'active' => (bool) $row['variant_active'],
            ];
        }

        return array_values($products);
    }

    /** @return list<array<string, mixed>> */
    public function publicProducts(): array
    {
        return array_values(array_filter($this->adminList(), static function (array $product): bool {
            return $product['publish_store']
                && array_filter($product['variants'], static fn (array $variant): bool => $variant['active']) !== [];
        }));
    }

    /** @return array<int, string> */
    public function imagePaths(): array
    {
        $rows = $this->pdo->query(
            'SELECT product_id, primary_image_path FROM artjet_product_sync WHERE primary_image_path <> ""'
        )->fetchAll();
        $images = [];
        foreach ($rows as $row) {
            $images[(int) $row['product_id']] = (string) $row['primary_image_path'];
        }
        $variants = $this->pdo->query('SELECT product_id, sku FROM product_variants WHERE sku <> ""')->fetchAll();
        foreach ($variants as $variant) {
            $productId = (int) $variant['product_id'];
            if (isset($images[$productId])) {
                continue;
            }
            foreach (['webp', 'jpg', 'png'] as $extension) {
                $path = '/uploads/artjet/products/' . rawurlencode((string) $variant['sku']) . '.' . $extension;
                if (is_file($this->root . '/v1' . $path)) {
                    $images[$productId] = $path;
                    break;
                }
            }
        }
        return $images;
    }

    public function setProductImage(int $productId, string $imagePath): void
    {
        $exists = $this->pdo->prepare('SELECT 1 FROM products WHERE id = :id AND deleted_at IS NULL');
        $exists->execute(['id' => $productId]);
        if ($exists->fetchColumn() === false) {
            throw new ValidationException('Producto inválido.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO artjet_product_sync(product_id, primary_image_path, updated_at)
             VALUES(:product_id, :primary_image_path, CURRENT_TIMESTAMP)
             ON CONFLICT(product_id) DO UPDATE SET
                primary_image_path = excluded.primary_image_path,
                updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'product_id' => $productId,
            'primary_image_path' => $this->localImagePath($imagePath),
        ]);
    }

    /** @return array{product_id:int,image_path:string} */
    public function importVerifiedSample(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             WHERE p.deleted_at IS NULL AND p.active = 1
               AND v.sku = :sku AND v.barcode = :barcode
             LIMIT 1'
        );
        $statement->execute(['sku' => '115A4100', 'barcode' => '721450695454']);
        $matched = $statement->fetch();
        $productId = (int) ($matched['id'] ?? 0);
        if ($productId < 1) {
            throw new ValidationException('No encontramos el producto Art-Jet de prueba con su SKU y código de barras exactos.');
        }

        $image = ['image_path' => '/uploads/artjet/papel-fotografico-adhesivo-115g-cutout.png'];
        $description = 'Papel fotográfico brillante autoadhesivo de alta resolución, con pegamento potente. No amarillea con el tiempo y resiste agua y salpicaduras (no sumergible). Ideal para stickers con calidad fotográfica, candy bar, etiquetas de producto y packaging. Recomendado para superficies 100% lisas y no porosas.';
        $technical = "Formato: A4\nGramaje: 115 g\nPresentación: 100 hojas\nTerminación: brillante autoadhesiva\nUso recomendado: stickers, etiquetas, candy bar y packaging\nResistencia: agua y salpicaduras (no sumergible)";

        $this->pdo->beginTransaction();
        try {
            $update = $this->pdo->prepare(
                'UPDATE products SET description = :description, image_path = :image_path, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $update->execute(['id' => $productId, 'description' => $description, 'image_path' => $image['image_path']]);
            $this->save([
                'product_id' => $productId,
                'source_url' => '',
                'store_title' => 'Papel Fotográfico Brillante Adhesivo 115g',
                'store_description' => $description,
                'artjet_category' => 'Papeles',
                'artjet_subcategory' => 'Fotográficos adhesivos',
                'primary_image_path' => $image['image_path'],
                'additional_images' => [],
                'technical_info' => $technical,
                'match_status' => 'confirmed',
                'sync_description' => true,
                'sync_images' => true,
                'publish_store' => true,
            ]);
            $synced = $this->pdo->prepare('UPDATE artjet_product_sync SET last_synced_at = CURRENT_TIMESTAMP WHERE product_id = :id');
            $synced->execute(['id' => $productId]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return ['product_id' => $productId, 'image_path' => $image['image_path']];
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): void
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $exists = $this->pdo->prepare(
            'SELECT 1 FROM products WHERE id = :id AND deleted_at IS NULL
             AND (LOWER(name) LIKE "%artjet%" OR LOWER(name) LIKE "%art-jet%")'
        );
        $exists->execute(['id' => $productId]);
        if ($exists->fetchColumn() === false) {
            throw new ValidationException('Seleccioná un producto Art-Jet válido de Laboratorio Digital.');
        }

        $sourceUrl = trim((string) ($data['source_url'] ?? ''));
        if ($sourceUrl !== '' && !preg_match('~^https://(?:www\.)?(?:eshop\.)?art-jet\.com\.ar(?:/|$)~i', $sourceUrl)) {
            throw new ValidationException('La URL debe pertenecer a uno de los sitios oficiales de Art-Jet.');
        }
        $status = (string) ($data['match_status'] ?? 'review');
        if (!in_array($status, ['confirmed', 'review', 'unmatched', 'unsearched'], true)) {
            throw new ValidationException('Estado de coincidencia inválido.');
        }
        $primaryImage = $this->localImagePath((string) ($data['primary_image_path'] ?? ''));
        $additional = is_array($data['additional_images'] ?? null) ? $data['additional_images'] : [];
        $additional = array_values(array_filter(array_map(fn ($path): string => $this->localImagePath((string) $path), $additional)));

        $statement = $this->pdo->prepare(
            'INSERT INTO artjet_product_sync(
                product_id, source_url, store_title, store_description,
                artjet_category, artjet_subcategory, primary_image_path,
                additional_images_json, technical_info, match_status,
                sync_description, sync_images, publish_store, updated_at
             ) VALUES(
                :product_id, :source_url, :store_title, :store_description,
                :artjet_category, :artjet_subcategory, :primary_image_path,
                :additional_images_json, :technical_info, :match_status,
                :sync_description, :sync_images, :publish_store, CURRENT_TIMESTAMP
             ) ON CONFLICT(product_id) DO UPDATE SET
                source_url = excluded.source_url,
                store_title = excluded.store_title,
                store_description = excluded.store_description,
                artjet_category = excluded.artjet_category,
                artjet_subcategory = excluded.artjet_subcategory,
                primary_image_path = excluded.primary_image_path,
                additional_images_json = excluded.additional_images_json,
                technical_info = excluded.technical_info,
                match_status = excluded.match_status,
                sync_description = excluded.sync_description,
                sync_images = excluded.sync_images,
                publish_store = excluded.publish_store,
                updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'product_id' => $productId,
            'source_url' => $sourceUrl,
            'store_title' => substr(trim((string) ($data['store_title'] ?? '')), 0, 180),
            'store_description' => trim((string) ($data['store_description'] ?? '')),
            'artjet_category' => substr(trim((string) ($data['artjet_category'] ?? '')), 0, 80),
            'artjet_subcategory' => substr(trim((string) ($data['artjet_subcategory'] ?? '')), 0, 100),
            'primary_image_path' => $primaryImage,
            'additional_images_json' => json_encode($additional, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'technical_info' => trim((string) ($data['technical_info'] ?? '')),
            'match_status' => $status,
            'sync_description' => !empty($data['sync_description']) ? 1 : 0,
            'sync_images' => !empty($data['sync_images']) ? 1 : 0,
            'publish_store' => !empty($data['publish_store']) ? 1 : 0,
        ]);
    }

    /** @return array{category:string,subcategory:string,source_url:string} */
    private function inferMetadata(string $name): array
    {
        $upper = strtoupper($name);
        if (str_starts_with($upper, 'TINTA')) {
            $subcategory = str_contains($upper, 'ETERNITY') ? 'Eternity'
                : (str_contains($upper, 'PROFESIONAL') ? 'Profesional'
                : (str_contains($upper, 'SUBLIM') ? 'Sublimación' : 'Comercial'));
            return ['category' => 'Tintas', 'subcategory' => $subcategory, 'source_url' => 'https://www.eshop.art-jet.com.ar/art-jet/tintas/'];
        }
        $subcategory = match (true) {
            str_contains($upper, 'FILMILO') => 'Filmilo',
            str_contains($upper, 'HOLOFAN') => 'Holofan',
            str_contains($upper, 'MATELINA') => 'Matelina',
            str_contains($upper, 'TATUFAN') => 'Tatufan',
            str_contains($upper, 'WINKY') => 'Winky Paper',
            str_contains($upper, 'SUBLISTICK') => 'Sublistick',
            str_contains($upper, 'DURALITE') => 'Duralite',
            str_contains($upper, 'SUBLIM') => 'Sublimación',
            str_contains($upper, 'TEXTURADO'), str_contains($upper, 'CANVAS') => 'Canvas',
            default => 'Fotográficos',
        };
        return ['category' => 'Papeles', 'subcategory' => $subcategory, 'source_url' => 'https://www.eshop.art-jet.com.ar/art-jet/papeles/'];
    }

    private function commercialName(string $name): string
    {
        return trim((string) preg_replace('/\s+ART-?JET\b/iu', '', $name));
    }

    private function localImagePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';
        if (!str_starts_with($path, '/') || str_contains($path, '..')) {
            throw new ValidationException('Las imágenes deben guardarse como rutas locales de la tienda.');
        }
        return $path;
    }
}
