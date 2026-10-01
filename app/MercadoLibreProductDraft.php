<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

require_once __DIR__ . '/MercadoLibrePriceCalculator.php';

/** Private publication drafts; saving a product never publishes an item. */
final class MercadoLibreProductDraft
{
    public static function normalize(mixed $input): ?array
    {
        if ($input === null) return null;
        if (!is_array($input)) throw new ValidationException('La ficha de Mercado Libre no es válida.');
        $result = [];
        foreach (['category_id' => 30, 'family_name' => 120, 'description' => 50000,
            'listing_type_id' => 40, 'condition' => 30, 'shipping_mode' => 30, 'logistic_type' => 40, 'catalog_product_id' => 40] as $key => $limit) {
            if (!is_scalar($input[$key] ?? '')) throw new ValidationException('Campo de Mercado Libre inválido.');
            $value = trim((string) ($input[$key] ?? ''));
            if (strlen($value) > $limit * 4) throw new ValidationException('Un campo de Mercado Libre es demasiado largo.');
            $result[$key] = $value;
        }
        if ($result['category_id'] !== '' && !preg_match('/^MLA\d+$/D', $result['category_id'])) {
            throw new ValidationException('La categoría de Mercado Libre debe ser un código MLA.');
        }
        if (!in_array($result['condition'], ['', 'new', 'used', 'not_specified'], true)
            || !in_array($result['shipping_mode'], ['', 'me2', 'me1', 'custom', 'not_specified'], true)) {
            throw new ValidationException('Condición o modalidad de envío inválida.');
        }
        $result['variant_id'] = max(0, (int) ($input['variant_id'] ?? 0));
        $result['publish_all_variants'] = ($input['publish_all_variants'] ?? false) === true;
        $result['size_grid_rows'] = [];
        $gridRows = $input['size_grid_rows'] ?? [];
        if (!is_array($gridRows) || count($gridRows) > 150) throw new ValidationException('Las filas de la guía de talles no son válidas.');
        foreach ($gridRows as $variantId => $rowId) {
            if (!ctype_digit((string) $variantId) || !is_string($rowId) || !preg_match('/^\d+:\d+$/D', $rowId)) {
                throw new ValidationException('La fila de la guía de talles de Mercado Libre no es válida.');
            }
            $result['size_grid_rows'][(int) $variantId] = $rowId;
        }
        foreach (['local_pick_up', 'free_shipping', 'package_confirmed'] as $key) {
            $result[$key] = ($input[$key] ?? false) === true;
        }
        foreach (['attributes', 'sale_terms'] as $key) {
            $values = $input[$key] ?? [];
            if (!is_array($values) || count($values) > 150) throw new ValidationException('Ficha técnica demasiado extensa.');
            $result[$key] = [];
            foreach ($values as $id => $value) {
                if (!preg_match('/^[A-Z][A-Z0-9_]{0,79}$/D', (string) $id) || !is_scalar($value) || strlen((string) $value) > 1020) {
                    throw new ValidationException('Atributo de Mercado Libre inválido.');
                }
                $result[$key][$id] = trim((string) $value);
            }
        }
        $pictures = $input['pictures'] ?? [];
        if (!is_array($pictures) || count($pictures) > 12) throw new ValidationException('Mercado Libre permite hasta 12 fotos en esta ficha.');
        $result['pictures'] = [];
        foreach ($pictures as $picture) {
            if (!is_string($picture) || strlen($picture) > 2048 || !filter_var($picture, FILTER_VALIDATE_URL)
                || !str_starts_with($picture, 'https://')) throw new ValidationException('Las fotos de Mercado Libre requieren URLs HTTPS.');
            $result['pictures'][] = $picture;
        }
        $result['pricing'] = null;
        if (isset($input['pricing'])) {
            try {
                $pricing = MercadoLibrePriceCalculator::inputs($input['pricing']);
            } catch (\RuntimeException $error) {
                throw new ValidationException($error->getMessage());
            }
            foreach (['price_cents', 'net_cents', 'sale_fee_cents', 'listing_fee_cents', 'other_percentage_cents', 'fixed_fee_cents'] as $key) {
                $value = filter_var($input['pricing'][$key] ?? 0, FILTER_VALIDATE_INT);
                if ($value === false || $value < 0 || $value > 2000000000) throw new ValidationException('Precio calculado inválido.');
                $pricing[$key] = $value;
            }
            $date = $input['pricing']['queried_at'] ?? '';
            if (!is_string($date) || strlen($date) > 40) throw new ValidationException('Fecha de cotización inválida.');
            $pricing['queried_at'] = $date;
            $context = $input['pricing']['context_key'] ?? '';
            if (!is_string($context) || strlen($context) > 2048) throw new ValidationException('Contexto del cálculo inválido.');
            $pricing['context_key'] = $context;
            $result['pricing'] = $pricing;
        }
        return $result;
    }

    public static function save(PDO $pdo, int $productId, ?array $draft): void
    {
        if ($draft === null) return; // Other callers leave the existing draft intact.
        $statement = $pdo->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP");
        $statement->execute(['key' => 'meli_product_' . $productId, 'value' => json_encode($draft, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
    }

    public static function all(PDO $pdo): array
    {
        $drafts = [];
        foreach ($pdo->query("SELECT key, value FROM settings WHERE key GLOB 'meli_product_[0-9]*'")->fetchAll() as $row) {
            $draft = json_decode($row['value'], true);
            if (is_array($draft)) $drafts[(int) substr($row['key'], 13)] = $draft;
        }
        return $drafts;
    }

    public static function publicationStates(PDO $pdo): array
    {
        $states = [];
        foreach ($pdo->query("SELECT key,value FROM settings WHERE key GLOB 'meli_listing_[0-9]*' OR key GLOB 'meli_publication_error_[0-9]*'") as $row) {
            $record = json_decode($row['value'], true);
            $id = (int) ($record['product_id'] ?? 0);
            if (!$id) continue;
            $states[$id] ??= ['state' => 'unpublished', 'items' => [], 'message' => ''];
            if (($record['state'] ?? '') === 'published') $states[$id]['items'][] = $record;
            if (in_array($record['state'] ?? '', ['pending', 'failed'], true)) {
                $states[$id]['message'] = $record['message'] ?? 'Hay un envío pendiente de verificar en MeLi.';
            }
        }
        foreach ($states as &$state) $state['state'] = $state['message'] !== '' ? 'failed' : ($state['items'] ? 'published' : 'unpublished');
        unset($state);
        return $states;
    }
}
