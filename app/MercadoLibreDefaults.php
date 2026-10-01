<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

require_once __DIR__ . '/MercadoLibrePriceCalculator.php';

final class MercadoLibreDefaults
{
    public static function seed(): array
    {
        return ['condition' => 'new', 'listing_type_id' => 'gold_special', 'shipping_mode' => 'me2',
            'logistic_type' => 'xd_drop_off', 'local_pick_up' => false, 'free_shipping' => false,
            'publish_all_variants' => true, 'rounding_pesos' => 100, 'installments' => true, 'financing_max_percent' => 5,
            'packaging_cents' => 0, 'shipping_cents' => 0, 'other_fixed_cents' => 0, 'other_percentage' => 0];
    }

    public static function normalize(array $input): array
    {
        $result = self::seed();
        foreach (['condition' => ['new', 'used', 'not_specified'], 'listing_type_id' => ['gold_special', 'gold_pro', 'free'],
            'shipping_mode' => ['me2', 'me1', 'custom', 'not_specified']] as $key => $allowed) {
            if (!in_array($input[$key] ?? null, $allowed, true)) throw new \RuntimeException('Revisá las opciones generales de MeLi.');
            $result[$key] = $input[$key];
        }
        if (!is_string($input['logistic_type'] ?? null) || !preg_match('/^[a-z_]{1,40}$/D', $input['logistic_type'])) throw new \RuntimeException('Elegí una logística válida.');
        $result['logistic_type'] = $input['logistic_type'];
        foreach (['local_pick_up', 'free_shipping', 'publish_all_variants', 'installments'] as $key) {
            if (!is_bool($input[$key] ?? null)) throw new \RuntimeException('Opción general inválida.');
            $result[$key] = $input[$key];
        }
        if (!in_array($input['rounding_pesos'] ?? null, [1, 10, 100], true)) throw new \RuntimeException('Redondeo inválido.');
        $result['rounding_pesos'] = $input['rounding_pesos'];
        $limit = $input['financing_max_percent'] ?? null;
        if (!is_numeric($limit) || !is_finite((float) $limit) || (float) $limit <= 0 || (float) $limit > 5) throw new \RuntimeException('El costo de cuotas a absorber debe ser mayor a cero y como máximo 5 %.');
        $result['financing_max_percent'] = (float) $limit;
        $costs = MercadoLibrePriceCalculator::inputs(['base_price_cents' => 1, 'billable_weight' => 1] + $input);
        foreach (['packaging_cents', 'shipping_cents', 'other_fixed_cents', 'other_percentage'] as $key) $result[$key] = $costs[$key];
        if ($result['installments'] && ($result['listing_type_id'] !== 'gold_special' || $result['condition'] !== 'new')) throw new \RuntimeException('Las cuotas de 3 a 12 requieren publicación Clásica y producto nuevo.');
        return $result;
    }

    public static function read(PDO $pdo): array
    {
        $raw = $pdo->query("SELECT value FROM settings WHERE key='meli_defaults'")->fetchColumn();
        return ['configured' => $raw !== false] + ($raw === false ? self::seed() : self::normalize(json_decode((string) $raw, true)));
    }

    public static function save(PDO $pdo, array $input): array
    {
        $values = self::normalize($input);
        $q = $pdo->prepare("INSERT INTO settings(key,value) VALUES('meli_defaults',?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=CURRENT_TIMESTAMP");
        $q->execute([json_encode($values, JSON_THROW_ON_ERROR)]);
        return ['configured' => true] + $values;
    }

    public static function apply(PDO $pdo, ?array $draft): ?array
    {
        if ($draft === null) return null;
        $values = self::read($pdo);
        if (!$values['configured']) return $draft;
        foreach (['condition', 'listing_type_id', 'shipping_mode', 'logistic_type', 'local_pick_up', 'free_shipping', 'publish_all_variants', 'installments', 'financing_max_percent'] as $key) $draft[$key] = $values[$key];
        if (isset($draft['pricing'])) foreach (['rounding_pesos', 'packaging_cents', 'shipping_cents', 'other_fixed_cents', 'other_percentage'] as $key) $draft['pricing'][$key] = $values[$key];
        return $draft;
    }
}
