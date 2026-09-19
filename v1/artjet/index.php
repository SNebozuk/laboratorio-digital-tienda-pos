<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$products = array_values(array_filter(
    $app['products']->publicCatalog(),
    static fn (array $product): bool => preg_match('/\bART-?JET\b/i', (string) $product['name']) === 1
));
$artjetImages = $app['artjet']->imagePaths();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$money = static fn (int $cents): string => '$' . number_format($cents / 100, 0, ',', '.');
$upper = static fn (string $value): string => function_exists('mb_strtoupper')
    ? mb_strtoupper($value)
    : strtoupper(strtr($value, ['á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ñ' => 'Ñ']));
$categoryFor = static function (string $name): string {
    $name = strtoupper($name);
    return match (true) {
        str_starts_with($name, 'TINTA') => 'Tintas',
        str_contains($name, 'FILMILO') => 'Filmilo',
        str_contains($name, 'HOLOFAN') => 'Holofan',
        str_contains($name, 'MATELINA') => 'Matelina',
        str_contains($name, 'SUBLIMACION'), str_contains($name, 'SUBLISTICK') => 'Sublimación',
        str_contains($name, 'DURALITE'), str_contains($name, 'TATUFAN'), str_contains($name, 'WINKY') => 'Especialidades',
        default => 'Fotográficos',
    };
};
$categoryOrder = ['Fotográficos', 'Matelina', 'Filmilo', 'Holofan', 'Sublimación', 'Especialidades', 'Tintas'];
$categorySlugs = [
    'Fotográficos' => 'fotograficos', 'Matelina' => 'matelina', 'Filmilo' => 'filmilo',
    'Holofan' => 'holofan', 'Sublimación' => 'sublimacion', 'Especialidades' => 'especialidades', 'Tintas' => 'tintas',
];
$grouped = array_fill_keys($categoryOrder, []);
foreach ($products as $product) {
    $grouped[$categoryFor((string) $product['name'])][] = $product;
}
$grouped = array_filter($grouped);
$featured = null;
foreach ($products as $product) {
    if (str_contains(strtoupper((string) $product['name']), 'PAPEL FOTOGRAFICO A4 200G')) {
        $featured = $product;
        break;
    }
}
$featured ??= $products[0] ?? null;
$displayName = static fn (string $name): string => trim((string) preg_replace('/\s+ART-?JET\b/iu', '', $name));
$formatFor = static function (string $name): string {
    preg_match_all('/\b(?:A3\+|A3|A4|A6|10\s*[X×]\s*15|\d+\s*CC|\d+\s*G|\d+\s*MICRONES?)\b/iu', $name, $matches);
    $parts = array_values(array_unique(array_map(
        static fn (string $part): string => preg_replace('/\s+/', ' ', $part) ?? $part,
        $matches[0] ?? []
    )));
    return implode(' · ', $parts);
};
?>
<!doctype html>
<html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Artjet · Materiales para crear</title>
    <link rel="stylesheet" href="assets/artjet.css?v=4">
    <link rel="stylesheet" href="assets/artjet-cards.css?v=1">
</head><body>
<header class="artjet-header">
    <a href="#inicio" class="artjet-logo" aria-label="Artjet">ART<span>JET</span></a>
    <nav><a href="#papeles">PAPELES</a><a href="#tintas">TINTAS</a><a href="#catalogo">CATÁLOGO</a></nav>
    <div class="artjet-header-actions"><a href="/">TIENDA</a><a href="/cotizador.php">COTIZADOR</a><a class="artjet-header-action" href="#catalogo">EXPLORAR <span>↘</span></a></div>
</header>
<main id="inicio">
<?php if ($featured === null): ?>
    <section class="artjet-empty"><p>ARTJET / LABORATORIO DIGITAL</p><h1>El catálogo<br>está por llegar.</h1><span>Los productos se publicarán desde Laboratorio Digital.</span></section>
<?php else:
    $featuredImage = $artjetImages[(int) $featured['id']] ?? $featured['image_path'];
