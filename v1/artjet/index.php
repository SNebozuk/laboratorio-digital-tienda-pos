<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$products = $app['artjet']->publicProducts();
$product = $products[0] ?? null;
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$money = static fn (int $cents): string => '$' . number_format($cents / 100, 0, ',', '.');
if ($product !== null) {
    $variants = array_values(array_filter($product['variants'], static fn (array $variant): bool => $variant['active']));
    $variant = $variants[0] ?? null;
    $image = (string) ($product['primary_image_path'] ?: $product['ld_image_path']);
    $stock = array_sum(array_map(static fn (array $item): int => (int) $item['stock_on_hand'], $variants));
    $technical = array_values(array_filter(preg_split('/\r?\n/', (string) $product['technical_info']) ?: []));
}
?><!doctype html>
<html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Artjet · Papeles que dejan huella</title>
    <link rel="stylesheet" href="assets/artjet.css?v=2">
</head><body>
<header class="artjet-header"><a href="#inicio" class="artjet-logo">ART<span>JET</span></a><nav><a href="#producto">PAPELES</a><a href="#material">MATERIAL</a></nav><span class="artjet-preview-label">TIENDA DE PRUEBA</span></header>
<main id="inicio">
<?php if ($product === null): ?>
    <section class="artjet-empty"><p>ARTJET / LABORATORIO DIGITAL</p><h1>La primera pieza<br>está por llegar.</h1><span>Publicá el producto de prueba desde el administrador para ver esta experiencia.</span></section>
<?php else: ?>
    <section class="artjet-hero">
        <div class="artjet-hero-copy"><p>SUPERFICIES QUE CUENTAN HISTORIAS</p><h1>Imprimí<br><em>para pegar.</em></h1><span>Papel fotográfico adhesivo diseñado para que las ideas no se queden en pantalla.</span><a href="#producto">DESCUBRIR MATERIAL <b>↓</b></a></div>
        <div class="artjet-hero-image"><i></i><img src="<?= $escape($image) ?>" alt="<?= $escape($product['store_title']) ?>"></div>
    </section>
    <section class="artjet-marquee" aria-label="Características"><span>BRILLANTE</span><b>✳</b><span>AUTOADHESIVO</span><b>✳</b><span>ALTA RESOLUCIÓN</span><b>✳</b><span>ARTJET</span></section>
    <section class="artjet-product" id="producto">
        <div class="artjet-product-image"><div class="artjet-image-note">PAPEL / 115G<br>HECHO PARA IMPRIMIR</div><img src="<?= $escape($image) ?>" alt="<?= $escape($product['store_title']) ?>"></div>
        <div class="artjet-product-copy"><p>01 / PAPELES</p><h2><?= $escape($product['store_title']) ?></h2><div class="artjet-price"><strong><?= $variant ? $escape($money((int) $variant['price_cents'])) : 'Consultar' ?></strong><span class="<?= $stock > 0 ? 'is-available' : '' ?>"><?= $stock > 0 ? 'Disponible' : 'Sin stock' ?></span></div><p class="artjet-description"><?= $escape($product['store_description']) ?></p><div class="artjet-actions"><button type="button" disabled><?= $stock > 0 ? 'PRÓXIMAMENTE' : 'SIN STOCK' ?></button><small>Precio y disponibilidad de Laboratorio Digital</small></div></div>
    </section>
    <section class="artjet-details" id="material"><div><p>HECHO PARA</p><h2>Que cada detalle<br>quede en su lugar.</h2></div><ul><?php foreach ($technical as $item): ?><li><?= $escape($item) ?></li><?php endforeach ?></ul></section>
    <section class="artjet-statement"><p>ARTJET / <?= $escape($product['artjet_subcategory']) ?></p><h2>Una superficie simple.<br>Un resultado <em>imposible</em><br>de ignorar.</h2></section>
<?php endif; ?>
</main>
<footer><strong>ART<span>JET</span></strong><span>Precio, stock e imágenes alojados por Laboratorio Digital.</span></footer>
</body></html>
