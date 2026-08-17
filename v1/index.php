<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$catalog = []; // El catálogo se solicita luego de pintar la portada.
$categoryTree = $app['categories']->tree();
$publicSettings = $app['settings']->values();
$design = $app['settings']->design();
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$assetVersion = substr(hash('sha256',
    (string) @file_get_contents(__DIR__ . '/assets/app.css')
    . (string) @file_get_contents(__DIR__ . '/assets/light.css')
    . (string) @file_get_contents(__DIR__ . '/assets/store.js')
), 0, 12);
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$sizeGuideUrl = $storePath . '/tabla-de-talles.php';
$apiUrl = $storePath . '/api.php';
$whatsappNumber = preg_replace('/\D+/', '', (string) ($publicSettings['whatsapp_number'] ?? '5493415699338')) ?: '5493415699338';
$pickupAddress = trim((string) ($publicSettings['pickup_address'] ?? ''));
$businessHours = trim((string) ($publicSettings['business_hours'] ?? ''));
$mapUrl = $pickupAddress === '' ? '' : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($pickupAddress);
$cartMaintenanceEnabled = in_array((string) ($publicSettings['cart_maintenance_enabled'] ?? '0'), ['1', 'true', 'on'], true);
$featuredProductIds = $app['settings']->featuredProductIds();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f2ed">
    <title>Laboratorio Digital · Catálogo mayorista</title>
    <link rel="icon" href="<?= $escape($assetPath) ?>/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>&theme=light-20260811">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/light.css?v=<?= $escape($assetVersion) ?>">
