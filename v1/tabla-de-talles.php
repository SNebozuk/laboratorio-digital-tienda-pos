<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$guide = $app['settings']->sizeGuide();
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$assetVersion = (string) @filemtime(__DIR__ . '/assets/app.css');
$scriptVersion = (string) @filemtime(__DIR__ . '/assets/size-guide.js');
$escape = static fn (string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

$groups = [];
foreach ($guide['rows'] as $row) {
    $groups[$row['group']][] = $row;
}

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $escape($assetPath) ?>/favicon.png?v=20260826" type="image/png">
    <meta name="theme-color" content="#06080d">
    <title>Tabla de Talles · Laboratorio Digital</title>
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
</head>
<body class="size-guide-page">
    <header class="store-header">
        <a class="brand" href="<?= $escape($storeUrl) ?>" aria-label="Volver a la tienda">
            <span class="brand-mark">LD</span>
            <span>
                <strong>LABORATORIO DIGITAL</strong>
                <small>TABLA DE TALLES</small>
            </span>
        </a>
        <a class="header-link" href="<?= $escape($storeUrl) ?>">VOLVER A LA TIENDA</a>
    </header>

    <main class="size-guide-shell">
        <header class="size-guide-intro">
            <p class="eyebrow">REFERENCIA DE MEDIDAS</p>
            <h1>TABLA DE TALLES</h1>
            <?php if ($guide['intro'] !== ''): ?>
                <p><?= $escape($guide['intro']) ?></p>
            <?php endif ?>
        </header>

        <?php if ($groups === []): ?>
            <section class="size-guide-empty">
                <h2>Estamos preparando las medidas</h2>
                <p>Consultanos por WhatsApp antes de confirmar tu pedido.</p>
            </section>
        <?php else: ?>
            <div class="size-guide-search" role="search">
                <label for="size-guide-search">BUSCAR EN LA TABLA</label>
                <input id="size-guide-search" type="search" placeholder="Ej.: body, mangas largas o talle 5" autocomplete="off">
            </div>
            <p class="size-guide-search-empty" id="size-guide-search-empty" role="status" hidden>No encontramos medidas que coincidan con tu búsqueda.</p>
            <div class="size-guide-groups">
                <?php foreach ($groups as $group => $rows): ?>
                    <section class="size-guide-card">
                        <h2><?= $escape((string) $group) ?></h2>
                        <div class="size-guide-table-wrap">
                            <table class="size-guide-table">
                                <thead>
                                    <tr>
                                        <th>Talle</th>
                                        <th>Ancho</th>
                                        <th>Largo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <th><?= $escape($row['size']) ?></th>
                                            <td><?= $escape($row['width'] ?: '—') ?></td>
                                            <td><?= $escape($row['length'] ?: '—') ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </main>
    <script src="<?= $escape($assetPath) ?>/size-guide.js?v=<?= $escape($scriptVersion) ?>" defer></script>
</body>
</html>
