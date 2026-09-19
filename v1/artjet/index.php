<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$products = $app['products']->publicCatalog();
$artjetImages = $app['artjet']->imagePaths();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$money = static fn (int $cents): string => '$' . number_format($cents / 100, 0, ',', '.');
$featured = $products[0] ?? null;
?>
<!doctype html>
<html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Artjet · Materiales que dejan huella</title>
    <link rel="stylesheet" href="assets/artjet.css?v=3">
</head><body>
<header class="artjet-header"><a href="#inicio" class="artjet-logo">ART<span>JET</span></a><nav><a href="#catalogo">CATÁLOGO</a><a href="#material">MATERIAL</a></nav><span class="artjet-preview-label">TIENDA ONLINE</span></header>
<main id="inicio">
<?php if ($featured === null): ?>
    <section class="artjet-empty"><p>ARTJET / LABORATORIO DIGITAL</p><h1>El catálogo<br>está por llegar.</h1><span>Los productos se publicarán desde Laboratorio Digital.</span></section>
<?php else: ?>
    <section class="artjet-hero">
        <div class="artjet-hero-copy"><p>FORMATOS PARA CREAR</p><h1>Materiales<br><em>con impacto.</em></h1><span>Una selección completa de Laboratorio Digital, presentada con la identidad visual de Artjet.</span><a href="#catalogo">VER CATÁLOGO <b>↓</b></a></div>
        <div class="artjet-hero-image"><i></i><?php $featuredImage = $artjetImages[(int) $featured['id']] ?? $featured['image_path']; if (!empty($featuredImage)): ?><img src="<?= $escape((string) $featuredImage) ?>" alt="<?= $escape((string) $featured['name']) ?>"><?php endif; ?></div>
    </section>
    <section class="artjet-marquee" aria-label="Catálogo"><span>FORMATOS</span><b>✳</b><span>COLOR</span><b>✳</b><span>CALIDAD</span><b>✳</b><span>ARTJET</span></section>
    <section class="artjet-catalog" id="catalogo">
        <div class="artjet-catalog-heading"><p>CATÁLOGO COMPLETO</p><h2>Todo Laboratorio Digital.<br><em>Una nueva mirada.</em></h2><span><?= count($products) ?> productos disponibles</span></div>
        <div class="artjet-product-grid">
            <?php foreach ($products as $index => $product):
                $variants = $product['variants'];
                $firstVariant = $variants[0] ?? null;
                $hasStock = array_filter($variants, static fn (array $variant): bool => $variant['available_stock'] === null || (int) $variant['available_stock'] > 0) !== [];
                $price = $firstVariant['price_cents'] ?? null;
                $isA4 = preg_match('/\bA4\b/i', (string) $product['name']) === 1;
                $image = $artjetImages[(int) $product['id']] ?? $product['image_path'];
            ?>
                <article class="artjet-card">
                    <div class="artjet-card-image<?= $isA4 ? ' is-a4' : '' ?>"><?php if (!empty($image)): ?><img src="<?= $escape((string) $image) ?>" alt="<?= $escape((string) $product['name']) ?>"><?php else: ?><span>SIN IMAGEN</span><?php endif; ?></div>
                    <div class="artjet-card-copy"><p><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?> / <?= $escape((string) $product['category']['name']) ?></p><h3><?= $escape((string) $product['name']) ?></h3><div><strong><?= $price !== null ? $escape($money((int) $price)) : 'Consultar' ?></strong><span class="<?= $hasStock ? 'is-available' : '' ?>"><?= $hasStock ? 'Disponible' : 'Sin stock' ?></span></div><?php if (!empty($product['description'])): ?><small><?= $escape((string) $product['description']) ?></small><?php endif; ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="artjet-details" id="material"><div><p>INFORMACIÓN ACTUALIZADA</p><h2>Precio y stock<br>siempre <em>reales.</em></h2></div><ul><li>Productos, variantes y descripciones de Laboratorio Digital</li><li>Imágenes alojadas en nuestro propio servidor</li><li>Disponibilidad actualizada desde el mismo catálogo</li></ul></section>
<?php endif; ?>
</main>
<footer><strong>ART<span>JET</span></strong><span>Catálogo, precio, stock e imágenes de Laboratorio Digital.</span></footer>
</body></html>
