<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$catalog = $app['products']->publicCatalog();
$publicSettings = $app['settings']->values();

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <title>Laboratorio Digital · Catálogo mayorista</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <header class="store-header">
        <a class="brand" href="./" aria-label="Laboratorio Digital, inicio">
            <span class="brand-mark">LD</span>
            <span>
                <strong>LABORATORIO DIGITAL</strong>
                <small>CATÁLOGO MAYORISTA · RETIRO EN EL LOCAL</small>
            </span>
        </a>
        <button class="cart-mobile" id="cart-mobile" type="button">
            Pedido <span id="cart-mobile-count">0</span>
        </button>
    </header>

    <main class="store-shell">
        <aside class="category-panel" aria-label="Secciones del catálogo">
            <div class="category-title">PRODUCTOS</div>
            <nav id="category-list"></nav>
            <div class="category-help">
                <strong>Compra práctica</strong>
                <span>Elegí talle y cantidad directamente desde la lista.</span>
            </div>
        </aside>

        <section class="catalog-column" aria-labelledby="catalog-title">
            <div class="catalog-intro">
                <p class="eyebrow">STOCK DISPONIBLE EN TIEMPO REAL</p>
                <h1 id="catalog-title">ENCONTRÁ Y ARMÁ TU PEDIDO RÁPIDO</h1>
                <p>
                    El stock se muestra por variante. Las unidades se reservan
                    cuando subís el comprobante de transferencia.
                </p>
            </div>

            <div class="search-wrap">
                <label for="product-search">Buscar productos</label>
                <input
                    id="product-search"
                    type="search"
                    autocomplete="off"
                    placeholder="Escribí producto, categoría o talle"
                >
                <div
                    id="search-suggestions"
                    class="suggestions"
                    role="listbox"
                    aria-label="Productos encontrados"
                ></div>
            </div>

            <div id="catalog-results" class="catalog-results"></div>
        </section>

        <aside class="order-panel" id="order-panel" aria-labelledby="order-title">
            <div class="order-panel-head">
                <div>
                    <p class="eyebrow">RESUMEN</p>
                    <h2 id="order-title">TU PEDIDO</h2>
                </div>
                <button class="icon-button" id="close-cart-mobile" type="button" aria-label="Cerrar pedido">×</button>
            </div>
            <div id="cart-lines" class="cart-lines">
                <p class="empty-copy">Todavía no agregaste productos.</p>
            </div>
            <div class="order-total">
                <span>Total</span>
                <strong id="cart-total">$ 0</strong>
            </div>
            <button class="primary-button" id="checkout-button" type="button" disabled>
                CONTINUAR PEDIDO
            </button>
            <p class="order-note">
                Pago por transferencia · Retiro únicamente en el local
            </p>
        </aside>
    </main>

    <div class="modal" id="modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <button class="modal-close" type="button" data-close-modal aria-label="Cerrar">×</button>
            <div id="modal-content"></div>
        </section>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script id="app-data" type="application/json"><?=
        json_encode([
            'api_url' => '../api.php',
            'csrf_token' => $app['csrf_token'],
            'products' => $catalog,
            'whatsapp_number' => $publicSettings['whatsapp_number'] ?? '5493415699338',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    ?></script>
    <script src="assets/store.js" defer></script>
</body>
</html>
