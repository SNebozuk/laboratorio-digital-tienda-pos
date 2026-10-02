<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/MercadoLibreShirtChart.php';
use LaboratorioDigital\MercadoLibreShirtChart as Chart;
function check(bool $value): void { if (!$value) throw new RuntimeException('Prueba fallida'); }
$variants = [['id' => 12, 'name' => 'Talle 1'], ['id' => 13, 'name' => 'Talle 3']];
$guide = [['group' => 'Unisex', 'size' => '1', 'width' => '46 cm', 'length' => '66 cm'],
    ['group' => 'Unisex', 'size' => '3', 'width' => '53', 'length' => '69']];
$rows = Chart::measurements($variants, $guide, 'Unisex');
check($rows[1]['width'] === 53.0 && $rows[1]['size'] === '3');
$template = ['groups' => [['attributes' => [
    ['id' => 'SIZE', 'value_type' => 'string'],
    ['id' => 'GARMENT_CHEST_WIDTH_FROM', 'value_type' => 'number_unit'],
    ['id' => 'GARMENT_LENGTH_FROM', 'value_type' => 'number_unit'],
    ['id' => 'FILTRABLE_SIZE', 'value_type' => 'list', 'tags' => ['required'], 'values' => [['id' => '111', 'name' => '1'], ['id' => '333', 'name' => '3']]],
]]]];
$payload = Chart::payload($rows, $template, []);
check($payload['measure_type'] === 'CLOTHING_MEASURE');
check($payload['rows'][1]['attributes'][1]['values'][0]['struct']['number'] === 53.0);
check($payload['rows'][1]['attributes'][3]['values'][0]['id'] === '333');
$chart = ['id' => '321', 'main_attribute_id' => 'SIZE', 'rows' => [
    ['id' => '321:4', 'attributes' => [['id' => 'SIZE', 'values' => [['name' => '3']]]]],
    ['id' => '321:2', 'attributes' => [['id' => 'SIZE', 'values' => [['name' => '1']]]]],
]];
check(Chart::assignments($variants, $chart) === [12 => '321:2', 13 => '321:4']);
$template['groups'][0]['attributes'][3]['values'] = [['id' => '999', 'name' => 'M']];
try { Chart::payload($rows, $template, []); throw new LogicException('Se inventó equivalencia'); }
catch (RuntimeException $e) { check(str_contains($e->getMessage(), 'No se inventa')); }
$payload = Chart::payload($rows, $template, [], [12 => 'M', 13 => 'M']);
check($payload['rows'][0]['attributes'][0]['values'][0]['name'] === '1');
check($payload['rows'][0]['attributes'][3]['values'][0]['id'] === '999');
$buzo = Chart::payload($rows, $template, [], [12 => 'M', 13 => 'M'], 'SWEATSHIRTS_AND_HOODIES', 'Buzos cuello redondo');
check($buzo['domain_id'] === 'SWEATSHIRTS_AND_HOODIES' && $buzo['names']['MLA'] === 'Buzos cuello redondo');
check($buzo['rows'][1]['attributes'][0]['values'][0]['name'] === '3');
echo "Guía de remeras: medidas, filas y equivalencias verificadas.\n";
