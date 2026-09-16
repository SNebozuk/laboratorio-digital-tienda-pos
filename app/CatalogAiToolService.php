<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class CatalogAiToolService
{
    /** @var list<array<string,mixed>>|null */
    private ?array $catalog = null;

    public function __construct(private readonly ProductService $products)
    {
    }

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function buscarProductos(array $filters = [], int $limit = 50): array
    {
        $filters = $this->filters($filters);
        $codeProductIds = isset($filters['texto']) ? array_flip($this->products->publicCodeMatches((string) $filters['texto'])) : [];
        $matches = [];
        foreach ($this->catalog() as $product) {
            foreach ($product['variants'] as $variant) {
                $row = $this->row($product, $variant);
                if (isset($codeProductIds[(int) $product['id']]) || $this->matches($row, $filters)) $matches[] = $row;
            }
        }
        usort($matches, static fn (array $a, array $b): int => ($b['stock'] > 0 <=> $a['stock'] > 0) ?: strcmp($a['producto'], $b['producto']));
        return array_slice($matches, 0, max(1, min(200, $limit)));
    }

    /** @return list<array<string, mixed>> */
    public function obtenerVariantes(int $productId): array
    {
        if ($productId < 1) return [];
        foreach ($this->catalog() as $product) {
            if ((int) $product['id'] !== $productId) continue;
            return array_map(fn (array $variant): array => $this->row($product, $variant), $product['variants']);
        }
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function obtenerVariantesPorCodigo(string $code): array
    {
        $code = trim($code);
        foreach ($this->products->publicCodeMatches($code, 1) as $productId) {
            return $this->obtenerVariantes($productId);
        }
        return ctype_digit($code) ? $this->obtenerVariantes((int) $code) : [];
    }

    /** @return array<string, int>|null */
    public function consultarStock(int $variantId): ?array
    {
        foreach ($this->catalog() as $product) foreach ($product['variants'] as $variant) {
            if ((int) $variant['id'] === $variantId) return ['variante_id' => $variantId, 'stock' => (int) ($variant['available_stock'] ?? 0)];
        }
        return null;
    }

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function buscarAlternativas(array $filters = []): array
    {
        $filters = $this->filters($filters);
        $source = isset($filters['variante_id']) ? $this->sourceVariant((int) $filters['variante_id']) : null;
        if ($source) {
            unset($filters['texto']);
            $filters += ['categoria' => $source['categoria'], 'talle' => $source['talle'] ?? '', 'color' => $source['color'] ?? ''];
        }
        $rows = array_filter($this->buscarProductos($filters), static fn (array $row): bool => $row['stock'] > 0 && (!$source || $row['variante_id'] !== $source['variante_id']));
        usort($rows, static fn (array $a, array $b): int => (($source && $a['producto_id'] === $source['producto_id']) ? 0 : 1) <=> (($source && $b['producto_id'] === $source['producto_id']) ? 0 : 1) ?: strcmp($a['producto'], $b['producto']));
        return array_slice(array_values($rows), 0, 20);
    }

    /** @param array<string, mixed> $product @param array<string, mixed> $variant @return array<string, mixed> */
    private function row(array $product, array $variant): array
    {
        $variantName = trim((string) $variant['name']);
        preg_match('/\btalle\s*([[:alnum:].-]+)/iu', $variantName, $size);
        if (!isset($size[1]) && preg_match('/\b(\d+(?:\.\d+)?)\b/u', $variantName, $numericSize)) $size[1] = $numericSize[1];
        $parts = preg_split('/\s+-\s+/', (string) $product['name']);
        $color = count($parts) > 1 ? trim((string) end($parts)) : null;
        return ['producto_id' => (int) $product['id'], 'producto' => $product['name'], 'descripcion' => $product['description'] ?? '', 'categoria' => $product['category']['name'] ?? null, 'variante_id' => (int) $variant['id'], 'variante' => $variantName, 'atributos' => ['nombre' => $variantName], 'talle' => $size[1] ?? null, 'color' => $color, 'precio' => $variant['price_cents'] === null ? null : (int) $variant['price_cents'] / 100, 'stock' => $variant['available_stock'] === null ? null : (int) $variant['available_stock'], 'imagen' => $product['image_path'] ?? null, 'visible' => ($product['active'] ?? true) && ($variant['active'] ?? true)];
    }

    /** @param array<string, mixed> $filters @return array<string, string|int> */
    private function filters(array $filters): array
    {
        $allowed = ['texto', 'marca', 'categoria', 'material', 'talle', 'color', 'gramaje', 'tamano', 'tipo', 'uso', 'atributos', 'variante_id']; $out = [];
        foreach ($allowed as $key) if (isset($filters[$key]) && is_scalar($filters[$key])) $out[$key] = $key === 'variante_id' ? (int) $filters[$key] : (function_exists('mb_strtolower') ? mb_strtolower(trim((string) $filters[$key])) : strtolower(trim((string) $filters[$key])));
        return $out;
    }

    /** @param array<string, mixed> $row @param array<string, string|int> $filters */
    private function matches(array $row, array $filters): bool
    {
        if (isset($filters['variante_id']) && $row['variante_id'] !== $filters['variante_id']) return false;
        foreach (['texto' => 'identidad', 'marca' => 'detalles', 'categoria' => 'categoria', 'material' => 'detalles', 'talle' => 'talle', 'color' => 'color', 'gramaje' => 'detalles', 'tamano' => 'detalles', 'tipo' => 'detalles', 'uso' => 'detalles', 'atributos' => 'detalles'] as $filter => $field) {
            $value = match ($field) {
                'identidad' => implode(' ', [$row['producto'], $row['descripcion'], $row['categoria'], $row['variante']]),
                'detalles' => implode(' ', [$row['producto'], $row['descripcion'], $row['categoria'], $row['variante']]),
                default => (string) ($row[$field] ?? ''),
            };
            if (isset($filters[$filter]) && !$this->textMatches($value, (string) $filters[$filter])) return false;
        }
        return true;
    }
    private function textMatches(string $value, string $query): bool
    {
        $value = $this->fold($value); $query = $this->fold($query);
        if ($query === '' || str_contains($value, $query)) return true;
        $compactValue = preg_replace('/[^a-z0-9]/', '', $value); $compactQuery = preg_replace('/[^a-z0-9]/', '', $query);
        if ($compactQuery !== '' && str_contains($compactValue, $compactQuery)) return true;
        $words = preg_split('/[^a-z0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = preg_split('/[^a-z0-9]+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ignored = ['de', 'del', 'para', 'por', 'favor', 'quiero', 'quisiera', 'necesito', 'busco', 'buscar', 'mostrame', 'mostrar', 'dame', 'comprar', 'compra', 'tenes', 'tienen', 'hay', 'un', 'una', 'unos', 'unas', 'el', 'la', 'los', 'las'];
        $terms = array_values(array_diff($terms, $ignored));
        foreach ($terms as $term) {
            $found = false;
            foreach ($words as $word) {
                $shortest = min(strlen($word), strlen($term));
                $prefixMatch = $shortest >= 4
                    && abs(strlen($word) - strlen($term)) <= 2
                    && (str_starts_with($word, $term) || str_starts_with($term, $word));
                $rootLength = strspn($word ^ $term, "\0");
                $rootMatch = $shortest >= 5 && $rootLength >= 5 && $rootLength >= (int) ceil($shortest * 0.7);
                $singularWord = strlen($word) > 4 ? preg_replace('/s$/', '', $word) : $word;
                $singularTerm = strlen($term) > 4 ? preg_replace('/s$/', '', $term) : $term;
                $fuzzyMatch = $shortest >= 4 && levenshtein($singularWord, $singularTerm) <= 1;
                if ($word === $term || $prefixMatch || $rootMatch || $fuzzyMatch) { $found = true; break; }
            }
            if (!$found) return false;
        }
        return $terms !== [];
    }
    /** @return list<array<string,mixed>> */
    private function catalog(): array { return $this->catalog ??= $this->products->adminCatalog(); }
    private function sourceVariant(int $id): ?array { foreach ($this->catalog() as $p) foreach ($p['variants'] as $v) if ((int) $v['id'] === $id) return $this->row($p, $v); return null; }
    private function fold(string $value): string { $value = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value); return preg_replace('/[áàä]/u','a',preg_replace('/[éèë]/u','e',preg_replace('/[íìï]/u','i',preg_replace('/[óòö]/u','o',preg_replace('/[úùü]/u','u',$value))))); }
}
