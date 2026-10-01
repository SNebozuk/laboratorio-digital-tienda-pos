<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class MercadoLibrePriceCalculator
{
    public static function inputs(mixed $input): array
    {
        if (!is_array($input)) throw new \RuntimeException('Ingresá los gastos para calcular el precio.');
        $result = [];
        foreach (['base_price_cents', 'packaging_cents', 'shipping_cents', 'other_fixed_cents'] as $key) {
            $value = filter_var($input[$key] ?? 0, FILTER_VALIDATE_INT);
            if ($value === false || $value < 0 || $value > 1000000000) throw new \RuntimeException('Los importes deben ser positivos y expresados en centavos.');
            $result[$key] = $value;
        }
        if ($result['base_price_cents'] < 1) throw new \RuntimeException('La variante necesita un precio mayor a cero.');
        $percentage = $input['other_percentage'] ?? 0;
        $weight = $input['billable_weight'] ?? 0;
        if (!is_numeric($percentage) || !is_finite((float) $percentage) || (float) $percentage < 0 || (float) $percentage >= 100
            || !is_numeric($weight) || !is_finite((float) $weight) || (float) $weight <= 0 || (float) $weight > 1000000) {
            throw new \RuntimeException('Revisá el porcentaje adicional y el peso facturable en gramos.');
        }
        $result['other_percentage'] = (float) $percentage;
        $result['billable_weight'] = (float) $weight;
        $rounding = filter_var($input['rounding_pesos'] ?? 100, FILTER_VALIDATE_INT);
        if (!in_array($rounding, [1, 10, 100], true)) throw new \RuntimeException('El redondeo debe ser de 1, 10 o 100 pesos.');
        $result['rounding_pesos'] = $rounding;
        return $result;
    }

    /** Requotes the final price so fee thresholds cannot leave the seller short. */
    public static function calculate(array $input, callable $quote): array
    {
        $input = self::inputs($input);
        $target = $input['base_price_cents'];
        $fixed = $input['packaging_cents'] + $input['shipping_cents'] + $input['other_fixed_cents'];
        $otherRate = $input['other_percentage'] / 100;
        $step = $input['rounding_pesos'] * 100;
        $price = (int) (ceil(($target + $fixed) / (1 - $otherRate) / $step) * $step);
        for ($attempt = 0; $attempt < 12; $attempt++) {
            if ($price > 1000000000) throw new \RuntimeException('El precio calculado supera el límite permitido.');
            $fees = $quote($price);
            foreach (['sale_fee_amount', 'listing_fee_amount'] as $key) {
                if (!isset($fees[$key]) || !is_numeric($fees[$key]) || !is_finite((float) $fees[$key]) || (float) $fees[$key] < 0) {
                    throw new \RuntimeException('Mercado Libre no devolvió cargos válidos. No se calculó un precio.');
                }
            }
            $saleFee = (int) round((float) $fees['sale_fee_amount'] * 100);
            $listingFee = (int) round((float) $fees['listing_fee_amount'] * 100);
            $otherFee = (int) ceil($price * $otherRate);
            $net = $price - $saleFee - $listingFee - $otherFee - $fixed;
            if ($net >= $target) {
                return $input + ['price_cents' => $price, 'net_cents' => $net, 'sale_fee_cents' => $saleFee,
                    'listing_fee_cents' => $listingFee, 'other_percentage_cents' => $otherFee,
                    'fixed_fee_cents' => (int) round((float) ($fees['sale_fee_details']['fixed_fee'] ?? 0) * 100),
                    'queried_at' => gmdate('c')];
            }
            // percentage_fee includes financing; fixed_fee is already in sale_fee_amount.
            $percentage = $fees['sale_fee_details']['percentage_fee'] ?? null;
            $rate = is_numeric($percentage) ? (float) $percentage / 100 : null;
            if ($rate !== null && ($rate < 0 || $rate + $otherRate >= 1)) {
                throw new \RuntimeException('Las comisiones y porcentajes suman 100 % o más. Revisá los gastos.');
            }
            $next = ($target + $fixed + $saleFee + $listingFee) / (1 - $otherRate);
            if ($rate !== null) {
                $constantFee = max(0, $saleFee - $price * $rate);
                $next = ($target + $fixed + $constantFee + $listingFee) / (1 - $rate - $otherRate);
            }
            $price = max($price + $step, (int) (ceil($next / $step) * $step));
        }
        throw new \RuntimeException('Los cargos cambiaron durante el cálculo. Volvé a consultar antes de fijar el precio.');
    }
}
