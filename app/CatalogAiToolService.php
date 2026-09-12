<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class CatalogAiToolService
{
    public function __construct(private readonly ProductService $products)
    {
    }

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function buscarProductos(array $filters = []): array
    {
        $filters = $this->filters($filters);
        $matches = [];
        foreach ($this->products->publicCatalog() as $product) {
            foreach ($product['variants'] as $variant) {
                $row = $this->row($product, $variant);
                if ($this->matches($row, $filters)) $matches[] = $row;
            }
        }
        usort($matches, static fn (array $a, array $b): int => ($b['stock'] > 0 <=> $a['stock'] > 0) ?: strcmp($a['producto'], $b['producto']));
        return array_slice($matches, 0, 50);
    }

    /** @return list<array<string, mixed>> */
    public function obtenerVariantes(int $productId): array
    {
        if ($productId < 1) return [];
        foreach ($this->products->publicCatalog() as $product) {
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
        foreach ($this->products->publicCatalog() as $product) foreach ($product['variants'] as $variant) {
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
        $parts = preg_split('/\s+-\s+/', (string) $product['name']);
        $color = count($parts) > 1 ? trim((string) end($parts)) : null;
        return ['producto_id' => (int) $product['id'], 'producto' => $product['name'], 'categoria' => $product['category']['name'] ?? null, 'variante_id' => (int) $variant['id'], 'variante' => $variantName, 'atributos' => ['nombre' => $variantName], 'talle' => $size[1] ?? null, 'color' => $color, 'precio' => $variant['price_cents'] === null ? null : (int) $variant['price_cents'] / 100, 'stock' => $variant['available_stock'] === null ? null : (int) $variant['available_stock'], 'imagen' => $product['image_path'] ?? null];
    }

    /** @param array<string, mixed> $filters @return array<string, string|int> */
    private function filters(array $filters): array
    {
        $allowed = ['texto', 'categoria', 'talle', 'color', 'tipo', 'uso', 'atributos', 'variante_id']; $out = [];
        foreach ($allowed as $key) if (isset($filters[$key]) && is_scalar($filters[$key])) $out[$key] = $key === 'variante_id' ? (int) $filters[$key] : (function_exists('mb_strtolower') ? mb_strtolower(trim((string) $filters[$key])) : strtolower(trim((string) $filters[$key])));
        return $out;
    }

    /** @param array<string, mixed> $row @param array<string, string|int> $filters */
    private function matches(array $row, array $filters): bool
    {
        if (isset($filters['variante_id']) && $row['variante_id'] !== $filters['variante_id']) return false;
        foreach (['texto' => 'producto', 'categoria' => 'categoria', 'talle' => 'talle', 'color' => 'color', 'tipo' => 'producto', 'uso' => 'producto', 'atributos' => 'variante'] as $filter => $field) {
            if (isset($filters[$filter]) && !str_contains($this->fold((string) $row[$field]), $this->fold((string) $filters[$filter]))) return false;
        }
        return true;
    }
    private function sourceVariant(int $id): ?array { foreach ($this->products->publicCatalog() as $p) foreach ($p['variants'] as $v) if ((int) $v['id'] === $id) return $this->row($p, $v); return null; }
    private function fold(string $value): string { $value = mb_strtolower($value); return preg_replace('/[áàä]/u','a',preg_replace('/[éèë]/u','e',preg_replace('/[íìï]/u','i',preg_replace('/[óòö]/u','o',preg_replace('/[úùü]/u','u',$value))))); }
}
