<?php
declare(strict_types=1);

$host = strtolower((string) preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if (in_array($host, ['artjet.com.ar', 'www.artjet.com.ar'], true)) {
    header('Location: https://www.laboratoriodigital.com.ar/', true, 301);
    exit;
}

// También sirve los archivos de rastreo cuando el hosting usa esta página
// como controlador para las rutas públicas que no son archivos físicos.
$publicRequestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if ($publicRequestPath === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    readfile(dirname(__DIR__) . '/robots.txt');
    exit;
}
if ($publicRequestPath === '/sitemap.xml') {
    require dirname(__DIR__) . '/sitemap.php';
    exit;
}

require_once dirname(__DIR__) . '/app/StoreSeo.php';
$app = require dirname(__DIR__) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
// El alojamiento puede enviar rutas antiguas o inexistentes al catálogo.
// Solo sus entradas reales deben responder como la página principal.
$publicStorePrefix = trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$catalogPaths = ['/', '/index.php', '/v1', '/v1/', '/v1/index.php', '/tienda', '/tienda/'];
if ($publicStorePrefix !== '') {
    $catalogPaths[] = '/' . $publicStorePrefix;
    $catalogPaths[] = '/' . $publicStorePrefix . '/';
    $catalogPaths[] = '/' . $publicStorePrefix . '/index.php';
}
if (!in_array($publicRequestPath, $catalogPaths, true)) {
    http_response_code(404);
    header('X-Robots-Tag: noindex');
    header('Content-Type: text/html; charset=UTF-8');
    $catalogLink = htmlspecialchars(\LaboratorioDigital\StoreSeo::storeUrl($publicStorePrefix), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Página no disponible · Laboratorio Digital</title></head><body><main><h1>Esta página no está disponible</h1><p>Podés encontrar los productos actuales en nuestro catálogo.</p><a href="' . $catalogLink . '">Ver catálogo de Laboratorio Digital</a></main></body></html>';
    exit;
}
$storeUser = $app['auth']->user();
if ($storeUser === null) {
    $visitorId = (string) ($_COOKIE['laboratorio_store_visitor'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $visitorId)) {
        $visitorId = bin2hex(random_bytes(32));
        setcookie('laboratorio_store_visitor', $visitorId, [
            'expires' => time() + 60 * 60 * 24 * 400,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $app['store_visits']->record($visitorId);
}
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
    . (string) @file_get_contents(__DIR__ . '/assets/klaus.js')
    . (string) @file_get_contents(__DIR__ . '/assets/pulga.js')
    . (string) @file_get_contents(__DIR__ . '/assets/store.js')
), 0, 12);
$searchNormalizerJsVersion = substr(hash_file('sha256', __DIR__ . '/assets/search-normalizer.js') ?: '1', 0, 12);
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$sizeGuideUrl = $storePath . '/tabla-de-talles.php';
$quoteUrl = $storePath . '/cotizador.php';
$quoteEnabled = ($app['settings']->quote()['enabled'] ?? '1') === '1';
$apiUrl = $storePath . '/api.php';
$checkoutCustomer = $app['checkout_google']->customer();
$checkoutCustomerData = [
    'enabled' => $app['checkout_google']->enabled(),
    'customer' => $checkoutCustomer,
    'return_to_checkout' => false,
    'error' => '',
];
$whatsappNumber = preg_replace('/\D+/', '', (string) ($publicSettings['whatsapp_number'] ?? '5493415699338')) ?: '5493415699338';
$pickupAddress = trim((string) ($publicSettings['pickup_address'] ?? ''));
$businessHours = trim((string) ($publicSettings['business_hours'] ?? ''));
$mapUrl = $pickupAddress === '' ? '' : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($pickupAddress);
$cartMaintenanceEnabled = in_array((string) ($publicSettings['cart_maintenance_enabled'] ?? '0'), ['1', 'true', 'on'], true);
$welcomePopupEnabled = in_array((string) ($publicSettings['welcome_popup_enabled'] ?? '0'), ['1', 'true', 'on'], true);
$welcomePopupText = trim((string) ($publicSettings['welcome_popup_text'] ?? ''));
$featuredProductIds = $app['settings']->featuredProductIds();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$logoIsText = ($design['logo_mode'] ?? 'image') === 'text' && trim((string) ($design['logo_text'] ?? '')) !== '';
$logoText = trim((string) ($design['logo_text'] ?? '')) ?: 'Laboratorio Digital';
$logoHref = trim((string) ($design['logo_link'] ?? '')) ?: $storeUrl;
$seoCatalog = $app['products']->publicCatalog();
$seoProduct = null;
$requestedProductId = filter_input(INPUT_GET, 'producto', FILTER_VALIDATE_INT);
foreach ($seoCatalog as $product) {
    if ($requestedProductId === $product['id']) {
        $seoProduct = $product;
        break;
    }
}
if (isset($_GET['producto']) && $seoProduct === null) {
    http_response_code(404);
    header('X-Robots-Tag: noindex');
}
$seoBaseUrl = \LaboratorioDigital\StoreSeo::storeUrl($storePath);
$canonicalUrl = $seoBaseUrl . ($seoProduct ? '?producto=' . $seoProduct['id'] : '');
$seoTitle = $seoProduct ? $seoProduct['name'] . ' · Laboratorio Digital' : 'Laboratorio Digital · Catálogo mayorista de sublimación y personalización';
$seoDescription = trim(strip_tags((string) ($seoProduct['description'] ?? ('Insumos para sublimación y personalización. ' . $design['hero_text']))));
if ($seoDescription === '') {
    $seoDescription = $seoTitle . '. Consultá las variantes disponibles y armá tu pedido online.';
}
$seoImageUrl = \LaboratorioDigital\StoreSeo::imageUrl($seoProduct['image_path'] ?? null, $seoBaseUrl);
$structuredData = isset($_GET['producto']) && $seoProduct === null
    ? null : \LaboratorioDigital\StoreSeo::structuredData($seoBaseUrl, $publicSettings, $seoProduct);

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="google-site-verification" content="8azoEAFPPWisdBLqL-TbSgBqxFxK2dd0Ir_O2Hyagi8">
    <meta name="msvalidate.01" content="0E592E25ED5A040836D3CFE3632C3E82">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= $escape((string) ($design['color_background'] ?? '#f7faf7')) ?>">
    <title><?= $escape($seoTitle) ?></title>
    <meta name="description" content="<?= $escape($seoDescription) ?>">
    <link rel="canonical" href="<?= $escape($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $escape($seoTitle) ?>">
    <meta property="og:description" content="<?= $escape($seoDescription) ?>">
    <meta property="og:url" content="<?= $escape($canonicalUrl) ?>">
    <meta property="og:locale" content="es_AR">
    <?php if ($seoImageUrl !== null): ?>
    <meta property="og:image" content="<?= $escape($seoImageUrl) ?>">
    <?php endif ?>
    <?php if ($structuredData !== null): ?>
    <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
    <?php endif ?>
    <link rel="icon" href="<?= $escape($storePath) ?>/favicon.php" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= $escape($assetPath) ?>/favicon.png">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>&theme=light-20260811">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/light.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/page-loading.css?v=<?= substr(hash_file('sha256', __DIR__ . '/assets/page-loading.css') ?: '1', 0, 12) ?>">
    <script src="<?= $escape($assetPath) ?>/page-loading.js?v=<?= substr(hash_file('sha256', __DIR__ . '/assets/page-loading.js') ?: '1', 0, 12) ?>"></script>
</head>
<body>
    <div id="page-loading" role="status" aria-label="Cargando sitio"><span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span></div>
    <header class="store-header">
        <div class="header-leading">
            <button class="catalog-menu-button" id="catalog-menu-button" type="button" aria-expanded="false" aria-controls="category-panel">
                <span aria-hidden="true">☰</span><span>MENÚ</span>
            </button>
            <a class="brand store-brand" href="<?= $escape($logoHref) ?>" aria-label="<?= $escape($logoText) ?>">
                <?php if ($logoIsText || $design['logo_path'] === ''): ?>
                    <span class="store-text-logo"><?= $escape($logoText) ?></span>
                <?php else: ?>
                    <img class="brand-logo" src="<?= $escape((string) $design['logo_path']) ?>" alt="<?= $escape($logoText) ?>">
                <?php endif ?>
            </a>
        </div>
        <div class="header-actions">
            <?php if ($quoteEnabled): ?><a class="header-link header-quote-link" href="<?= $escape($quoteUrl) ?>">COTIZADOR</a><?php endif ?>
            <a class="header-link header-learn-link" href="<?= $escape($storeUrl) ?>?aprende=1" data-open-tutorials>APRENDE</a>
            <a class="header-link" href="<?= $escape($storePath) ?>/descargables.php">DESCARGABLES</a>
            <a class="header-link" href="<?= $escape($sizeGuideUrl) ?>" aria-label="Ver talles"><span class="header-link-long">VER TALLES</span><span class="header-link-short">TALLES</span></a>
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
            <div class="search-wrap">
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
                        aria-label="Buscar productos por nombre, descripción, variante o código"
                        placeholder="Nombre, descripción, variante o código"
                    >
                </div>
            </div>
            <div class="catalog-intro">
                <p class="eyebrow"><?= $escape((string) $design['hero_badge']) ?></p>
                <?php if (!$seoProduct && (string) $design['hero_link'] !== ''): ?><a class="catalog-title-link" href="<?= $escape((string) $design['hero_link']) ?>"><?php endif ?>
                <h1 id="catalog-title"><?= $escape((string) ($seoProduct['name'] ?? $design['hero_title'])) ?></h1>
                <?php if (!$seoProduct && (string) $design['hero_link'] !== ''): ?></a><?php endif ?>
                <p><?= $escape((string) $design['hero_text']) ?></p>
            </div>

            <div class="trust-strip" aria-label="Distribución oficial">
                <p>Somos reseller oficial de <strong>ART-JET</strong></p>
            </div>

            <nav class="catalog-breadcrumb" id="category-breadcrumb" aria-label="Ubicación actual">
                Todos los productos
            </nav>

            <div class="search-mode-head" id="search-mode-head">
                <h2>PRODUCTOS</h2>
                <button id="search-close" type="button" aria-label="Cerrar buscador">×</button>
            </div>

            <nav class="product-view-switcher" id="product-view-switcher" aria-label="Vista de productos">
                <span>VER PRODUCTOS</span>
                <button type="button" data-product-view="list" aria-pressed="true"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 6h10M9 12h10M9 18h10M5 6h.01M5 12h.01M5 18h.01"/></svg>Lista completa</button>
                <button type="button" data-product-view="catalog" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>Catálogo</button>
                <button type="button" data-product-view="minimal" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></svg>Minimalista</button>
                <button type="button" data-product-view="immersive" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/></svg>Inmersiva</button>
            </nav>

            <div id="catalog-results" class="catalog-results"></div>
            <details>
                <summary>Catálogo de productos y descripciones</summary>
                <?php if (isset($_GET['producto']) && $seoProduct === null): ?>
                    <p>Este producto no está disponible. Podés consultar el catálogo actual.</p>
                <?php endif ?>
                <?php foreach ($seoProduct ? [$seoProduct] : $seoCatalog as $product): ?>
                    <article>
                        <h2><a href="<?= $escape($storeUrl . '?producto=' . $product['id']) ?>"><?= $escape((string) $product['name']) ?></a></h2>
                        <p><?= nl2br($escape((string) $product['description'])) ?></p>
                        <p>Categoría: <?= $escape((string) $product['category']['name']) ?></p>
                        <ul>
                            <?php foreach ($product['variants'] as $variant): ?>
                                <li><?= $escape((string) $variant['name']) ?> · <?= $variant['price_cents'] === null ? 'Consultar precio' : '$ ' . number_format($variant['price_cents'] / 100, 2, ',', '.') . ' ARS' ?> · <?= $variant['available_stock'] === null ? 'Consultar disponibilidad' : ($variant['available_stock'] > 0 ? 'Disponible' : 'Sin stock') ?></li>
                            <?php endforeach ?>
                        </ul>
                    </article>
                <?php endforeach ?>
                <?php if ($seoProduct): ?><a href="<?= $escape($storeUrl) ?>">Ver todos los productos</a><?php endif ?>
                <section aria-label="Información de contacto">
                    <h2><?= $escape((string) ($publicSettings['store_name'] ?? 'Laboratorio Digital')) ?></h2>
                    <?php if ($pickupAddress !== ''): ?><p>Ubicación: <?= $escape($pickupAddress) ?></p><?php endif ?>
                    <?php if ($businessHours !== ''): ?><p>Horario: <?= $escape($businessHours) ?></p><?php endif ?>
                    <p>Vendemos a consumidores finales. Hacemos envíos y ofrecemos retiro en el local. Consultá zonas, costos y plazos por WhatsApp.</p>
                    <p><a href="https://wa.me/<?= $escape($whatsappNumber) ?>">Consultar por WhatsApp</a></p>
                </section>
            </details>
        </section>

        <aside class="cart-benefits-panel" aria-labelledby="cart-benefits-title">
            <h2 id="cart-benefits-title">BENEFICIOS DE TU COMPRA</h2>
            <p>Sumá unidades para completar el beneficio de tu pedido.</p>
            <section id="cart-rewards" class="cart-rewards" aria-live="polite"></section>
        </aside>

        <aside class="order-panel" id="order-panel" aria-labelledby="order-title">
            <div class="order-panel-head">
                <div>
                    <h2 id="order-title">TU PEDIDO</h2>
                </div>
                <button class="icon-button" id="close-cart-mobile" type="button" aria-label="Cerrar pedido">×</button>
            </div>
            <div class="cart-actions">
                <p id="cart-summary-meta" class="cart-summary-meta" aria-live="polite">0 productos diferentes · 0 unidades</p>
                <div class="order-total">
                    <span>Subtotal <small id="cart-subtotal"></small><br><small id="cart-discount"></small><br>Total</span>
                    <strong id="cart-total">$ 0</strong>
                </div>
                <button class="primary-button" id="checkout-button" type="button" disabled>
                    CONTINUAR PEDIDO
                </button>
                <button class="continue-shopping-button" id="continue-shopping-button" type="button">
                    SEGUIR AGREGANDO PRODUCTOS
                </button>
                <p class="order-note">
                    Transferencia bancaria · Envíos y retiro en el local
                </p>
            </div>
            <div class="cart-products-divider" aria-hidden="true"></div>
            <div id="cart-lines" class="cart-lines">
                <p class="empty-copy">Todavía no agregaste productos.</p>
            </div>
        </aside>
    </main>
    <div id="mobile-klaus-host" class="mobile-klaus-host" aria-live="polite"></div>

    <footer class="store-footer" id="contacto">
        <button class="footer-contact-button" id="contact-button" type="button">
            <span>CONTACTO</span>
            <small>Horario, WhatsApp y ubicación</small>
        </button>
        <a class="creator-credit" href="http://www.tienditas.com.ar">
            Sitio creado por
            <span class="creator-credit-logo"><img src="<?= $escape($assetPath) ?>/tienditas-logo.png" alt="tienditas" width="1760" height="800"></span>
        </a>
    </footer>

    <a
        class="floating-whatsapp"
        href="whatsapp://send?phone=<?= $escape($whatsappNumber) ?>"
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

    <?php if ($welcomePopupEnabled && $welcomePopupText !== ''): ?>
        <div class="welcome-popup" id="welcome-popup" role="dialog" aria-modal="true" aria-labelledby="welcome-popup-title">
            <section class="welcome-popup-card">
                <p class="eyebrow">BIENVENIDOS</p>
                <h1 id="welcome-popup-title">Aviso</h1>
                <p class="welcome-popup-message"><?= $escape($welcomePopupText) ?></p>
                <button class="primary-button" id="welcome-popup-enter" type="button">INGRESAR AL SITIO</button>
            </section>
        </div>
    <?php endif; ?>

    <script id="app-data" type="application/json"><?=
        json_encode([
            'api_url' => $apiUrl,
            'asset_url' => $assetPath,
            'csrf_token' => $app['csrf_token'],
            'checkout_customer' => $checkoutCustomerData,
            'products' => $catalog,
            'categories' => $categoryTree,
            'whatsapp_number' => $publicSettings['whatsapp_number'] ?? '5493415699338',
            'orders_enabled' => (bool) ($app['config']['orders_enabled'] ?? false),
            'cart_maintenance_enabled' => $cartMaintenanceEnabled,
            'klaus_discount_unlocked' => !empty($_SESSION['cart_klaus_discount_unlocked']),
            'klaus_reward_checked' => !empty($_SESSION['cart_klaus_reward_checked']),
            'rewards' => array_filter($publicSettings, static fn ($key) => str_starts_with((string) $key, 'reward_'), ARRAY_FILTER_USE_KEY),
            'pulga' => [
                'enabled' => $publicSettings['pulga_enabled'] ?? '1',
                'frequency_seconds' => $publicSettings['pulga_frequency_seconds'] ?? '75',
                'animations_enabled' => $publicSettings['pulga_animations_enabled'] ?? '1',
            ],
            'featured_product_ids' => $featuredProductIds,
            'tutorials' => $app['tutorials']->publicList(),
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
    <script src="<?= $escape($assetPath) ?>/klaus.js?v=<?= $escape($assetVersion) ?>" defer></script>
    <script src="<?= $escape($assetPath) ?>/pulga.js?v=<?= $escape($assetVersion) ?>" defer></script>
    <script src="<?= $escape($assetPath) ?>/search-normalizer.js?v=<?= $escape($searchNormalizerJsVersion) ?>" defer></script>
    <script src="<?= $escape($assetPath) ?>/store.js?v=<?= $escape($assetVersion) ?>" defer></script>
</body>
</html>
