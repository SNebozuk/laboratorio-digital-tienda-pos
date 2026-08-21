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
    . (string) @file_get_contents(dirname(__DIR__) . '/assets/klaus.js')
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
    <title>Punto de Venta · Laboratorio Digital</title>
    <link rel="stylesheet" href="<?= $escape($storeAssetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($adminAssetPath) ?>/admin.css?v=<?= $escape($assetVersion) ?>">
</head>
<body class="admin-body pos-page-body">
    <main class="pos-page">
        <header class="pos-page-header">
            <a href="./" class="pos-back-link">← Administración</a>
            <div class="pos-page-brand"><strong>LABORATORIO DIGITAL</strong><span>PUNTO DE VENTA</span></div>
            <span class="pos-live-status"><i></i> LISTO PARA VENDER</span>
        </header>
        <section class="pos-page-workspace">
            <div class="pos-page-products">
                <div class="pos-page-title">
                    <p class="eyebrow">MOSTRADOR</p>
                    <h1>NUEVA VENTA</h1>
                </div>
                <a class="pos-add-products-link" href="pos-products.php">
                    <span class="pos-add-products-icon">+</span>
                    <span><strong>AGREGAR PRODUCTOS</strong><small>Buscar por producto, variante, SKU o código de barras</small></span>
                    <b>›</b>
                </a>
                <section class="pos-sale-details">
                    <button class="pos-klaus" id="pos-klaus" type="button" aria-label="Acariciar a Klaus" title="Acariciar a Klaus">
                        <span class="pos-klaus-figure" aria-hidden="true"><svg viewBox="0 0 180 120"><g style="fill:#d4a15f !important;stroke:#68411d !important;stroke-width:2.6;stroke-linecap:round;stroke-linejoin:round"><path class="pos-klaus-tail" d="M134 78c21-17 32-4 23 10-4 6-10 9-16 9"/><path d="M54 79c1-23 18-37 46-37 28 0 45 14 46 37l-9 23H57z"/><path class="pos-klaus-chest" style="fill:#efcc98 !important" d="M84 55c10 2 19 11 21 24l-7 23H73l5-23c1-11 2-19 6-24z"/><path d="M36 31c10-19 39-22 53-4 10 12 7 34-6 45-15 13-40 8-50-8-7-11-4-24 3-33z"/><path class="pos-klaus-ear" style="fill:#ad743b !important" d="M40 33C20 34 18 52 29 68c6 8 17 5 20-6l4-23z"/><path class="pos-klaus-ear" style="fill:#ad743b !important" d="M79 34c18-9 27 8 21 24-4 11-14 14-20 5l-5-17z"/><path class="pos-klaus-line" d="M45 46c4-3 8-3 11-1M68 44c4-3 8-2 10 1"/><ellipse class="pos-klaus-muzzle" style="fill:#efcc98 !important;stroke:none" cx="61" cy="63" rx="18" ry="13"/><circle class="pos-klaus-dark" style="fill:#322015 !important;stroke:none" cx="51" cy="51" r="3"/><circle class="pos-klaus-dark" style="fill:#322015 !important;stroke:none" cx="74" cy="50" r="3"/><path class="pos-klaus-dark" style="fill:#322015 !important;stroke:none" d="M57 59q5-4 10 0l-5 5z"/><path class="pos-klaus-line" d="M61 65c3 5 9 6 13 0M67 96v13M122 96v13"/><path class="pos-klaus-collar" style="fill:none;stroke:#7652b8 !important;stroke-width:4" d="M45 75c11 8 29 8 41-1"/><circle class="pos-klaus-tag" style="fill:#f2c84b !important;stroke:#8b6918 !important;stroke-width:1.2" cx="65" cy="80" r="3"/></g></svg></span>
                        <span class="pos-klaus-label">KLAUS</span>
                    </button>
                    <div class="pos-main-total"><span>TOTAL</span><strong id="pos-total">$ 0</strong></div>
                </section>
            </div>
            <aside class="pos-cart pos-page-cart">
                <div class="pos-cart-heading">
                    <div><p class="eyebrow">VENTA ACTUAL</p><h2>RESUMEN</h2></div>
                    <button class="pos-clear-cart" id="pos-clear-cart" type="button" disabled>VACIAR CARRITO</button>
                </div>
                <div id="pos-cart-lines" class="cart-lines"></div>
                <div class="pos-customer-checkout">
                    <input id="pos-customer" value="" placeholder="Nombre y apellido (opcional)" autocomplete="name" aria-label="Nombre y apellido del cliente, opcional">
                    <input id="pos-customer-phone" value="" inputmode="tel" placeholder="WhatsApp" autocomplete="tel" aria-label="WhatsApp del cliente">
                </div>
                <button class="primary-button" id="complete-sale-button" type="button" disabled>FINALIZAR VENTA</button>
            </aside>
        </section>
    </main>
    <div class="modal" id="modal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><section class="modal-card admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title"><button class="modal-close" type="button" data-close-modal aria-label="Cerrar">×</button><div id="modal-content"></div></section></div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script id="admin-app-data" type="application/json"><?= json_encode(['api_url' => $apiUrl, 'csrf_token' => $app['csrf_token'], 'user' => $user, 'setup_required' => false, 'size_guide_url' => $storePath . '/tabla-de-talles.php', 'store_url' => $storeUrl], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="<?= $escape($storeAssetPath) ?>/klaus.js?v=<?= $escape($assetVersion) ?>" defer></script>
    <script src="<?= $escape($adminAssetPath) ?>/admin.js?v=<?= $escape($assetVersion) ?>" defer></script>
</body>
</html>
