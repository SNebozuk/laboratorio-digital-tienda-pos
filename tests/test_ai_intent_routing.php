<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/CatalogAiChatService.php';

use LaboratorioDigital\CatalogAiChatService;

$service = (new ReflectionClass(CatalogAiChatService::class))->newInstanceWithoutConstructor();
$route = new ReflectionMethod(CatalogAiChatService::class, 'intentRoute');

$cases = [
    [['intent' => 'product_search', 'confidence' => 'high', 'core_product_term' => 'papel', 'store_topics' => []], 'catalog'],
    [['intent' => 'store_information', 'confidence' => 'high', 'core_product_term' => '', 'store_topics' => ['payment']], 'store'],
    [['intent' => 'mixed', 'confidence' => 'high', 'core_product_term' => 'papel', 'store_topics' => ['shipping']], 'mixed'],
    [['intent' => 'ambiguous', 'confidence' => 'high'], 'clarify'],
    [['intent' => 'product_search', 'confidence' => 'medium', 'core_product_term' => 'papel'], 'clarify'],
    [['intent' => 'store_information', 'confidence' => 'low', 'store_topics' => ['payment']], 'clarify'],
    [['intent' => 'store_information', 'confidence' => 'high', 'core_product_term' => 'papel', 'store_topics' => ['payment']], 'clarify'],
    [['intent' => 'product_search', 'confidence' => 'high', 'core_product_term' => '', 'store_topics' => []], 'clarify'],
    [['intent' => 'unsupported', 'confidence' => 'high'], 'unsupported'],
];

foreach ($cases as [$interpretation, $expected]) {
    $actual = $route->invoke($service, $interpretation);
    if ($actual !== $expected) {
        fwrite(STDERR, 'Expected ' . $expected . ', got ' . $actual . PHP_EOL);
        exit(1);
    }
}

$exactRows = new ReflectionMethod(CatalogAiChatService::class, 'exactRequestedRows');
$paperRows = [[
    'producto' => 'MATELINA TEXTURADO A4 230G LINO NATURAL',
    'variante' => 'Única',
    'categoria' => 'PAPELES',
    'descripcion' => '',
    'variante_id' => 1,
    'talle' => null,
]];
$paperRequest = [
    'mode' => 'new',
    'core_product_term' => 'papel mate texturado',
    'product_requests' => [[
        'product_term' => 'papel mate texturado',
        'talle' => null,
        'color' => null,
        'material' => null,
        'gramaje' => null,
        'tamano' => null,
        'tipo_papel' => null,
    ]],
];
if (count($exactRows->invoke($service, $paperRows, $paperRequest, [['role' => 'user', 'content' => 'Busco papel mate texturado']])) !== 1) {
    fwrite(STDERR, "Expected the textured matte paper request to match the Matelina catalog row.\n");
    exit(1);
}

echo "AI intent routing tests passed.\n";
