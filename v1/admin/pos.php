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
    <link rel="icon" href="<?= $escape($storeAssetPath) ?>/favicon.png?v=20260826" type="image/png">
    <meta name="theme-color" content="#ffffff">
    <title>Punto de Venta · Laboratorio Digital</title>
    <link rel="stylesheet" href="<?= $escape($storeAssetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($adminAssetPath) ?>/admin.css?v=<?= $escape($assetVersion) ?>">
</head>
<body class="admin-body pos-page-body">
    <main class="pos-page">
        <header class="pos-page-header">
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
                        <img class="pos-klaus-image" src="<?= $escape($storeAssetPath) ?>/klaus_checkout_sitting.png" alt="">
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
