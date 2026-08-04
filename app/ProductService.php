<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class ProductService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Catálogo público. El SKU y el código de barras no se exponen.
     *
     * @return list<array<string, mixed>>
     */
    public function publicCatalog(): array
    {
        $rows = $this->pdo->query(
            'SELECT
                p.id AS product_id,
                p.name AS product_name,
                p.description,
                p.image_path,
                c.name AS category_name,
                c.slug AS category_slug,
                v.id AS variant_id,
                v.name AS variant_name,
                v.price_cents,
                (v.stock_on_hand - v.stock_reserved) AS available_stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             JOIN product_variants v ON v.product_id = p.id
             WHERE p.active = 1 AND v.active = 1
             ORDER BY
                COALESCE(c.sort_order, 9999),
                COALESCE(c.name, "Sin categoría"),
                p.sort_order,
                p.name,
                v.sort_order,
                v.name'
        )->fetchAll();

        return $this->groupProducts($rows, false);
    }

    /** @return list<int> */
    public function publicCodeMatches(string $query, int $limit = 100): array
    {
        $query = trim($query);
        if ($query === '' || strlen($query) > 80) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $escaped = strtr($query, [
            '\\' => '\\\\',
            '%' => '\\%',
            '_' => '\\_',
        ]);
        $statement = $this->pdo->prepare(
            'SELECT
                p.id,
                MIN(
                    CASE
                        WHEN v.barcode = :exact COLLATE NOCASE THEN 0
                        WHEN v.sku = :exact COLLATE NOCASE THEN 1
                        WHEN COALESCE(v.barcode, "") LIKE :contains ESCAPE "\\" THEN 2
                        ELSE 3
                    END
                ) AS relevance
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             WHERE p.active = 1
               AND v.active = 1
               AND (
                    v.sku LIKE :contains ESCAPE "\\"
                    OR COALESCE(v.barcode, "") LIKE :contains ESCAPE "\\"
               )
             GROUP BY p.id
             ORDER BY relevance, p.name
             LIMIT :limit'
        );
        $statement->bindValue(':exact', $query);
        $statement->bindValue(':contains', '%' . $escaped . '%');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $statement->fetchAll()
        );
    }

    /** @return list<array<string, mixed>> */
    public function adminCatalog(): array
    {
        $rows = $this->pdo->query(
            'SELECT
                p.id AS product_id,
                p.name AS product_name,
                p.description,
                p.image_path,
                p.active AS product_active,
                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                v.id AS variant_id,
                v.name AS variant_name,
                v.sku,
                v.barcode,
                v.price_cents,
                v.stock_on_hand,
                v.stock_reserved,
                (v.stock_on_hand - v.stock_reserved) AS available_stock,
                v.min_stock,
                v.active AS variant_active
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             JOIN product_variants v ON v.product_id = p.id
             ORDER BY p.active DESC, p.name, v.active DESC, v.sort_order, v.name'
        )->fetchAll();

        return $this->groupProducts($rows, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $actorUserId): int
    {
        $payload = $this->validateProduct($data);

        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($payload, $actorUserId): int {
                $categoryId = $this->categoryId($pdo, $payload['category']);
                $insertProduct = $pdo->prepare(
                    'INSERT INTO products(category_id, name, description, image_path)
                     VALUES(:category_id, :name, :description, :image_path)'
                );
                $insertProduct->execute([
                    'category_id' => $categoryId,
                    'name' => $payload['name'],
                    'description' => $payload['description'],
                    'image_path' => $payload['image_path'],
                ]);

                $productId = (int) $pdo->lastInsertId();
                foreach ($payload['variants'] as $sort => $variant) {
                    $variantId = $this->insertVariant($pdo, $productId, $variant, $sort);
                    if ($variant['stock_on_hand'] > 0) {
                        $this->recordStockMovement(
                            $pdo,
                            $variantId,
                            $variant['stock_on_hand'],
                            0,
                            'initial_stock',
                            'Alta de producto',
                            $actorUserId
                        );
                    }
                }

                return $productId;
            }
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $productId, array $data, int $actorUserId): void
    {
        $payload = $this->validateProduct($data);

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($productId, $payload, $actorUserId): void {
                $categoryId = $this->categoryId($pdo, $payload['category']);
                $updateProduct = $pdo->prepare(
                    'UPDATE products
                     SET category_id = :category_id,
                         name = :name,
                         description = :description,
                         image_path = :image_path,
                         active = :active,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $updateProduct->execute([
                    'category_id' => $categoryId,
                    'name' => $payload['name'],
                    'description' => $payload['description'],
                    'image_path' => $payload['image_path'],
                    'active' => $payload['active'] ? 1 : 0,
                    'id' => $productId,
                ]);
                if ($updateProduct->rowCount() !== 1) {
                    throw new ValidationException('El producto no existe.');
                }

                $existingQuery = $pdo->prepare(
                    'SELECT id, stock_on_hand, stock_reserved
                     FROM product_variants
                     WHERE product_id = :product_id'
                );
                $existingQuery->execute(['product_id' => $productId]);
                $existing = [];
                foreach ($existingQuery->fetchAll() as $row) {
                    $existing[(int) $row['id']] = $row;
                }

                $keptIds = [];
                foreach ($payload['variants'] as $sort => $variant) {
                    $variantId = (int) ($variant['id'] ?? 0);
                    if ($variantId < 1) {
                        $variantId = $this->insertVariant(
                            $pdo,
                            $productId,
                            $variant,
                            $sort
                        );
                        if ($variant['stock_on_hand'] > 0) {
                            $this->recordStockMovement(
                                $pdo,
                                $variantId,
                                $variant['stock_on_hand'],
                                0,
                                'initial_stock',
                                'Nueva variante',
                                $actorUserId
                            );
                        }
                        $keptIds[] = $variantId;
                        continue;
                    }

                    if (!isset($existing[$variantId])) {
                        throw new ValidationException('Una variante no pertenece al producto.');
                    }
                    if ($variant['stock_on_hand'] < (int) $existing[$variantId]['stock_reserved']) {
                        throw new ConflictException(
                            'El stock no puede ser menor que las unidades reservadas.'
                        );
                    }

                    $stockDelta = $variant['stock_on_hand']
                        - (int) $existing[$variantId]['stock_on_hand'];
                    $updateVariant = $pdo->prepare(
                        'UPDATE product_variants
                         SET name = :name,
                             sku = :sku,
                             barcode = :barcode,
                             price_cents = :price_cents,
                             stock_on_hand = :stock_on_hand,
                             min_stock = :min_stock,
                             sort_order = :sort_order,
                             active = :active,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id AND product_id = :product_id'
                    );
                    $updateVariant->execute([
                        'name' => $variant['name'],
                        'sku' => $variant['sku'],
                        'barcode' => $variant['barcode'],
                        'price_cents' => $variant['price_cents'],
                        'stock_on_hand' => $variant['stock_on_hand'],
                        'min_stock' => $variant['min_stock'],
                        'sort_order' => $sort,
                        'active' => $variant['active'] ? 1 : 0,
                        'id' => $variantId,
                        'product_id' => $productId,
                    ]);

                    if ($stockDelta !== 0) {
                        $this->recordStockMovement(
                            $pdo,
                            $variantId,
                            $stockDelta,
                            0,
                            'manual_adjustment',
                            'Edición de producto',
                            $actorUserId
                        );
                    }
                    $keptIds[] = $variantId;
                }

                foreach ($existing as $variantId => $row) {
                    if (in_array($variantId, $keptIds, true)) {
                        continue;
                    }
                    if ((int) $row['stock_on_hand'] > 0 || (int) $row['stock_reserved'] > 0) {
                        throw new ConflictException(
                            'No se puede quitar una variante con stock. Dejala inactiva o llevá su stock a cero.'
                        );
                    }

                    $disable = $pdo->prepare(
                        'UPDATE product_variants
                         SET active = 0, updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id'
                    );
                    $disable->execute(['id' => $variantId]);
                }
            }
        );
    }

    /**
     * Edición directa desde la lista de productos.
     *
     * @param array<string, mixed> $changes
     */
    public function quickUpdateVariant(
        int $variantId,
        array $changes,
        int $actorUserId
    ): void {
        $priceCents = filter_var(
            $changes['price_cents'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );
        $stockOnHand = filter_var(
            $changes['stock_on_hand'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($priceCents === false || $stockOnHand === false) {
            throw new ValidationException('Precio y stock deben ser números enteros positivos.');
        }

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $variantId,
                $priceCents,
                $stockOnHand,
                $actorUserId
            ): void {
                $query = $pdo->prepare(
                    'SELECT stock_on_hand, stock_reserved
                     FROM product_variants
                     WHERE id = :id'
                );
                $query->execute(['id' => $variantId]);
                $current = $query->fetch();
                if (!$current) {
                    throw new ValidationException('La variante no existe.');
                }
                if ($stockOnHand < (int) $current['stock_reserved']) {
                    throw new ConflictException(
                        'No se puede bajar el stock por debajo de las unidades reservadas.'
                    );
                }

                $update = $pdo->prepare(
                    'UPDATE product_variants
                     SET price_cents = :price_cents,
                         stock_on_hand = :stock_on_hand,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute([
                    'price_cents' => $priceCents,
                    'stock_on_hand' => $stockOnHand,
                    'id' => $variantId,
                ]);

                $stockDelta = $stockOnHand - (int) $current['stock_on_hand'];
                if ($stockDelta !== 0) {
                    $this->recordStockMovement(
                        $pdo,
                        $variantId,
                        $stockDelta,
                        0,
                        'manual_adjustment',
                        'Edición rápida',
                        $actorUserId
                    );
                }
            }
        );
    }

    public function duplicate(int $productId, int $actorUserId): int
    {
        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($productId, $actorUserId): int {
                $productQuery = $pdo->prepare('SELECT * FROM products WHERE id = :id');
                $productQuery->execute(['id' => $productId]);
                $product = $productQuery->fetch();
                if (!$product) {
                    throw new ValidationException('El producto no existe.');
                }

                $insertProduct = $pdo->prepare(
                    'INSERT INTO products(
                        category_id, name, description, image_path, active, sort_order
                     ) VALUES(
                        :category_id, :name, :description, :image_path, 0, :sort_order
                     )'
                );
                $insertProduct->execute([
                    'category_id' => $product['category_id'],
                    'name' => $product['name'] . ' (COPIA)',
                    'description' => $product['description'],
                    'image_path' => $product['image_path'],
                    'sort_order' => $product['sort_order'],
                ]);
                $newProductId = (int) $pdo->lastInsertId();

                $variants = $pdo->prepare(
                    'SELECT * FROM product_variants
                     WHERE product_id = :product_id
                     ORDER BY sort_order, id'
                );
                $variants->execute(['product_id' => $productId]);
                $suffix = '-COPIA-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

                foreach ($variants->fetchAll() as $sort => $variant) {
                    $newSku = substr((string) $variant['sku'], 0, 48) . $suffix;
                    $newVariantId = $this->insertVariant(
                        $pdo,
                        $newProductId,
                        [
                            'name' => $variant['name'],
                            'sku' => $newSku,
                            'barcode' => null,
                            'price_cents' => (int) $variant['price_cents'],
                            'stock_on_hand' => 0,
                            'min_stock' => (int) $variant['min_stock'],
                            'active' => false,
                        ],
                        $sort
                    );
                    unset($newVariantId);
                }

                $this->recordOrderlessAudit(
                    $pdo,
                    $actorUserId,
                    'duplicate_product',
                    'Producto ' . $productId . ' duplicado como ' . $newProductId
                );

                return $newProductId;
            }
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function groupProducts(array $rows, bool $admin): array
    {
        $products = [];
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $productId,
                    'name' => $row['product_name'],
                    'description' => $row['description'],
                    'image_path' => $row['image_path'],
                    'category' => [
                        'id' => isset($row['category_id']) ? (int) $row['category_id'] : null,
                        'name' => $row['category_name'] ?? 'Sin categoría',
                        'slug' => $row['category_slug'] ?? 'sin-categoria',
                    ],
                    'active' => $admin ? (bool) $row['product_active'] : true,
                    'variants' => [],
                ];
            }

            $variant = [
                'id' => (int) $row['variant_id'],
                'name' => $row['variant_name'],
                'price_cents' => (int) $row['price_cents'],
                'available_stock' => (int) $row['available_stock'],
            ];
            if ($admin) {
                $variant += [
                    'sku' => $row['sku'],
                    'barcode' => $row['barcode'],
                    'stock_on_hand' => (int) $row['stock_on_hand'],
                    'stock_reserved' => (int) $row['stock_reserved'],
                    'min_stock' => (int) $row['min_stock'],
                    'active' => (bool) $row['variant_active'],
                ];
            }
            $products[$productId]['variants'][] = $variant;
        }

        return array_values($products);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validateProduct(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'General'));
        $variants = $data['variants'] ?? [];

        if ($name === '') {
            throw new ValidationException('Ingresá el nombre del producto.');
        }
        if (!is_array($variants) || count($variants) < 1) {
            throw new ValidationException('El producto debe tener al menos una variante.');
        }
        if (count($variants) > 100) {
            throw new ValidationException('Un producto no puede superar 100 variantes.');
        }

        $validatedVariants = [];
        $seenSkus = [];
        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                throw new ValidationException('Hay una variante inválida.');
            }
            $variantName = trim((string) ($variant['name'] ?? ''));
            $sku = strtoupper(trim((string) ($variant['sku'] ?? '')));
            $price = filter_var(
                $variant['price_cents'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );
            $stock = filter_var(
                $variant['stock_on_hand'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );
            $minimum = filter_var(
                $variant['min_stock'] ?? 0,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );

            if ($variantName === '' || $sku === '') {
                throw new ValidationException('Cada variante necesita nombre y SKU.');
            }
            if ($price === false || $stock === false || $minimum === false) {
                throw new ValidationException('Precio y stock deben ser números enteros positivos.');
            }
            if (isset($seenSkus[$sku])) {
                throw new ValidationException('No se puede repetir el SKU dentro del producto.');
            }
            $seenSkus[$sku] = true;

            $validatedVariants[] = [
                'id' => isset($variant['id']) ? (int) $variant['id'] : null,
                'name' => $variantName,
                'sku' => $sku,
                'barcode' => trim((string) ($variant['barcode'] ?? '')) ?: null,
                'price_cents' => $price,
                'stock_on_hand' => $stock,
                'min_stock' => $minimum,
                'active' => !isset($variant['active']) || (bool) $variant['active'],
            ];
        }

        $imagePath = trim((string) ($data['image_path'] ?? ''));
        if (
            $imagePath !== ''
            && !str_starts_with($imagePath, '/')
            && !preg_match('#^https://#i', $imagePath)
        ) {
            throw new ValidationException(
                'La imagen debe usar HTTPS o una ruta local.'
            );
        }

        return [
            'name' => function_exists('mb_strtoupper')
                ? mb_strtoupper($name, 'UTF-8')
                : strtoupper($name),
            'category' => $category !== '' ? $category : 'General',
            'description' => trim((string) ($data['description'] ?? '')),
            'image_path' => $imagePath ?: null,
            'active' => !isset($data['active']) || (bool) $data['active'],
            'variants' => $validatedVariants,
        ];
    }

    private function categoryId(PDO $pdo, string $categoryName): int
    {
        $slug = $this->slug($categoryName);
        $insert = $pdo->prepare(
            'INSERT OR IGNORE INTO categories(name, slug) VALUES(:name, :slug)'
        );
        $insert->execute(['name' => $categoryName, 'slug' => $slug]);

        $query = $pdo->prepare(
            'SELECT id FROM categories WHERE name = :name COLLATE NOCASE'
        );
        $query->execute(['name' => $categoryName]);

        return (int) $query->fetchColumn();
    }

    /** @param array<string, mixed> $variant */
    private function insertVariant(
        PDO $pdo,
        int $productId,
        array $variant,
        int $sortOrder
    ): int {
        $insert = $pdo->prepare(
            'INSERT INTO product_variants(
                product_id, name, sku, barcode, price_cents,
                stock_on_hand, min_stock, sort_order, active
             ) VALUES(
                :product_id, :name, :sku, :barcode, :price_cents,
                :stock_on_hand, :min_stock, :sort_order, :active
             )'
        );
        $insert->execute([
            'product_id' => $productId,
            'name' => $variant['name'],
            'sku' => $variant['sku'],
            'barcode' => $variant['barcode'],
            'price_cents' => $variant['price_cents'],
            'stock_on_hand' => $variant['stock_on_hand'],
            'min_stock' => $variant['min_stock'],
            'sort_order' => $sortOrder,
            'active' => $variant['active'] ? 1 : 0,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function recordStockMovement(
        PDO $pdo,
        int $variantId,
        int $onHandDelta,
        int $reservedDelta,
        string $reason,
        string $reference,
        int $actorUserId
    ): void {
        $insert = $pdo->prepare(
            'INSERT INTO stock_movements(
                variant_id, actor_user_id, on_hand_delta,
                reserved_delta, reason, reference
             ) VALUES(
                :variant_id, :actor_user_id, :on_hand_delta,
                :reserved_delta, :reason, :reference
             )'
        );
        $insert->execute([
            'variant_id' => $variantId,
            'actor_user_id' => $actorUserId,
            'on_hand_delta' => $onHandDelta,
            'reserved_delta' => $reservedDelta,
            'reason' => $reason,
            'reference' => $reference,
        ]);
    }

    private function recordOrderlessAudit(
        PDO $pdo,
        int $actorUserId,
        string $event,
        string $detail
    ): void {
        unset($pdo, $actorUserId, $event, $detail);
        // Los cambios de producto quedan auditados por stock_movements.
        // Esta función reserva un punto de extensión para una bitácora general.
    }

    private function slug(string $value): string
    {
        $value = function_exists('mb_strtolower')
            ? mb_strtolower(trim($value), 'UTF-8')
            : strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-') ?: 'general';
    }
}