?>
    <section class="artjet-hero">
        <div class="artjet-hero-copy"><p>PAPELES · TINTAS · SUPERFICIES</p><h1>Creá.<br><em>Imprimí.</em><br>Dejá huella.</h1><span>Materiales Art‑Jet para transformar una idea en algo que se puede tocar.</span><a href="#categorias">DESCUBRIR COLECCIONES <b>↓</b></a></div>
        <div class="artjet-hero-visual"><div class="artjet-hero-word">MATERIAL</div><i></i><?php if (!empty($featuredImage)): ?><img src="<?= $escape((string) $featuredImage) ?>" alt="<?= $escape((string) $featured['name']) ?>"><?php endif; ?><span>FORMATO A4<br>ESCALA VISUAL 1:2</span></div>
    </section>
    <section class="artjet-marquee" aria-label="Identidad Artjet"><span>ALTA DEFINICIÓN</span><b>✳</b><span>COLOR QUE IMPACTA</span><b>✳</b><span>SUPERFICIES QUE INSPIRAN</span><b>✳</b><span>ARTJET</span></section>

    <section class="artjet-intro" id="categorias">
        <p>COLECCIONES</p><h2>Un material para<br>cada <em>idea.</em></h2><span><?= count($products) ?> productos Art‑Jet disponibles con precio y stock de Laboratorio Digital.</span>
    </section>
    <section class="artjet-editorial-categories">
        <a class="artjet-editorial-card artjet-editorial-paper" id="papeles" href="#fotograficos"><img src="/uploads/artjet/editorial/artjet-papeles.jpg" alt="Aplicaciones realizadas con papeles Art-Jet"><span>01 / PAPELES</span><h3>Imágenes que<br>se vuelven objeto.</h3><b>VER PAPELES ↘</b></a>
        <a class="artjet-editorial-card artjet-editorial-ink" id="tintas" href="#tintas-productos"><img src="/uploads/artjet/editorial/artjet-tintas.jpg" alt="Tintas Art-Jet"><span>02 / TINTAS</span><h3>Color preciso.<br>Impacto real.</h3><b>VER TINTAS ↘</b></a>
    </section>
    <nav class="artjet-category-nav" aria-label="Categorías Artjet">
        <?php foreach ($grouped as $category => $items): ?><a href="#<?= $escape($category === 'Tintas' ? 'tintas-productos' : $categorySlugs[$category]) ?>"><span><?= str_pad((string) count($items), 2, '0', STR_PAD_LEFT) ?></span><?= $escape($upper($category)) ?></a><?php endforeach; ?>
    </nav>

    <section class="artjet-catalog" id="catalogo">
        <?php foreach ($grouped as $category => $items):
            $slug = $category === 'Tintas' ? 'tintas-productos' : $categorySlugs[$category];
        ?>
            <section class="artjet-collection" id="<?= $escape($slug) ?>">
                <header><p>COLECCIÓN / <?= $escape($upper($category)) ?></p><h2><?= $escape($category) ?><sup><?= str_pad((string) count($items), 2, '0', STR_PAD_LEFT) ?></sup></h2></header>
                <div class="artjet-product-grid">
                    <?php foreach ($items as $index => $product):
                        $variants = $product['variants'];
                        $firstVariant = $variants[0] ?? null;
                        $hasStock = array_filter($variants, static fn (array $variant): bool => $variant['available_stock'] === null || (int) $variant['available_stock'] > 0) !== [];
                        $price = $firstVariant['price_cents'] ?? null;
                        $isA4 = preg_match('/\bA4\b/i', (string) $product['name']) === 1;
                        $image = $artjetImages[(int) $product['id']] ?? $product['image_path'];
                        $format = $formatFor((string) $product['name']);
                    ?>
                        <article class="artjet-card" data-artjet-card>
                            <div class="artjet-card-inner">
                                <button class="artjet-card-face artjet-card-front" type="button" data-artjet-flip aria-expanded="false" aria-label="Ver información de <?= $escape((string) $product['name']) ?>">
                                    <span class="artjet-card-image<?= $isA4 ? ' is-a4' : '' ?>"><?php if (!empty($image)): ?><img src="<?= $escape((string) $image) ?>" alt="<?= $escape((string) $product['name']) ?>"><?php else: ?><span>SIN IMAGEN</span><?php endif; ?><b><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></b></span>
                                </button>
                                <div class="artjet-card-face artjet-card-back" inert>
                                    <button class="artjet-card-close" type="button" data-artjet-flip aria-label="Volver a la foto">↙</button>
                                    <div class="artjet-card-back-heading">
                                        <?php if (!empty($image)): ?><img src="<?= $escape((string) $image) ?>" alt=""><?php endif; ?>
                                        <p><?= $escape($upper($category)) ?></p>
                                    </div>
                                    <h3><?= $escape($displayName((string) $product['name'])) ?></h3>
                                    <?php if ($format !== ''): ?><p class="artjet-card-format"><?= $escape($upper($format)) ?></p><?php endif; ?>
                                    <?php if (!empty($product['description'])): ?><p class="artjet-card-description"><?= nl2br($escape((string) $product['description'])) ?></p><?php endif; ?>
                                    <div class="artjet-card-meta"><strong><?= $price !== null ? $escape($money((int) $price)) : 'Consultar' ?></strong><span class="<?= $hasStock ? 'is-available' : '' ?>"><?= $hasStock ? 'Disponible' : 'Sin stock' ?></span></div>
                                    <a class="artjet-card-action" href="/?producto=<?= (int) $product['id'] ?>">VER PRODUCTO ↗</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </section>
    <section class="artjet-manifesto"><p>ARTJET / LABORATORIO DIGITAL</p><h2>Inspirar momentos.<br>Crear valor.<br><em>Dejar huella.</em></h2><span>Catálogo, precios, stock e imágenes servidos desde nuestra propia infraestructura.</span></section>
<?php endif; ?>
</main>
<footer><strong>ART<span>JET</span></strong><span>Una experiencia de Laboratorio Digital.</span><a href="#inicio">VOLVER ARRIBA ↑</a></footer>
<script src="assets/artjet.js?v=1" defer></script>
</body></html>