</head>
<body>
    <header class="store-header">
        <div class="header-leading">
            <button class="catalog-menu-button" id="catalog-menu-button" type="button" aria-expanded="false" aria-controls="category-panel">
                <span aria-hidden="true">☰</span><span>MENÚ</span>
            </button>
            <a class="brand" href="<?= $escape($design['logo_link'] ?: $storeUrl) ?>" aria-label="Laboratorio Digital, inicio">
                <img class="brand-logo" src="<?= $escape($design['logo_path']) ?>" alt="Laboratorio Digital">
                <span>
                    <strong>LABORATORIO DIGITAL</strong>
                    <small>CATÁLOGO MAYORISTA · RETIRO EN EL LOCAL</small>
                </span>
            </a>
        </div>
        <div class="header-actions">
            <a class="header-link" href="<?= $escape($sizeGuideUrl) ?>" aria-label="Ver tabla de talles"><span class="header-link-long">TABLA DE TALLES</span><span class="header-link-short">TALLES</span></a>
            <button class="cart-mobile" id="cart-mobile" type="button" aria-label="Abrir pedido">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 1.9-1.4L20 8H7M10 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/></svg>
                <span id="cart-mobile-count">0</span>
            </button>
        </div>
    </header>

    <main class="store-shell">
        <button class="category-backdrop" id="category-backdrop" type="button" aria-label="Cerrar menú de categorías" tabindex="-1"></button>
        <aside class="category-panel" id="category-panel" aria-label="Secciones del catálogo" tabindex="0">
            <div class="category-title">CATEGORÍAS</div>
            <button class="category-mobile-toggle" id="category-toggle" type="button" aria-expanded="false">
                <span>Categorías</span><span aria-hidden="true">⌄</span>
            </button>
            <nav id="category-list"></nav>
            <div class="category-help">
                <strong>Compra práctica</strong>
                <span>Elegí talle y cantidad directamente desde la lista.</span>
            </div>
        </aside>

        <section class="catalog-column" aria-labelledby="catalog-title">
            <?php if ($cartMaintenanceEnabled): ?>
                <section class="cart-maintenance-notice" role="status">
                    <strong>Estamos realizando trabajos en la tienda</strong>
                    <span>Podés recorrer el catálogo con normalidad; el carrito estará disponible nuevamente muy pronto.</span>
                </section>
            <?php endif; ?>
            <div class="catalog-intro">
                <h1 id="catalog-title">Tus ideas merecen hacerse realidad.</h1>
                <p>Insumos para sublimación, papeles, indumentaria y productos para personalizar.</p>
            </div>

            <div class="trust-strip" aria-label="Información de compra">
                <span>Pago por transferencia</span>
                <span>Retiro en el local</span>
                <span>Ayuda por WhatsApp</span>
            </div>

            <nav class="catalog-breadcrumb" id="category-breadcrumb" aria-label="Ubicación actual">
                Todos los productos
            </nav>

            <div class="search-mode-head" id="search-mode-head">
                <h2>PRODUCTOS</h2>
                <button id="search-close" type="button" aria-label="Cerrar buscador">×</button>
            </div>

            <div class="search-wrap">
                <label for="product-search">Buscar productos</label>
                <div class="search-field">
                    <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"></path>
                    </svg>
                    <input
                        id="product-search"
                        type="search"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="none"
                        spellcheck="false"
                        aria-autocomplete="none"
                        inputmode="search"
                        enterkeyhint="search"
                        placeholder="Buscar por nombre, descripción, variante o código"
                    >
                </div>
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
            <p id="cart-summary-meta" class="cart-summary-meta" aria-live="polite">0 productos diferentes · 0 unidades</p>
            <div class="order-total">
                <span>Total</span>
                <strong id="cart-total">$ 0</strong>
            </div>
            <button class="primary-button" id="checkout-button" type="button" disabled>
                CONTINUAR PEDIDO
            </button>
            <button class="continue-shopping-button" id="continue-shopping-button" type="button">
                SEGUIR AGREGANDO PRODUCTOS
            </button>
            <p class="order-note">
                Transferencia bancaria · Retiro únicamente en el local
            </p>
        </aside>
    </main>

    <footer class="store-footer" id="contacto">
        <button class="footer-contact-button" id="contact-button" type="button">
            <span>CONTACTO</span>
            <small>Horario, WhatsApp y ubicación</small>
        </button>
        <a class="creator-credit" href="<?= $escape($storePath) ?>/sergio-nebozuk.php">
            <span>¿QUERÉS VENDER MÁS ONLINE?</span>
            <strong>Creemos una tienda que trabaje para tu negocio <b aria-hidden="true">→</b></strong>
        </a>
    </footer>

    <a
        class="floating-whatsapp"
        href="https://wa.me/<?= $escape($whatsappNumber) ?>"
        target="_blank"
        rel="noopener"
        aria-label="Consultar por WhatsApp"
        title="Consultar por WhatsApp"
    >
        <svg aria-hidden="true" viewBox="0 0 32 32" focusable="false">
            <path d="M16 3a12.7 12.7 0 0 0-11 19.1L3.2 29l7.1-1.9A12.7 12.7 0 1 0 16 3Zm0 22.9c-2 0-3.9-.6-5.5-1.6l-.4-.2-4.2 1.1 1.1-4.1-.3-.4A10.1 10.1 0 1 1 16 25.9Zm5.6-7.6c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1-1.8-.9-3-1.7-4.2-3.8-.3-.5.3-.5.9-1.7.1-.2 0-.5 0-.7l-1-2.4c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 2.9 0 1.7 1.2 3.4 1.4 3.6.2.2 2.5 3.8 6 5.3 2.2.9 3.1 1 4.2.8 1.3-.2 1.8-.9 2.1-1.7.3-.8.3-1.5.2-1.7-.1-.2-.4-.3-.7-.4Z"></path>
        </svg>
    </a>

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
            'api_url' => $apiUrl,
            'csrf_token' => $app['csrf_token'],
            'products' => $catalog,
            'categories' => $categoryTree,
            'whatsapp_number' => $publicSettings['whatsapp_number'] ?? '5493415699338',
            'orders_enabled' => (bool) ($app['config']['orders_enabled'] ?? false),
            'cart_maintenance_enabled' => $cartMaintenanceEnabled,
            'featured_product_ids' => $featuredProductIds,
            'contact' => [
                'store_name' => $publicSettings['store_name'] ?? 'Laboratorio Digital',
                'whatsapp_number' => $whatsappNumber,
                'pickup_address' => $pickupAddress,
                'business_hours' => $businessHours,
                'map_url' => $mapUrl,
            ],
            'design' => $design,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    ?></script>
    <script src="<?= $escape($assetPath) ?>/store.js?v=<?= $escape($assetVersion) ?>" defer></script>
</body>
</html>
