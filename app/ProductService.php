<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;
use PDOException;

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
                v.image_path AS variant_image_path,
                v.price_cents,
                v.price_specified,
                v.stock_on_hand AS available_stock,
                v.stock_specified
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             JOIN product_variants v ON v.product_id = p.id
             WHERE p.active = 1 AND p.deleted_at IS NULL AND v.active = 1
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
             WHERE p.active = 1 AND p.deleted_at IS NULL
               AND v.active = 1
               AND substr(v.sku, 1, 8) <> \'__AUTO__\'
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
                v.image_path AS variant_image_path,
                v.sku,
                v.barcode,
                v.price_cents,
                v.price_specified,
                v.stock_on_hand,
                v.stock_specified,
                v.stock_reserved,
                v.stock_on_hand AS available_stock,
                v.min_stock,
                v.active AS variant_active
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             JOIN product_variants v ON v.product_id = p.id
             WHERE p.deleted_at IS NULL
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
        $this->assertBarcodesAvailable($payload);

        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($payload, $actorUserId): int {
                $categoryId = $this->categoryId($pdo, $payload['category'], $payload['category_id']);
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
        $this->assertBarcodesAvailable($payload, $productId);

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($productId, $payload, $actorUserId): void {
                $categoryId = $this->categoryId($pdo, $payload['category'], $payload['category_id']);
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
                    if ($variant['reset_stock_reservations']) {
                        $this->releasePriorReservationsForStockReset($pdo, $variantId);
                    }

                    $stockDelta = $variant['stock_on_hand']
                        - (int) $existing[$variantId]['stock_on_hand'];
                    $updateVariant = $pdo->prepare(
                        'UPDATE product_variants
                         SET name = :name,
                             sku = :sku,
                             barcode = :barcode,
                             image_path = :image_path,
                             price_cents = :price_cents,
                             price_specified = :price_specified,
                             stock_on_hand = :stock_on_hand,
                             stock_specified = :stock_specified,
                             stock_reserved = CASE WHEN :reset_stock_reservations = 1 THEN 0 ELSE stock_reserved END,
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
                        'image_path' => $variant['image_path'],
                        'price_cents' => $variant['price_cents'],
                        'price_specified' => $variant['price_specified'] ? 1 : 0,
                        'stock_on_hand' => $variant['stock_on_hand'],
                        'stock_specified' => $variant['stock_specified'] ? 1 : 0,
                        'reset_stock_reservations' => $variant['reset_stock_reservations'] ? 1 : 0,
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
        $priceSpecified = ($changes['price_cents'] ?? null) !== null && ($changes['price_cents'] ?? '') !== '';
        $stockSpecified = ($changes['stock_on_hand'] ?? null) !== null && ($changes['stock_on_hand'] ?? '') !== '';
        $priceCents = filter_var(
            $priceSpecified ? $changes['price_cents'] : 0,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );
        $stockOnHand = filter_var(
            $stockSpecified ? $changes['stock_on_hand'] : 0,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($priceCents === false || $stockOnHand === false) {
            throw new ValidationException('Precio y stock deben ser números enteros positivos.');
        }
        $resetReservations = (bool) ($changes['reset_stock_reservations'] ?? false);

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $variantId,
                $priceCents,
                $stockOnHand,
                $actorUserId,
                $resetReservations,
                $priceSpecified,
                $stockSpecified
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
                if ($resetReservations) {
                    $this->releasePriorReservationsForStockReset($pdo, $variantId);
                }

                $update = $pdo->prepare(
                    'UPDATE product_variants
                     SET price_cents = :price_cents,
                         price_specified = :price_specified,
                         stock_on_hand = :stock_on_hand,
                         stock_specified = :stock_specified,
                         stock_reserved = CASE WHEN :reset_stock_reservations = 1 THEN 0 ELSE stock_reserved END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute([
                    'price_cents' => $priceCents,
                    'price_specified' => $priceSpecified ? 1 : 0,
                    'stock_on_hand' => $stockOnHand,
                    'stock_specified' => $stockSpecified ? 1 : 0,
                    'reset_stock_reservations' => $resetReservations ? 1 : 0,
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

    /** @param list<int> $productIds */
    public function setVisibility(array $productIds, bool $active): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new ValidationException('Seleccioná al menos un producto.');
        }
        Database::immediate($this->pdo, function (PDO $pdo) use ($ids, $active): void {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $statement = $pdo->prepare("UPDATE products SET active = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
            $statement->execute([$active ? 1 : 0, ...$ids]);
        });
    }

    /** @param list<int> $productIds */
    public function delete(array $productIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new ValidationException('Seleccioná al menos un producto.');
        }
        Database::immediate($this->pdo, function (PDO $pdo) use ($ids): void {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $activeOrders = $pdo->prepare("SELECT DISTINCT o.public_number FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN product_variants v ON v.id = oi.variant_id WHERE v.product_id IN ($placeholders) AND o.archived_at IS NULL AND o.status <> 'cancelled' ORDER BY o.id DESC");
            $activeOrders->execute($ids);
            $numbers = array_map(static fn (array $row): string => (string) $row['public_number'], $activeOrders->fetchAll());
            if ($numbers !== []) {
                throw new ConflictException('No se puede eliminar porque todavía participa de ventas activas: ' . implode(', ', $numbers) . '.');
            }
            $hideVariants = $pdo->prepare("UPDATE product_variants SET active = 0, sku = '__BORRADO__' || id, barcode = NULL, updated_at = CURRENT_TIMESTAMP WHERE product_id IN ($placeholders)");
            $hideVariants->execute($ids);
            $delete = $pdo->prepare("UPDATE products SET active = 0, deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
            $delete->execute($ids);
        });
    }

    /** @return array{kept:int, removed:int} */
    public function resetDemoData(): array
    {
        return Database::immediate($this->pdo, function (PDO $pdo): array {
            $keepQuery = $pdo->query(
                'SELECT id FROM products WHERE deleted_at IS NULL ORDER BY CASE WHEN image_path IS NULL OR image_path = "" THEN 1 ELSE 0 END, id LIMIT 4'
            );
            $keepIds = array_map(static fn (array $row): int => (int) $row['id'], $keepQuery->fetchAll());
            if (count($keepIds) < 1) {
                throw new ValidationException('No hay productos disponibles para conservar como muestra.');
            }
            $count = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')->fetchColumn();
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $pdo->exec('DELETE FROM payment_proofs');
            $pdo->exec('DELETE FROM order_events');
            $pdo->exec('UPDATE stock_movements SET order_id = NULL');
            $pdo->exec('DELETE FROM orders');
            $hideVariants = $pdo->prepare("UPDATE product_variants SET active = 0, stock_reserved = 0, updated_at = CURRENT_TIMESTAMP WHERE product_id NOT IN ($placeholders)");
            $hideVariants->execute($keepIds);
            $hideProducts = $pdo->prepare("UPDATE products SET active = 0, deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id NOT IN ($placeholders)");
            $hideProducts->execute($keepIds);
            $showVariants = $pdo->prepare("UPDATE product_variants SET active = 1, stock_reserved = 0, updated_at = CURRENT_TIMESTAMP WHERE product_id IN ($placeholders)");
            $showVariants->execute($keepIds);
            $showProducts = $pdo->prepare("UPDATE products SET active = 1, deleted_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
            $showProducts->execute($keepIds);
            return ['kept' => count($keepIds), 'removed' => max(0, $count - count($keepIds))];
        });
    }

    /** @return array{orders:int, products:int} */
    public function prepareCatalogImport(): array
    {
        return Database::immediate($this->pdo, function (PDO $pdo): array {
            $orders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
            $products = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')->fetchColumn();
            $pdo->exec('DELETE FROM payment_proofs');
            $pdo->exec('DELETE FROM order_events');
            $pdo->exec('UPDATE stock_movements SET order_id = NULL');
            $pdo->exec('DELETE FROM orders');
            $pdo->exec('UPDATE product_variants SET active = 0, stock_reserved = 0, barcode = NULL, sku = "__BORRADO__" || id, updated_at = CURRENT_TIMESTAMP');
            $pdo->exec('UPDATE products SET active = 0, deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE deleted_at IS NULL');
            return ['orders' => $orders, 'products' => $products];
        });
    }

    /** @param list<array<string, mixed>> $records */
    public function importCatalog(array $records, int $actorUserId): int
    {
        if ($records === [] || count($records) > 100) throw new ValidationException('La importación no contiene productos válidos.');
        $created = 0;
        foreach ($records as $record) {
            $this->create($record, $actorUserId);
            $created++;
        }
        return $created;
    }

    /**
     * Asigna un código leído en el mostrador a una variante concreta.
     * La restricción UNIQUE de la base impide que el mismo código apunte a
     * dos productos distintos.
     */
    public function assignBarcode(
        int $variantId,
        string $barcode,
        int $actorUserId
    ): void {
        $barcode = trim($barcode);
        if ($variantId < 1) {
            throw new ValidationException('Elegí una variante válida.');
        }
        if (!preg_match('/^[A-Za-z0-9._\-]{3,80}$/', $barcode)) {
            throw new ValidationException(
                'El código debe tener entre 3 y 80 letras, números, puntos o guiones.'
            );
        }

        try {
            Database::immediate(
                $this->pdo,
                function (PDO $pdo) use (
                    $variantId,
                    $barcode,
                    $actorUserId
                ): void {
                    $query = $pdo->prepare(
                        'SELECT pv.barcode, pv.name AS variant_name,
                                p.name AS product_name
                         FROM product_variants pv
                         JOIN products p ON p.id = pv.product_id
                         WHERE pv.id = :id'
                    );
                    $query->execute(['id' => $variantId]);
                    $variant = $query->fetch();
                    if (!$variant) {
                        throw new ValidationException('La variante no existe.');
                    }

                    $update = $pdo->prepare(
                        'UPDATE product_variants
                         SET barcode = :barcode,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id'
                    );
                    $update->execute([
                        'barcode' => $barcode,
                        'id' => $variantId,
                    ]);

                    $this->recordOrderlessAudit(
                        $pdo,
                        $actorUserId,
                        'barcode_assigned',
                        $barcode . ' · ' . $variant['product_name']
                            . ' · ' . $variant['variant_name']
                    );
                }
            );
        } catch (PDOException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw new ConflictException(
                    'Ese código ya está asignado a otro producto.'
                );
            }
            throw $exception;
        }
    }

    public function duplicate(int $productId, int $actorUserId, bool $copyImages = true): int
    {
        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($productId, $actorUserId, $copyImages): int {
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
                    'image_path' => $copyImages ? $product['image_path'] : null,
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
                    $newSku = str_starts_with((string) $variant['sku'], '__AUTO__')
                        ? '__AUTO__' . bin2hex(random_bytes(12))
                        : substr((string) $variant['sku'], 0, 48) . $suffix;
                    $newVariantId = $this->insertVariant(
                        $pdo,
                        $newProductId,
                        [
                            'name' => $variant['name'],
                            'sku' => $newSku,
                            'barcode' => null,
                            'image_path' => $copyImages ? $variant['image_path'] : null,
                            'price_cents' => (int) $variant['price_cents'],
                            'price_specified' => (bool) ($variant['price_specified'] ?? true),
                            'stock_on_hand' => 0,
                            'stock_specified' => (bool) ($variant['stock_specified'] ?? true),
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
                'image_path' => $row['variant_image_path'] ?? null,
                'price_cents' => !empty($row['price_specified']) ? (int) $row['price_cents'] : null,
                'available_stock' => !empty($row['stock_specified']) ? (int) $row['available_stock'] : null,
            ];
            if ($admin) {
                $variant += [
                    'sku' => str_starts_with((string) $row['sku'], '__AUTO__') ? '' : $row['sku'],
                    'barcode' => $row['barcode'],
                    'stock_on_hand' => !empty($row['stock_specified']) ? (int) $row['stock_on_hand'] : null,
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
        $categoryId = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
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
        $seenBarcodes = [];
        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                throw new ValidationException('Hay una variante inválida.');
            }
            $variantName = trim((string) ($variant['name'] ?? ''));
            $sku = strtoupper(trim((string) ($variant['sku'] ?? '')));
            $barcode = trim((string) ($variant['barcode'] ?? ''));
            $priceSpecified = ($variant['price_cents'] ?? null) !== null && ($variant['price_cents'] ?? '') !== '';
            $stockSpecified = ($variant['stock_on_hand'] ?? null) !== null && ($variant['stock_on_hand'] ?? '') !== '';
            $price = filter_var(
                $priceSpecified ? $variant['price_cents'] : 0,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );
            $stock = filter_var(
                $stockSpecified ? $variant['stock_on_hand'] : 0,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );
            $minimum = filter_var(
                $variant['min_stock'] ?? 0,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );

            if ($variantName === '') {
                throw new ValidationException('Cada variante necesita un nombre.');
            }
            if ($price === false || $stock === false || $minimum === false) {
                throw new ValidationException('Precio y stock deben ser números enteros positivos.');
            }
            if ($sku !== '' && isset($seenSkus[$sku])) {
                throw new ValidationException('No se puede repetir el SKU dentro del producto.');
            }
            if ($sku !== '') $seenSkus[$sku] = true;
            if ($barcode !== '') {
                $barcodeKey = strtolower($barcode);
                if (isset($seenBarcodes[$barcodeKey])) {
                    throw new ValidationException('No se puede repetir el código de barras dentro del producto.');
                }
                $seenBarcodes[$barcodeKey] = true;
            }
            $variantImagePath = trim((string) ($variant['image_path'] ?? ''));
            if (
                $variantImagePath !== ''
                && !str_starts_with($variantImagePath, '/')
            ) {
                throw new ValidationException('La foto de una variante debe estar alojada en este sitio.');
            }

            $validatedVariants[] = [
                'id' => isset($variant['id']) ? (int) $variant['id'] : null,
                'name' => $variantName,
                'sku' => $sku !== '' ? $sku : '__AUTO__' . bin2hex(random_bytes(12)),
                'barcode' => $barcode !== '' ? $barcode : null,
                'image_path' => $variantImagePath ?: null,
                'price_cents' => $price,
                'price_specified' => $priceSpecified,
                'stock_on_hand' => $stock,
                'stock_specified' => $stockSpecified,
                'min_stock' => $minimum,
                'active' => !isset($variant['active']) || (bool) $variant['active'],
                'reset_stock_reservations' => !empty($variant['reset_stock_reservations']),
            ];
        }

        $imagePath = trim((string) ($data['image_path'] ?? ''));
        if (
            $imagePath !== ''
            && !str_starts_with($imagePath, '/')
        ) {
            throw new ValidationException(
                'La imagen debe estar alojada en este sitio.'
            );
        }

        return [
            'name' => function_exists('mb_strtoupper')
                ? mb_strtoupper($name, 'UTF-8')
                : strtoupper($name),
            'category' => $category !== '' ? $category : 'General',
            'category_id' => $categoryId === false ? null : (int) $categoryId,
            'description' => trim((string) ($data['description'] ?? '')),
            'image_path' => $imagePath ?: null,
            'active' => !isset($data['active']) || (bool) $data['active'],
            'variants' => $validatedVariants,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function assertBarcodesAvailable(array $payload, ?int $productId = null): void
    {
        $query = $this->pdo->prepare(
            'SELECT pv.id
             FROM product_variants pv
             WHERE pv.barcode = :barcode COLLATE NOCASE
               AND (:product_id IS NULL OR pv.product_id <> :product_id)
             LIMIT 1'
        );
        foreach ($payload['variants'] as $variant) {
            if ($variant['barcode'] === null) {
                continue;
            }
            $query->bindValue(':barcode', $variant['barcode']);
            if ($productId === null) {
                $query->bindValue(':product_id', null, PDO::PARAM_NULL);
            } else {
                $query->bindValue(':product_id', $productId, PDO::PARAM_INT);
            }
            $query->execute();
            if ($query->fetchColumn() !== false) {
                throw new ConflictException('Ese código de barras ya está asignado a otro producto.');
            }
        }
    }

    /**
     * Una carga manual de stock define el nuevo punto de partida. Las reservas
     * anteriores dejan de restar disponibilidad y no podrán restaurarse luego.
     */
    private function releasePriorReservationsForStockReset(PDO $pdo, int $variantId): void
    {
        $orders = $pdo->prepare(
            'SELECT DISTINCT o.id
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE oi.variant_id = :variant_id
               AND o.stock_reserved_at IS NOT NULL'
        );
        $orders->execute(['variant_id' => $variantId]);
        foreach ($orders->fetchAll() as $order) {
            $items = $pdo->prepare(
                'SELECT variant_id, quantity FROM order_items WHERE order_id = :order_id'
            );
            $items->execute(['order_id' => (int) $order['id']]);
            foreach ($items->fetchAll() as $item) {
                $release = $pdo->prepare(
                    'UPDATE product_variants
                     SET stock_reserved = CASE
                         WHEN stock_reserved > :quantity THEN stock_reserved - :quantity
                         ELSE 0
                     END,
                     updated_at = CURRENT_TIMESTAMP
                     WHERE id = :variant_id'
                );
                $release->execute([
                    'quantity' => (int) $item['quantity'],
                    'variant_id' => (int) $item['variant_id'],
                ]);
            }
            $clear = $pdo->prepare(
                'UPDATE orders SET stock_reserved_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $clear->execute(['id' => (int) $order['id']]);
        }
    }

    private function categoryId(PDO $pdo, string $categoryName, ?int $categoryId = null): int
    {
        if ($categoryId !== null) {
            $existing = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
            $existing->execute(['id' => $categoryId]);
            if ($existing->fetchColumn() === false) {
                throw new ValidationException('La categoría seleccionada no existe.');
            }
            return $categoryId;
        }
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
                product_id, name, sku, barcode, image_path, price_cents, price_specified,
                stock_on_hand, stock_specified, min_stock, sort_order, active
             ) VALUES(
                :product_id, :name, :sku, :barcode, :image_path, :price_cents, :price_specified,
                :stock_on_hand, :stock_specified, :min_stock, :sort_order, :active
             )'
        );
        $insert->execute([
            'product_id' => $productId,
            'name' => $variant['name'],
            'sku' => $variant['sku'],
            'barcode' => $variant['barcode'],
            'image_path' => $variant['image_path'],
            'price_cents' => $variant['price_cents'],
            'price_specified' => $variant['price_specified'] ? 1 : 0,
            'stock_on_hand' => $variant['stock_on_hand'],
            'stock_specified' => $variant['stock_specified'] ? 1 : 0,
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
