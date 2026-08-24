<?php
declare(strict_types=1);
$app = require dirname(__DIR__) . '/app/container.php';
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$apiUrl = $storePath . '/api.php';
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$quoteAssetsVersion = (string) max(
    filemtime(__DIR__ . '/assets/quote.js') ?: 0,
    filemtime(__DIR__ . '/assets/quote-carousel.css') ?: 0
);
$quoteCatalog = [
    'products' => $app['products']->publicCatalog(),
    'categories' => $app['categories']->tree(),
    'quote_settings' => $app['settings']->quote(),
];
$paperCategoryIds = [];
$collectPaperCategories = static function (array $category) use (&$collectPaperCategories, &$paperCategoryIds): void {
    $paperCategoryIds[(int) $category['id']] = true;
    foreach ($category['children'] ?? [] as $child) $collectPaperCategories($child);
};
foreach ($quoteCatalog['categories'] as $category) {
    if (stripos((string) $category['name'], 'papel') !== false) $collectPaperCategories($category);
}
$quotePapers = [];
foreach ($quoteCatalog['products'] as $product) {
    if (!isset($paperCategoryIds[(int) ($product['category']['id'] ?? 0)])) continue;
    foreach ($product['variants'] as $variant) {
        if (($variant['price_cents'] ?? null) === null) continue;
        $quotePapers[] = ['product_name' => $product['name'], 'variant_name' => $variant['name'], 'variant_id' => $variant['id'], 'price_cents' => $variant['price_cents']];
    }
}
$quoteAtelier = array_values(array_filter($quoteCatalog['products'], static fn (array $product): bool => stripos((string) $product['name'], 'atelier') !== false));
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; connect-src 'self'; img-src 'self' https: data:; base-uri 'self'; form-action 'self'");
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cotizador para Emprendedores · Laboratorio Digital</title><link rel="icon" href="<?= $escape($assetPath) ?>/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="<?= $escape($assetPath) ?>/quote.css?v=<?= $quoteAssetsVersion ?>"><link rel="stylesheet" href="<?= $escape($assetPath) ?>/quote-table.css?v=<?= $quoteAssetsVersion ?>"><link rel="stylesheet" href="<?= $escape($assetPath) ?>/quote-carousel.css?v=<?= $quoteAssetsVersion ?>"></head>
<body><main class="quote-shell"><a class="quote-back" href="<?= $escape($storePath ?: '/') ?>">← Volver a Laboratorio Digital</a><header class="quote-hero"><span>✦ HERRAMIENTA ATELIER</span><h1>Tu idea, <em>bien cotizada.</em></h1><p>Calculá cuánto papel necesita y cuánto cuesta tu trabajo de tarjetería o papelería.</p></header>
<section class="quote-card"><div class="quote-step"><b>1</b><div><h2>Elegí tu papel</h2><p>Elegí entre los papeles disponibles en Laboratorio Digital.</p></div></div><details id="paper-picker" class="paper-picker"><summary>Seleccioná un papel <span>⌄</span></summary><div id="paper-list" class="paper-list"><table class="paper-table"><thead><tr><th>Papel</th><th>Precio resma</th></tr></thead><tbody><?php foreach ($quotePapers as $paper): ?><tr data-id="<?= (int) $paper['variant_id'] ?>" data-product="<?= $escape((string) $paper['product_name']) ?>" data-variant="<?= $escape((string) $paper['variant_name']) ?>" data-price="<?= (int) $paper['price_cents'] ?>"><td><?= $escape((string) $paper['product_name']) ?><?= $paper['variant_name'] ? ' · ' . $escape((string) $paper['variant_name']) : '' ?></td><td>$ <?= number_format((int) $paper['price_cents'] / 100, 0, ',', '.') ?></td></tr><?php endforeach ?></tbody></table></div></details><p id="paper-selected" class="paper-selected" hidden></p></section>
<section class="quote-guide"><strong>Medidas de referencia</strong><span>A4 · 21 × 29,7 cm</span><span>A3 · 29,7 × 42 cm</span><span>A3+ · 32,9 × 48,3 cm</span><span>A6 / 4R · 10 × 15 cm</span></section>
<section class="quote-card quote-project"><div class="quote-step"><b>2</b><div><h2>Contanos tu proyecto</h2><p>Usamos el tamaño real del papel elegido para aprovecharlo al máximo.</p></div></div><div class="quote-fields"><label>Cantidad de piezas<input id="project-quantity" type="number" min="1" value="200"></label><label>Ancho de cada pieza (cm)<input id="project-width" type="number" min="0.1" step="0.1" value="10"></label><label>Alto de cada pieza (cm)<input id="project-height" type="number" min="0.1" step="0.1" value="15"></label><label>Margen de corte (mm)<input id="cut-margin" type="number" min="0" step="0.5" value="0"></label></div><button id="calculate-quote" class="quote-calculate" type="button">Calcular costo del papel</button></section>
<dialog id="quote-modal" class="quote-modal" aria-labelledby="quote-modal-title"><section id="quote-result" class="quote-result" aria-live="polite"><h2 id="quote-modal-title">Tu cotización</h2></section><button id="close-quote" class="quote-calculate" type="button">Volver al cotizador</button></dialog>
<section id="related-products" class="related-products" aria-label="Productos Atelier"><div class="related-track"><?php foreach ($quoteAtelier as $product): $variant = $product['variants'][0] ?? null; if (!$variant || $variant['price_cents'] === null) continue; ?><a class="related-card" href="<?= $escape($storeUrl) ?>?producto=<?= (int) $product['id'] ?>"><?php if ($product['image_path']): ?><img src="<?= $escape((string) $product['image_path']) ?>" alt=""><?php endif ?><strong><?= $escape((string) $product['name']) ?></strong><small>$ <?= number_format((int) $variant['price_cents'] / 100, 0, ',', '.') ?></small></a><?php endforeach ?></div></section></main><script>window.quoteApp={storeUrl:<?= json_encode($storeUrl) ?>,catalog:<?= json_encode($quoteCatalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,papers:<?= json_encode($quotePapers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>};</script><script src="<?= $escape($assetPath) ?>/quote.js?v=<?= $quoteAssetsVersion ?>"></script></body></html>
