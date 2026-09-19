<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$products = $app['artjet']->publicProducts();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$price = static function (array $product): string {
    $prices = array_column(array_filter($product['variants'], static fn (array $variant): bool => $variant['active']), 'price_cents');
    if ($prices === []) return 'Consultar';
    $minimum = min($prices);
    $maximum = max($prices);
    $format = static fn (int $cents): string => '$' . number_format($cents / 100, 0, ',', '.');
    return $minimum === $maximum ? $format($minimum) : $format($minimum) . ' — ' . $format($maximum);
};
$stock = static fn (array $product): int => array_sum(array_map(static fn (array $variant): int => $variant['active'] ? (int) $variant['stock_on_hand'] : 0, $product['variants']));
?><!doctype html>
<html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Artjet · Maqueta de tienda</title>
    <link rel="stylesheet" href="assets/artjet.css?v=1">
</head><body>
<header class="artjet-header"><a href="#inicio" class="artjet-logo">ART<span>JET</span></a><nav><a href="#papeles">PAPELES</a><a href="#tintas">TINTAS</a></nav><span class="artjet-preview-label">MAQUETA PRIVADA</span></header>
<main id="inicio">
    <section class="artjet-hero"><p>INSUMOS PARA CREAR SIN LÍMITES</p><h1>El color empieza<br>con una gran idea.</h1><span>Papeles y tintas Art‑Jet disponibles en Laboratorio Digital.</span></section>
    <?php foreach (['Papeles', 'Tintas'] as $category): ?>
        <section class="artjet-category" id="<?= strtolower($category) ?>"><header><p>COLECCIÓN</p><h2><?= $category ?></h2></header><div class="artjet-products">
        <?php foreach (array_filter($products, static fn (array $product): bool => $product['artjet_category'] === $category) as $index => $product):
            $image = $product['primary_image_path'] ?: $product['ld_image_path'];
            $available = $stock($product);
        ?><article class="artjet-product <?= $index % 5 === 0 ? 'artjet-product-featured' : '' ?>">
            <div class="artjet-product-image"><?php if ($image !== ''): ?><img src="<?= $escape($image) ?>" alt="<?= $escape($product['store_title']) ?>"><?php endif ?></div>
            <div class="artjet-product-copy"><small><?= $escape($product['artjet_subcategory']) ?></small><h3><?= $escape($product['store_title']) ?></h3><div><strong><?= $escape($price($product)) ?></strong><span class="<?= $available > 0 ? 'is-available' : '' ?>"><?= $available > 0 ? 'Disponible' : 'Sin stock' ?></span></div></div>
        </article><?php endforeach ?>
        </div></section>
    <?php endforeach ?>
</main>
<footer><strong>ARTJET</strong><span>Maqueta visual · precios y stock de Laboratorio Digital</span></footer>
</body></html>
