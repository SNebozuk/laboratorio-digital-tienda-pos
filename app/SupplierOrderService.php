<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class SupplierOrderService
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function draft(int $userId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT payload_json, updated_at FROM supplier_order_drafts WHERE user_id = :user_id'
        );
        $query->execute(['user_id' => $userId]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        $payload = json_decode((string) $row['payload_json'], true);
        if (!is_array($payload)) {
            return null;
        }
        $payload['updated_at'] = (string) $row['updated_at'];
        return $payload;
    }

    /** @param array<string, mixed> $filters
     *  @return array{filters: array<string, mixed>, results: list<array<string, mixed>>}
     */
    public function search(array $filters): array
    {
        $filters = $this->filters($filters);
        $where = ['p.deleted_at IS NULL', 'v.stock_on_hand <= :threshold'];
        $parameters = ['threshold' => $filters['stock_threshold']];

        if ($filters['category_ids']) {
            $placeholders = [];
            foreach ($filters['category_ids'] as $index => $categoryId) {
                $name = ':category_' . $index;
                $placeholders[] = $name;
                $parameters[substr($name, 1)] = $categoryId;
            }
            $where[] = 'p.category_id IN (' . implode(', ', $placeholders) . ')';
        }

        $query = $this->pdo->prepare(
            'SELECT p.id AS product_id, p.name AS product_name, p.description,
                    p.active AS product_active, c.id AS category_id, c.name AS category_name,
                    v.id AS variant_id, v.name AS variant_name, v.sku, v.barcode,
                    v.stock_on_hand
             FROM products p
             JOIN product_variants v ON v.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE ' . implode(' AND ', $where)
        );
        $query->execute($parameters);
        $rows = $query->fetchAll();

        $keywords = preg_split('/\s+/', $this->fold($filters['keywords']), -1, PREG_SPLIT_NO_EMPTY);
        if ($keywords !== []) {
            $rows = array_values(array_filter($rows, function (array $row) use ($keywords): bool {
                $searchable = $this->fold(implode(' ', [
                    $row['product_name'],
                    $row['description'],
                    $row['variant_name'],
                    $row['sku'],
                    $row['barcode'],
                ]));

                foreach ($keywords as $keyword) {
                    if (!str_contains($searchable, $keyword)) {
                        return false;
                    }
                }

                return true;
            }));
        }

        return [
            'filters' => $filters,
            'results' => $this->groupRows($rows),
        ];
    }

    /** @param array<string, mixed> $draft
     *  @return array<string, mixed>
     */
    public function save(int $userId, array $draft): array
    {
        $filters = $this->filters(is_array($draft['filters'] ?? null) ? $draft['filters'] : []);
        $results = $this->canonicalResults(
            is_array($draft['results'] ?? null) ? $draft['results'] : []
        );
        $cart = $this->canonicalResults(
            is_array($draft['cart'] ?? null) ? $draft['cart'] : $results
        );
        $allowedVariantIds = [];
        foreach ($cart as $product) {
            foreach ($product['variants'] as $variant) {
                $allowedVariantIds[(int) $variant['id']] = true;
            }
        }
        $lines = $this->lines(
            is_array($draft['lines'] ?? null) ? $draft['lines'] : [],
            $allowedVariantIds
        );
        $whatsappEdited = !empty($draft['whatsapp_edited']);
        $whatsappText = trim((string) ($draft['whatsapp_text'] ?? ''));
        if (strlen($whatsappText) > 20000) {
            throw new ValidationException('El texto para WhatsApp es demasiado largo.');
        }
        if (!$whatsappEdited) {
            $whatsappText = $this->whatsappText($cart, $lines);
        }

        $summary = $this->summary($lines);
        $payload = [
            'filters' => $filters,
            'results' => $results,
            'lines' => $lines,
            'summary' => $summary,
            'cart' => $cart,
            'whatsapp_text' => $whatsappText,
            'whatsapp_edited' => $whatsappEdited,
            'searched' => !empty($draft['searched']),
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        Database::immediate($this->pdo, function (PDO $pdo) use ($userId, $encoded): void {
            $save = $pdo->prepare(
                'INSERT INTO supplier_order_drafts(user_id, payload_json, updated_at)
                 VALUES(:user_id, :payload_json, CURRENT_TIMESTAMP)
                 ON CONFLICT(user_id) DO UPDATE SET
                    payload_json = excluded.payload_json,
                    updated_at = CURRENT_TIMESTAMP'
            );
            $save->execute(['user_id' => $userId, 'payload_json' => $encoded]);
        });

        return $this->draft($userId) ?? $payload;
    }

    public function clear(int $userId): void
    {
        $delete = $this->pdo->prepare('DELETE FROM supplier_order_drafts WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);
    }

    /** @param array<string, mixed> $filters
     *  @return array{category_ids:list<int>,keywords:string,stock_threshold:int}
     */
    private function filters(array $filters): array
    {
        $rawCategoryIds = $filters['category_ids'] ?? [];
        if (!is_array($rawCategoryIds) || count($rawCategoryIds) > 100) {
            throw new ValidationException('Las categorías seleccionadas no son válidas.');
        }
        $categoryIds = [];
        foreach ($rawCategoryIds as $id) {
            $categoryIds[] = $this->integer($id, 'La categoría seleccionada', 1, PHP_INT_MAX);
        }
        $categoryIds = array_values(array_unique($categoryIds));
        if ($categoryIds) {
            $placeholders = [];
            $parameters = [];
            foreach ($categoryIds as $index => $categoryId) {
                $name = ':id_' . $index;
                $placeholders[] = $name;
                $parameters[substr($name, 1)] = $categoryId;
            }
            $categories = $this->pdo->prepare(
                'SELECT COUNT(*) FROM categories WHERE id IN (' . implode(', ', $placeholders) . ')'
            );
            $categories->execute($parameters);
            if ((int) $categories->fetchColumn() !== count($categoryIds)) {
                throw new ValidationException('Una de las categorías seleccionadas ya no existe.');
            }
        }

        $keywords = trim((string) ($filters['keywords'] ?? ''));
        if (strlen($keywords) > 250) {
            throw new ValidationException('Las palabras clave no pueden superar 250 caracteres.');
        }

        return [
            'category_ids' => $categoryIds,
            'keywords' => $keywords,
            'stock_threshold' => $this->integer(
                $filters['stock_threshold'] ?? null,
                'El valor de stock',
                0,
                1000000
            ),
        ];
    }

    /** @param list<array<string, mixed>> $results
     *  @return list<array<string, mixed>>
     */
    private function canonicalResults(array $results): array
    {
        if (count($results) > 10000) {
            throw new ValidationException('El pedido contiene demasiados productos.');
        }
        $variantIds = [];
        foreach ($results as $result) {
            if (!is_array($result) || !is_array($result['variants'] ?? null)) {
                throw new ValidationException('Los resultados del pedido no son válidos.');
            }
            foreach ($result['variants'] as $variant) {
                $variantId = $this->integer(
                    is_array($variant) ? ($variant['id'] ?? null) : $variant,
                    'La variante',
                    1,
                    PHP_INT_MAX
                );
                if (isset($variantIds[$variantId])) {
                    throw new ValidationException('No se puede repetir una variante en el pedido.');
                }
                $variantIds[$variantId] = true;
            }
        }
        if (!$variantIds) {
            return [];
        }

        $placeholders = [];
        $parameters = [];
        foreach (array_keys($variantIds) as $index => $variantId) {
            $name = ':variant_' . $index;
            $placeholders[] = $name;
            $parameters[substr($name, 1)] = $variantId;
        }
        $query = $this->pdo->prepare(
            'SELECT p.id AS product_id, p.name AS product_name, p.active AS product_active,
                    c.id AS category_id, c.name AS category_name,
                    v.id AS variant_id, v.name AS variant_name, v.sku, v.barcode, v.stock_on_hand
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.deleted_at IS NULL AND v.id IN (' . implode(', ', $placeholders) . ')'
        );
        $query->execute($parameters);
        $rows = $query->fetchAll();
        if (count($rows) !== count($variantIds)) {
            throw new ValidationException('Uno de los productos del pedido fue eliminado. Volvé a buscar.');
        }
        return $this->groupRows($rows);
    }

    /** @param list<array<string, mixed>> $rows
     *  @return list<array<string, mixed>>
     */
    private function groupRows(array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $productId,
                    'name' => (string) $row['product_name'],
                    'active' => (bool) $row['product_active'],
                    'category' => [
                        'id' => $row['category_id'] === null ? null : (int) $row['category_id'],
                        'name' => (string) ($row['category_name'] ?? 'Sin categoría'),
                    ],
                    'variants' => [],
                ];
            }
            $products[$productId]['variants'][] = [
                'id' => (int) $row['variant_id'],
                'name' => (string) $row['variant_name'],
                'sku' => str_starts_with((string) $row['sku'], '__AUTO__') ? '' : (string) $row['sku'],
                'barcode' => (string) ($row['barcode'] ?? ''),
                'stock_on_hand' => (int) $row['stock_on_hand'],
            ];
        }
        $products = array_values($products);
        usort($products, fn (array $left, array $right): int => $this->fold($left['name']) <=> $this->fold($right['name']));
        foreach ($products as &$product) {
            usort($product['variants'], fn (array $left, array $right): int => $this->fold($left['name']) <=> $this->fold($right['name']));
        }
        unset($product);
        return $products;
    }

    /** @param list<array<string, mixed>> $lines
     *  @param array<int, bool> $allowedVariantIds
     *  @return list<array<string, int|null>>
     */
    private function lines(array $lines, array $allowedVariantIds): array
    {
        if (count($lines) > count($allowedVariantIds)) {
            throw new ValidationException('Las líneas del pedido no son válidas.');
        }
        $normalized = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                throw new ValidationException('Una línea del pedido no es válida.');
            }
            $variantId = $this->integer($line['variant_id'] ?? null, 'La variante', 1, PHP_INT_MAX);
            if (!isset($allowedVariantIds[$variantId]) || isset($normalized[$variantId])) {
                throw new ValidationException('Una línea no pertenece a los resultados del pedido.');
            }
            $quantity = $this->integer($line['quantity'] ?? null, 'La cantidad', 0, 1000000);
            $price = $line['unit_price_cents'] ?? null;
            $priceCents = $price === null || $price === ''
                ? null
                : $this->integer($price, 'El precio unitario', 0, 999999999);
            $normalized[$variantId] = [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price_cents' => $priceCents,
                'subtotal_cents' => $priceCents === null ? null : $quantity * $priceCents,
            ];
        }
        foreach (array_keys($allowedVariantIds) as $variantId) {
            $normalized[$variantId] ??= [
                'variant_id' => $variantId,
                'quantity' => 0,
                'unit_price_cents' => null,
                'subtotal_cents' => null,
            ];
        }
        return array_values($normalized);
    }

    /** @param list<array<string, int|null>> $lines
     *  @return array{total_cents:int,total_units:int,included_count:int,missing_price_count:int}
     */
    private function summary(array $lines): array
    {
        $total = 0;
        $units = 0;
        $included = 0;
        $missing = 0;
        foreach ($lines as $line) {
            if ((int) $line['quantity'] < 1) {
                continue;
            }
            $included++;
            $units += (int) $line['quantity'];
            if ($line['unit_price_cents'] === null) {
                $missing++;
                continue;
            }
            $total += (int) $line['subtotal_cents'];
        }
        return [
            'total_cents' => $total,
            'total_units' => $units,
            'included_count' => $included,
            'missing_price_count' => $missing,
        ];
    }

    /** @param list<array<string, mixed>> $results
     *  @param list<array<string, int|null>> $lines
     */
    private function whatsappText(array $results, array $lines): string
    {
        $byVariant = [];
        foreach ($lines as $line) {
            $byVariant[(int) $line['variant_id']] = $line;
        }
        $message = [];
        foreach ($results as $product) {
            $isSingle = count($product['variants']) === 1
                && $this->fold((string) $product['variants'][0]['name']) === 'unica';
            foreach ($product['variants'] as $variant) {
                $line = $byVariant[(int) $variant['id']] ?? null;
                if (!$line || (int) $line['quantity'] < 1) {
                    continue;
                }
                $quantity = (int) $line['quantity'];
                $label = (string) $product['name'];
                if (!$isSingle && trim((string) $variant['name']) !== '') {
                    $label .= ', ' . trim((string) $variant['name']);
                }
                $message[] = $quantity . ' ' . ($quantity === 1 ? 'unidad' : 'unidades') . ' - ' . $label;
            }
        }
        return implode("\n", $message);
    }

    private function integer(mixed $value, string $label, int $minimum, int $maximum): int
    {
        if (is_int($value)) {
            $number = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value)) {
            $number = (int) $value;
        } else {
            throw new ValidationException($label . ' debe ser un número entero válido.');
        }
        if ($number < $minimum || $number > $maximum) {
            throw new ValidationException($label . ' está fuera del rango permitido.');
        }
        return $number;
    }

    private function fold(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return strtolower($ascii === false ? $value : $ascii);
    }
}
