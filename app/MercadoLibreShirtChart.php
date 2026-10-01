<?php
declare(strict_types=1);

namespace LaboratorioDigital;

/** Converts existing garment measurements without inventing size equivalences. */
final class MercadoLibreShirtChart
{
    public static function size(string $name): string
    {
        return trim((string) preg_replace('/^talle\s*/i', '', trim($name)));
    }

    public static function measurements(array $variants, array $guide, string $group): array
    {
        if ($group === '' || !$variants) throw new \RuntimeException('Elegí la tabla de talles de esta remera.');
        $rows = [];
        foreach ($variants as $variant) {
            $size = self::size($variant['name']);
            $matches = array_values(array_filter($guide, static fn ($r) => $r['group'] === $group && self::size($r['size']) === $size));
            if (count($matches) !== 1) throw new \RuntimeException('Falta una medida inequívoca para el talle ' . $size . '.');
            $row = ['size' => $size, 'variant_id' => $variant['id']];
            foreach (['width', 'length'] as $dimension) {
                if (!preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:cm)?$/iD', trim($matches[0][$dimension]), $m)) throw new \RuntimeException('La medida de ' . $dimension . ' del talle ' . $size . ' debe estar en cm.');
                $value = (float) str_replace(',', '.', $m[1]);
                if ($value <= 0 || $value > 200) throw new \RuntimeException('Medida fuera de rango para el talle ' . $size . '.');
                $row[$dimension] = $value;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public static function payload(array $rows, array $template, array $filters, array $equivalences = []): array
    {
        $definitions = [];
        $visit = static function (array $node) use (&$visit, &$definitions): void {
            if (isset($node['id'], $node['value_type'])) $definitions[$node['id']] = $node;
            foreach ($node as $value) if (is_array($value)) $visit($value);
        };
        $visit($template);
        foreach (['SIZE', 'GARMENT_CHEST_WIDTH_FROM', 'GARMENT_LENGTH_FROM'] as $id) {
            if (!isset($definitions[$id])) throw new \RuntimeException('La guía de MeLi no admite el atributo ' . $id . ' para las medidas de esta remera.');
        }
        $chartRows = [];
        foreach ($rows as $row) {
            $attrs = [['id' => 'SIZE', 'values' => [['name' => $row['size']]]]];
            foreach (['GARMENT_CHEST_WIDTH_FROM' => 'width', 'GARMENT_LENGTH_FROM' => 'length'] as $id => $dimension) {
                $attrs[] = ['id' => $id, 'values' => [['name' => $row[$dimension] . ' cm', 'struct' => ['number' => $row[$dimension], 'unit' => 'cm']]]];
            }
            foreach ($definitions as $id => $definition) {
                $tags = $definition['tags'] ?? [];
                $tag = static fn (string $name): bool => !empty($tags[$name]) || in_array($name, $tags, true);
                if ($tag('grid_filter') || in_array($id, ['SIZE', 'GARMENT_CHEST_WIDTH_FROM', 'GARMENT_LENGTH_FROM', 'BRAND', 'GENDER'], true)) continue;
                if (!$tag('required')) continue;
                if ($definition['value_type'] === 'list') {
                    $equivalent = $id === 'FILTRABLE_SIZE' ? ($equivalences[$row['variant_id']] ?? $row['size']) : $row['size'];
                    $options = array_values(array_filter($definition['values'] ?? [], static fn ($v) => self::size($v['name']) === $equivalent));
                    if (count($options) !== 1) throw new \RuntimeException('MeLi requiere ' . ($definition['name'] ?? $id) . ' para el talle ' . $row['size'] . '. Valores admitidos: ' . implode(', ', array_column($definition['values'] ?? [], 'name')) . '. No se inventa una equivalencia.');
                    $attrs[] = ['id' => $id, 'values' => [['id' => $options[0]['id'], 'name' => $options[0]['name']]]];
                } elseif (!$tag('BODY_MEASURE') && !$tag('body_measure')) {
                    throw new \RuntimeException('MeLi requiere una medida adicional: ' . ($definition['name'] ?? $id) . '.');
                }
            }
            $chartRows[] = ['attributes' => $attrs];
        }
        return ['names' => ['MLA' => 'Remeras unisex sin marca'], 'domain_id' => 'T_SHIRTS', 'site_id' => 'MLA',
            'measure_type' => 'CLOTHING_MEASURE', 'main_attribute' => ['attributes' => [['site_id' => 'MLA', 'id' => 'SIZE']]],
            'attributes' => $filters, 'rows' => $chartRows];
    }

    public static function assignments(array $variants, array $chart): array
    {
        $assignments = [];
        foreach ($variants as $variant) {
            $matches = array_values(array_filter($chart['rows'] ?? [], static function ($row) use ($variant, $chart): bool {
                foreach ($row['attributes'] ?? [] as $attribute) {
                    if ($attribute['id'] !== ($chart['main_attribute_id'] ?? 'SIZE')) continue;
                    foreach ($attribute['values'] ?? [] as $value) if (self::size($value['name']) === self::size($variant['name'])) return true;
                }
                return false;
            }));
            if (count($matches) !== 1) throw new \RuntimeException('No se pudo vincular la fila de MeLi de ' . $variant['name'] . '.');
            $assignments[$variant['id']] = (string) $matches[0]['id'];
        }
        return $assignments;
    }
}
