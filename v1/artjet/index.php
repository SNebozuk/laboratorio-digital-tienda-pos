<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$host = strtolower((string) preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($host === 'www.artjet.com.ar') {
    $visitorId = (string) ($_COOKIE['artjet_store_visitor'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $visitorId)) {
        $visitorId = bin2hex(random_bytes(32));
        setcookie('artjet_store_visitor', $visitorId, [
            'expires' => time() + 60 * 60 * 24 * 400,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $app['store_visits']->recordArtjet($visitorId);
}
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
    <link rel="stylesheet" href="assets/artjet.css?v=5">
    <link rel="stylesheet" href="assets/artjet-cards.css?v=2">
    <link rel="stylesheet" href="assets/artjet-chat.css?v=1">
</head><body>
<header class="artjet-header">
    <a href="#inicio" class="artjet-logo" aria-label="Artjet">ART<span>JET</span></a>
    <nav><a href="#fotograficos">PAPELES</a><a href="#tintas-productos">TINTAS</a><a href="#catalogo">CATÁLOGO</a></nav>
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
    <nav class="artjet-category-nav" id="categorias" aria-label="Categorías Artjet">
        <?php foreach ($grouped as $category => $items): ?><a href="#<?= $escape($category === 'Tintas' ? 'tintas-productos' : $categorySlugs[$category]) ?>"><span><?= str_pad((string) count($items), 2, '0', STR_PAD_LEFT) ?></span><?= $escape($upper($category)) ?></a><?php endforeach; ?>
    </nav>

    <section class="artjet-catalog" id="catalogo">
        <?php foreach ($grouped as $category => $items):
            $slug = $category === 'Tintas' ? 'tintas-productos' : $categorySlugs[$category];
        ?>
            <section class="artjet-collection" id="<?= $escape($slug) ?>">
                <header><div><p>COLECCIÓN / <?= $escape($upper($category)) ?></p><h2><?= $escape($category) ?><sup><?= str_pad((string) count($items), 2, '0', STR_PAD_LEFT) ?></sup></h2></div><div class="artjet-carousel-controls"><button class="icon-button" type="button" data-artjet-prev aria-label="Retroceder en <?= $escape($category) ?>">‹</button><button class="icon-button" type="button" data-artjet-next aria-label="Avanzar en <?= $escape($category) ?>">›</button></div></header>
                <div class="artjet-product-grid">
                    <?php foreach ($items as $index => $product):
                        $variants = $product['variants'];
                        $firstVariant = $variants[0] ?? null;
                        $hasStock = array_filter($variants, static fn (array $variant): bool => $variant['available_stock'] === null || (int) $variant['available_stock'] > 0) !== [];
                        $price = $firstVariant['price_cents'] ?? null;
                        $size = preg_match('/\b(?:A6|10\s*[X×]\s*15)\b/i', (string) $product['name']) ? 'is-a6' : (preg_match('/\bA3\+?\b/i', (string) $product['name']) ? 'is-a3' : 'is-a4');
                        $image = $artjetImages[(int) $product['id']] ?? $product['image_path'];
                        $format = $formatFor((string) $product['name']);
                    ?>
                        <article class="artjet-card">
                            <button class="artjet-card-open" type="button" data-artjet-open aria-label="Ver información de <?= $escape((string) $product['name']) ?>">
                                <span class="artjet-card-image <?= $size ?>"><?php if (!empty($image)): ?><img src="<?= $escape((string) $image) ?>" alt="" loading="lazy"><?php endif; ?><span class="artjet-image-fallback"<?= !empty($image) ? ' hidden' : '' ?>>SIN IMAGEN</span></span>
                                <span class="artjet-card-title"><?= $escape($displayName((string) $product['name'])) ?></span>
                            </button>
                            <div class="artjet-card-details" hidden>
                                <p><?= $escape($upper($category)) ?></p><h3><?= $escape($displayName((string) $product['name'])) ?></h3>
                                <?php if ($format !== ''): ?><p class="artjet-card-format"><?= $escape($upper($format)) ?></p><?php endif; ?>
                                <?php if (!empty($product['description'])): ?><p class="artjet-card-description"><?= nl2br($escape((string) $product['description'])) ?></p><?php endif; ?>
                                <div class="artjet-card-meta"><strong><?= $price !== null ? $escape($money((int) $price)) : 'Consultar' ?></strong><span class="<?= $hasStock ? 'is-available' : '' ?>"><?= $hasStock ? 'Disponible' : 'Sin stock' ?></span></div>
                                <a class="artjet-card-action" href="/?producto=<?= (int) $product['id'] ?>">VER PRODUCTO ↗</a>
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
<div class="artjet-modal" id="artjet-modal" role="dialog" aria-modal="true" aria-label="Detalle de producto" hidden><div class="artjet-modal-panel"><button class="artjet-modal-close icon-button" type="button" aria-label="Cerrar detalle">×</button><div class="artjet-modal-image"></div><div class="artjet-modal-details"></div></div></div>
<footer><strong>ART<span>JET</span></strong><span>Una experiencia de Laboratorio Digital.</span><a href="#inicio">VOLVER ARRIBA ↑</a></footer>
<a class="artjet-whatsapp" href="https://wa.me/5493415699338?text=Hola%2C%20les%20hablo%20desde%20el%20sitio%20de%20Artjet" target="_blank" rel="noopener" aria-label="Hablar por WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.7a8.4 8.4 0 0 1-12.4 7.4L3.5 20.5l1.4-4.4a8.4 8.4 0 1 1 15.6-4.4Z"/><path d="M8.2 7.7c.2-.4.4-.4.7-.4h.5c.2 0 .4 0 .5.4l.8 1.9c.1.3.1.5-.1.7l-.6.7c-.2.2-.1.4 0 .6.7 1.3 1.7 2.3 3 2.9.2.1.4.1.6-.1l.8-1c.2-.2.4-.3.7-.2l1.9.9c.3.1.4.3.4.5 0 .3-.2 1.5-.9 2.1-.6.6-1.5.9-2.5.7-1.1-.2-2.5-.7-4.2-2.2-2-1.8-3.3-4-3.5-5.5-.2-.9.1-1.6.5-2Z"/></svg></a>
<button class="artjet-chat-launcher" id="artjet-chat-launcher" type="button" aria-label="Abrir atención con IA">IA</button>
<section class="artjet-chat" id="artjet-chat" hidden aria-label="Atención Artjet">
    <header><div><strong>ARTJET</strong><span>Atención online</span></div><button type="button" id="artjet-chat-close" aria-label="Cerrar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></header>
    <div class="artjet-chat-messages" id="artjet-chat-messages" aria-live="polite"><p>Hola, ¿en qué te puedo ayudar?</p></div>
    <div class="artjet-chat-typing" id="artjet-chat-typing" hidden>Escribiendo…</div>
    <form id="artjet-chat-form"><textarea id="artjet-chat-input" rows="1" maxlength="800" placeholder="Escribí tu consulta…" aria-label="Consulta"></textarea><button type="submit" aria-label="Enviar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 12 16-8-5 16-3-6-8-2Z"/></svg></button></form>
</section>
<script src="assets/artjet.js?v=4" defer></script>
</body></html>
