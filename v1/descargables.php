<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$design = $app['settings']->design();
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$assetVersion = substr(hash('sha256', (string) @file_get_contents(__DIR__ . '/assets/app.css') . (string) @file_get_contents(__DIR__ . '/assets/light.css')), 0, 12);
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Descargables · Laboratorio Digital</title>
    <link rel="icon" href="<?= $escape($assetPath) ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/light.css?v=<?= $escape($assetVersion) ?>">
</head>
<body class="downloads-page">
    <header class="store-header">
        <div class="header-leading"><a class="brand" href="<?= $escape($storeUrl) ?>"><img class="brand-logo" src="<?= $escape($design['logo_path']) ?>" alt="Laboratorio Digital"></a></div>
        <a class="downloads-back-header" href="<?= $escape($storeUrl) ?>">← Volver a la tienda</a>
    </header>
    <main class="downloads-shell">
        <a class="downloads-back" href="<?= $escape($storeUrl) ?>">← Volver a la tienda <small>ESC</small></a>
        <header class="downloads-intro"><p>RECURSOS GRATUITOS</p><h1>Descargables</h1><span>Elegí un diseño y descargalo directamente desde Laboratorio Digital.</span></header>
        <section class="downloads-grid" aria-label="Archivos descargables">
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/mochila-verde-preview-1.jpg" alt="Mochila para colorear verde"><div><h2>Mochila para colorear · verde</h2><p>Una mochilita para colorear, ideal para llenar de golosinas y regalar.</p><a href="<?= $escape($assetPath) ?>/downloads/mochila-verde.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/mochila-rosa-preview-1.jpg" alt="Mochila para colorear rosa"><div><h2>Mochila para colorear · rosa</h2><p>Una versión alternativa de la mochilita para imprimir, colorear y regalar.</p><a href="<?= $escape($assetPath) ?>/downloads/mochila-rosa.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/llaveros-san-valentin.jpg" alt="Llaveros para San Valentín"><div><h2>Llaveros para San Valentín</h2><p>Diseños de llaveros para crear un regalo especial.</p><a href="<?= $escape($assetPath) ?>/downloads/llaveros-san-valentin.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/cartas-a-santa.jpg" alt="Cartas a Santa"><div><h2>Cartas a Santa</h2><p>Cartas y sobres listos para imprimir y preparar los deseos de Navidad.</p><a href="<?= $escape($assetPath) ?>/downloads/cartas-a-santa.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
        </section>
    </main>
    <script>document.addEventListener('keydown', event => { if (event.key === 'Escape') window.location.href = <?= json_encode($storeUrl) ?>; });</script>
</body>
</html>
