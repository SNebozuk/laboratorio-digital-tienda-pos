<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
$user = $app['auth']->user();
if (!$user) {
    header('Location: ./');
    exit;
}
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$storeAssetPath = $storePath . '/assets';
$adminAssetPath = $storePath . '/admin/assets';
$assetVersion = substr(hash('sha256',
    (string) @file_get_contents(dirname(__DIR__) . '/assets/app.css')
    . (string) @file_get_contents(__DIR__ . '/assets/admin.css')
    . (string) @file_get_contents(__DIR__ . '/assets/admin.js')
), 0, 12);
$apiUrl = $storePath . '/api.php';
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data: blob:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $escape($storeAssetPath) ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#ffffff">
    <title>Agregar productos · Punto de Venta</title>
    <link rel="stylesheet" href="<?= $escape($storeAssetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($adminAssetPath) ?>/admin.css?v=<?= $escape($assetVersion) ?>">
</head>
<body class="admin-body pos-page-body pos-search-page">
    <main class="pos-page">
        <header class="pos-page-header">
            <a href="pos.php" class="pos-back-link">← Volver a la venta</a>
            <div class="pos-page-brand"><strong>LABORATORIO DIGITAL</strong><span>PUNTO DE VENTA</span></div>
            <a href="pos.php" class="pos-search-close" aria-label="Cerrar búsqueda">×</a>
        </header>
        <section class="pos-page-intro">
            <div class="pos-page-title"><p class="eyebrow">PRODUCTOS</p><h1>AGREGAR PRODUCTOS</h1></div>
            <div class="search-wrap pos-search-wrap">
                <label for="pos-search">Buscar o escanear</label>
                <input id="pos-search" type="search" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="none" autofocus placeholder="Producto, talle, SKU o código de barras">
                <div id="pos-suggestions" class="suggestions"></div>
            </div>
        </section>
        <section class="pos-search-results"><div id="pos-products" class="pos-products"></div></section>
    </main>
    <div class="modal" id="modal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><section class="modal-card admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title"><button class="modal-close" type="button" data-close-modal aria-label="Cerrar">×</button><div id="modal-content"></div></section></div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script id="admin-app-data" type="application/json"><?= json_encode(['api_url' => $apiUrl, 'csrf_token' => $app['csrf_token'], 'user' => $user, 'setup_required' => false, 'size_guide_url' => $storePath . '/tabla-de-talles.php', 'store_url' => $storeUrl], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="<?= $escape($adminAssetPath) ?>/admin.js?v=<?= $escape($assetVersion) ?>" defer></script>
</body>
</html>
