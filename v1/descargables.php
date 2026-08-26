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

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; script-src 'self'; frame-src https://www.art-jet.com.ar; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Descargables · Laboratorio Digital</title>
    <link rel="icon" href="<?= $escape($assetPath) ?>/favicon.png">
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
        <header class="downloads-hero"><span>✦ COMUNIDAD ART-JET</span><h1>Descargables <em>gratuitos.</em></h1><p>Ideas, plantillas y proyectos para imprimir, crear y regalar.</p></header>
        <div class="downloads-source"><iframe class="downloads-frame" src="https://www.art-jet.com.ar/descargablescomunidad" title="Descargables de Art-Jet"></iframe></div>
    </main>
    <script>document.addEventListener('keydown', event => { if (event.key === 'Escape') window.location.href = <?= json_encode($storeUrl) ?>; });</script>
</body>
</html>
