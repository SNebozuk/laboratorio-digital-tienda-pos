<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
$user = $app['auth']->user();
$setupRequired = (int) $app['pdo']->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$storeAssetPath = $storePath . '/assets';
$adminAssetPath = $storePath . '/admin/assets';
$publicSettings = $app['settings']->values();
$assetVersion = static fn (string $path): string => substr(hash_file('sha256', $path) ?: '1', 0, 12);
$appCssVersion = $assetVersion(dirname(__DIR__) . '/assets/app.css');
$adminCssVersion = $assetVersion(__DIR__ . '/assets/admin.css');
$pulgaJsVersion = $assetVersion(dirname(__DIR__) . '/assets/pulga.js');
$klausJsVersion = $assetVersion(dirname(__DIR__) . '/assets/klaus.js');
$adminJsVersion = $assetVersion(__DIR__ . '/assets/admin.js');
$apiUrl = $storePath . '/api.php';
$sizeGuideUrl = $storePath . '/tabla-de-talles.php';
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
    <meta name="theme-color" content="#050505">
    <title>Laboratorio Digital · Administración</title>
    <link rel="icon" href="<?= $escape($storePath) ?>/favicon.php" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $escape($storeAssetPath) ?>/app.css?v=<?= $escape($appCssVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($adminAssetPath) ?>/admin.css?v=<?= $escape($adminCssVersion) ?>">
</head>
<body class="admin-body">
    <?php if (!$user): ?>
        <main class="login-shell">
            <section class="login-card">
                <div class="brand login-brand">
                    <span class="brand-mark">LD</span>
                    <span>
                        <strong>LABORATORIO DIGITAL</strong>
                        <small>ADMINISTRACIÓN Y PUNTO DE VENTA</small>
                    </span>
                </div>
                <?php if ($setupRequired): ?>
                    <p class="eyebrow">PRIMER INGRESO</p>
                    <h1>CREAR ADMINISTRADOR</h1>
                    <p>
                        Usá la clave de instalación configurada en el servidor.
                        Después de crear el usuario, esta pantalla se desactiva.
                    </p>
                    <form id="setup-form">
                        <label>Nombre<input name="name" required autocomplete="name"></label>
                        <label>Email<input name="email" type="email" required autocomplete="email"></label>
                        <label>Contraseña<span class="password-field"><input name="password" type="password" minlength="12" required autocomplete="new-password"><button type="button" class="password-toggle" aria-label="Mostrar contraseña">◉</button></span></label>
                        <label>Clave de instalación<input name="setup_token" type="password" required autocomplete="off"></label>
                        <button class="primary-button" type="submit">CREAR ADMINISTRADOR</button>
                    </form>
                <?php else: ?>
                    <p class="eyebrow">ACCESO INTERNO</p>
                    <h1>INGRESAR</h1>
                    <form id="login-form">
                        <label>Email<input name="email" type="email" required autocomplete="username"></label>
                        <label>Contraseña<span class="password-field"><input name="password" type="password" required autocomplete="current-password"><button type="button" class="password-toggle" aria-label="Mostrar contraseña">◉</button></span></label>
                        <button class="primary-button" type="submit">INGRESAR</button>
                    </form>
                <?php endif ?>
                <p class="login-help">
                    La tienda pública no contiene enlaces ni opciones de administración.
                </p>
            </section>
        </main>
    <?php else: ?>
        <div class="admin-shell">
            <aside class="admin-sidebar admin-icon-sidebar">
                <nav class="admin-nav" aria-label="Navegación principal">
                    <div class="admin-nav-sales">
                        <button class="admin-nav-button admin-nav-icon-button admin-nav-orders" type="button" data-view="orders" aria-label="Lista de Ventas (F1)" title="Lista de Ventas · F1"><svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"></rect><path d="M9 4V2h6v2M9 9h6M9 13h6M9 17h4"></path></svg><b id="orders-badge" class="nav-notification-badge" hidden>0</b></button>
                        <button class="admin-nav-button admin-nav-icon-button admin-nav-deliveries" type="button" data-view="deliveries" aria-label="Entrega de pedidos (F2)" title="Entrega de pedidos · F2"><svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h12v11H3zM15 9h3l3 3v4h-6M7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4"></path></svg><b id="deliveries-badge" class="nav-notification-badge" hidden>0</b></button>
                    </div>
                    <button class="admin-nav-button admin-nav-icon-button" type="button" data-view="pos" aria-label="Punto de Venta (F3)" title="Punto de Venta · F3"><svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18M7 15h4"></path></svg></button>
                    <div class="admin-nav-products">
                        <button class="admin-nav-button admin-nav-icon-button" type="button" data-view="products" aria-label="Productos (F4)" title="Productos · F4"><svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4 3 7l3 4 2-1v10h8V10l2 1 3-4-4-3-5 3z"></path></svg></button>
                    </div>
                    <button class="admin-nav-button admin-nav-icon-button" type="button" data-view="statistics" aria-label="Estadísticas (F6)" title="Estadísticas · F6"><svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M3 20h18"></path></svg></button>
                </nav>
                <?php if ($user['role'] === 'admin'): ?>
                    <div class="admin-sidebar-settings-menu" id="admin-sidebar-settings-menu" hidden>
                        <button class="admin-sidebar-settings-menu-close" type="button" aria-label="Cerrar configuración">Menú principal</button>
                        <strong>CONFIGURACIÓN</strong>
                        <button type="button" data-view="contact">Contacto</button>
                        <button type="button" data-view="size-guide">Tabla de Talles</button>
                        <button type="button" data-view="tutorials">Aprende</button>
                        <button id="admin-sidebar-design-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-sidebar-design-menu">Diseño</button>
                        <button type="button" data-view="quote">Cotizador</button>
                        <button type="button" data-view="whatsapp">WhatsApp</button>
                        <button type="button" data-view="users">Usuarios</button>
                        <button type="button" data-view="categories">Categorías</button>
                        <button type="button" data-view="maintenance">Mantenimiento</button>
                    </div>
                    <div class="admin-sidebar-settings-menu admin-sidebar-design-menu" id="admin-sidebar-design-menu" hidden>
                        <button class="admin-sidebar-design-menu-back" type="button" aria-label="Volver a configuración">Configuración</button>
                        <strong>DISEÑO</strong>
                        <button type="button" data-design-editor="content">Contenido principal</button>
                        <button type="button" data-design-editor="branding">Identidad visual</button>
                        <button type="button" data-design-editor="gallery">Fotos de portada</button>
                        <button type="button" data-design-editor="colors">Colores</button>
                        <button type="button" data-design-editor="mascots">Mascotas</button>
                        <button type="button" data-design-editor="order">Orden de las secciones</button>
                    </div>
                    <div class="admin-sidebar-settings-menu admin-sidebar-design-editor" id="admin-sidebar-design-editor" hidden>
                        <button class="admin-sidebar-design-editor-back" type="button" aria-label="Volver a diseño">Diseño</button>
                        <strong id="admin-sidebar-design-editor-title">DISEÑO</strong>
                        <form id="design-form" class="admin-sidebar-design-form">
                            <section data-design-editor-section="content" hidden>
                                <label>ETIQUETA SUPERIOR<input name="hero_badge" maxlength="120" required></label>
                                <label>TÍTULO PRINCIPAL<textarea name="hero_title" rows="3" maxlength="160" required></textarea></label>
                                <label>TEXTO PRINCIPAL<textarea name="hero_text" rows="4" maxlength="500" required></textarea></label>
                                <label>ENLACE DEL TÍTULO<input name="hero_link" placeholder="https://... o /v1/"></label>
                            </section>
                            <section data-design-editor-section="branding" hidden>
                                <div class="design-branding">
                                    <div><strong>LOGO EN EL ENCABEZADO</strong><small>Elegí conservar el logo imagen o mostrar el nombre de tu empresa.</small></div>
                                    <div class="design-logo-mode">
                                        <label><input name="logo_mode" type="radio" value="image">Logo imagen</label>
                                        <label><input name="logo_mode" type="radio" value="text">Logo textual</label>
                                    </div>
                                    <label>NOMBRE DE LA EMPRESA<input name="logo_text" maxlength="80" placeholder="Nombre de la empresa"></label>
                                    <label>TIPOGRAFÍA<select name="logo_font" class="font-family-select">
                                        <option value="Arial" style="font-family:Arial,sans-serif">Arial</option><option value="Helvetica" style="font-family:Helvetica,Arial,sans-serif">Helvetica</option><option value="Verdana" style="font-family:Verdana,sans-serif">Verdana</option><option value="Georgia" style="font-family:Georgia,serif">Georgia</option><option value="Times New Roman" style="font-family:'Times New Roman',serif">Times New Roman</option><option value="Trebuchet MS" style="font-family:'Trebuchet MS',sans-serif">Trebuchet MS</option><option value="Montserrat" style="font-family:Montserrat,Arial,sans-serif">Montserrat</option><option value="Roboto" style="font-family:Roboto,Arial,sans-serif">Roboto</option><option value="Poppins" style="font-family:Poppins,Arial,sans-serif">Poppins</option><option value="Oswald" style="font-family:Oswald,Arial,sans-serif">Oswald</option><option value="Inter" style="font-family:Inter,Arial,sans-serif">Inter</option><option value="Bebas Neue" style="font-family:'Bebas Neue',Arial,sans-serif">Bebas Neue</option>
                                    </select></label>
                                    <label>TAMAÑO<select name="logo_size"><option value="16">16 px</option><option value="20">20 px</option><option value="24">24 px</option><option value="28">28 px</option><option value="32">32 px</option><option value="36">36 px</option></select></label>
                                    <label>COLOR DEL TEXTO<input name="logo_color" type="color"></label>
                                    <label class="checkbox-setting"><input name="logo_bold" type="checkbox" value="1"><span><strong>Negrita</strong></span></label>
                                    <output class="design-text-logo-preview" id="design-text-logo-preview" aria-label="Vista previa del logo textual"></output>
                                </div>
                                <div class="design-branding">
                                    <div><strong>LOGO IMAGEN</strong><small>El archivo se conserva aunque actives el logo textual.</small></div>
                                    <label>ENLACE DEL LOGO<input name="logo_link" placeholder="https://... o /v1/"></label>
                                    <label>REEMPLAZAR LOGO<input name="logo_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="logo_path" type="hidden"></label>
                                    <button id="restore-default-logo" class="secondary-button" type="button">USAR LOGO ORIGINAL</button>
                                    <img id="design-logo-preview" class="admin-sidebar-design-image" alt="Vista previa del logo imagen">
                                </div>
                                <div class="design-branding">
                                    <div><strong>FAVICON</strong><small>Se genera automáticamente como SVG para toda la tienda.</small></div>
                                    <label>TEXTO (MÁXIMO 2 CARACTERES)<input name="favicon_text" maxlength="2" required></label>
                                    <label>TIPOGRAFÍA<select name="favicon_font" class="font-family-select">
                                        <option value="Arial" style="font-family:Arial,sans-serif">Arial</option><option value="Helvetica" style="font-family:Helvetica,Arial,sans-serif">Helvetica</option><option value="Verdana" style="font-family:Verdana,sans-serif">Verdana</option><option value="Georgia" style="font-family:Georgia,serif">Georgia</option><option value="Times New Roman" style="font-family:'Times New Roman',serif">Times New Roman</option><option value="Trebuchet MS" style="font-family:'Trebuchet MS',sans-serif">Trebuchet MS</option><option value="Montserrat" style="font-family:Montserrat,Arial,sans-serif">Montserrat</option><option value="Roboto" style="font-family:Roboto,Arial,sans-serif">Roboto</option><option value="Poppins" style="font-family:Poppins,Arial,sans-serif">Poppins</option><option value="Oswald" style="font-family:Oswald,Arial,sans-serif">Oswald</option><option value="Inter" style="font-family:Inter,Arial,sans-serif">Inter</option><option value="Bebas Neue" style="font-family:'Bebas Neue',Arial,sans-serif">Bebas Neue</option>
                                    </select></label>
                                    <label>COLOR DE FONDO<input name="favicon_background_color" type="color"></label>
                                    <label>COLOR DEL TEXTO<input name="favicon_text_color" type="color"></label>
                                    <output class="design-favicon-preview" id="design-favicon-preview" aria-label="Vista previa del favicon"></output>
                                </div>
                            </section>
                            <section data-design-editor-section="gallery" hidden>
                                <label>FOTO 1<input name="hero_1_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="hero_1_path" type="hidden"><img id="design-hero-1-preview" class="admin-sidebar-design-image" alt="Foto publicada 1"></label>
                                <label>FOTO 2<input name="hero_2_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="hero_2_path" type="hidden"><img id="design-hero-2-preview" class="admin-sidebar-design-image" alt="Foto publicada 2"></label>
                                <label>FOTO 3<input name="hero_3_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="hero_3_path" type="hidden"><img id="design-hero-3-preview" class="admin-sidebar-design-image" alt="Foto publicada 3"></label>
                            </section>
                            <section data-design-editor-section="colors" hidden>
                                <label>FONDO<input name="color_background" type="color"></label>
                                <label>SUPERFICIES<input name="color_surface" type="color"></label>
                                <label>FONDO SECUNDARIO<input name="color_secondary" type="color"></label>
                                <label>TEXTO<input name="color_text" type="color"></label>
                                <label>COLOR PRINCIPAL<input name="color_accent" type="color"></label>
                            </section>
                            <section data-design-editor-section="mascots" hidden>
                                <label class="checkbox-setting"><input name="mascot_klaus_enabled" type="checkbox" value="1"><span><strong>Mostrar a Klaus</strong></span></label>
                                <label class="checkbox-setting"><input name="mascot_klaus_animations_enabled" type="checkbox" value="1"><span><strong>Animaciones de Klaus</strong></span></label>
                                <label class="checkbox-setting"><input name="mascot_pulga_enabled" type="checkbox" value="1"><span><strong>Mostrar a Pulga</strong></span></label>
                                <label class="checkbox-setting"><input name="mascot_pulga_animations_enabled" type="checkbox" value="1"><span><strong>Animaciones de Pulga</strong></span></label>
                            </section>
                            <section data-design-editor-section="order" hidden>
                                <input name="section_order" type="hidden">
                                <input name="section_visibility" type="hidden">
                                <p class="design-section-order-help">Arrastrá las secciones para cambiar el orden y usá el ojo para mostrarlas u ocultarlas.</p>
                                <div class="design-section-order" data-design-section-order>
                                    <div class="design-section-order-item" data-design-section="featured" draggable="true"><span class="design-section-drag" aria-hidden="true">⠿</span><strong>PRODUCTOS DESTACADOS</strong><button class="icon-action-button" type="button" data-design-section-visibility aria-label="Ocultar productos destacados" title="Ocultar productos destacados"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button></div>
                                    <div class="design-section-order-item" data-design-section="gallery" draggable="true"><span class="design-section-drag" aria-hidden="true">⠿</span><strong>FOTOS DE PORTADA</strong><button class="icon-action-button" type="button" data-design-section-visibility aria-label="Ocultar fotos de portada" title="Ocultar fotos de portada"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button></div>
                                    <div class="design-section-order-item" data-design-section="categories" draggable="true"><span class="design-section-drag" aria-hidden="true">⠿</span><strong>CATEGORÍAS</strong><button class="icon-action-button" type="button" data-design-section-visibility aria-label="Ocultar categorías" title="Ocultar categorías"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button></div>
                                    <div class="design-section-order-item" data-design-section="tutorials" draggable="true"><span class="design-section-drag" aria-hidden="true">⠿</span><strong>TUTORIALES</strong><button class="icon-action-button" type="button" data-design-section-visibility aria-label="Ocultar tutoriales" title="Ocultar tutoriales"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button></div>
                                </div>
                            </section>
                            <button class="primary-button" type="submit">GUARDAR DISEÑO</button>
                        </form>
                    </div>
                <?php endif ?>
                <div class="admin-user">
                    <div class="admin-user-icon-actions">
                        <?php if ($user['role'] === 'admin'): ?>
                            <button class="icon-button admin-nav-button" type="button" data-view="supplier-order" aria-label="Pedido a proveedor" title="Pedido a proveedor"><svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l2-8H7M9 20a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM17 20a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"></path></svg></button>
                            <button class="icon-button admin-settings-icon" id="admin-sidebar-settings-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-sidebar-settings-menu" aria-label="Abrir configuración" title="Configuración">
                                <svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.1 2.1-.06-.06A1.7 1.7 0 0 0 15.76 18a1.7 1.7 0 0 0-1.04 1.55V20h-3v-.45A1.7 1.7 0 0 0 10.68 18a1.7 1.7 0 0 0-1.88.34l-.06.06-2.1-2.1.06-.06A1.7 1.7 0 0 0 7.04 14.4 1.7 1.7 0 0 0 5.5 13.36H5v-3h.5A1.7 1.7 0 0 0 7.04 9.32 1.7 1.7 0 0 0 6.7 7.44l-.06-.06 2.1-2.1.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.04-1.55V3.7h3v.43a1.7 1.7 0 0 0 1.04 1.55 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.1 2.1-.06.06a1.7 1.7 0 0 0-.34 1.88 1.7 1.7 0 0 0 1.55 1.04h.45v3h-.45A1.7 1.7 0 0 0 19.4 15Z"></path></svg>
                            </button>
                        <?php endif ?>
                        <button class="icon-button admin-store-link admin-sidebar-store-button" id="admin-store-button" type="button" aria-label="Ver tienda" title="Ver tienda">
                            <svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 20h18M5 20V8l7-4 7 4v12M9 20v-5h6v5M8 10h.01M16 10h.01"></path></svg>
                        </button>
                        <button class="icon-button admin-logout-icon" id="logout-button" type="button" aria-label="Salir" title="Salir">
                            <svg class="admin-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M13 4h6v16h-6"></path></svg>
                        </button>
                    </div>
                </div>
            </aside>

            <main class="admin-main">
                <header class="admin-mobile-header">
                    <strong>LABORATORIO DIGITAL</strong>
                    <select id="mobile-view">
                        <option value="orders">Lista de Ventas</option>
                        <option value="deliveries">Entrega de Pedidos</option>
                        <option value="statistics">Estadísticas</option>
                        <option value="products">Productos</option>
                        <?php if ($user['role'] === 'admin'): ?>
                            <option value="supplier-order">Pedido a proveedor</option>
                        <?php endif ?>
                        <option value="pos">Punto de Venta</option>
                        <?php if ($user['role'] === 'admin'): ?>
                            <option value="tutorials">Aprende</option>
                            <option value="categories">Categorías</option>
                            <option value="size-guide">Tabla de Talles</option>
                            <option value="contact">Contacto</option>
                            <option value="design">Diseño</option>
                            <option value="quote">Cotizador</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="users">Usuarios</option>
                            <option value="settings">Configuración</option>
                            <option value="maintenance">Mantenimiento</option>
                        <?php endif ?>
                    </select>
                </header>

                <section class="admin-view" id="view-products">
                    <div class="view-heading order-page-heading">
                        <div>
                            <p class="eyebrow">CATÁLOGO Y STOCK</p>
                            <h1 class="admin-page-title">PRODUCTOS</h1>
                            <p>Precio y stock se editan directamente por variante.</p>
                        </div>
                        <div class="order-page-actions product-page-actions"><button class="secondary-button" type="button" data-open-featured-products>DESTACADOS</button><button class="primary-button fit-button" id="new-product-button" type="button">NUEVO PRODUCTO</button></div>
                    </div>
                    <div class="admin-search product-search-tools">
                        <input id="admin-product-search" type="search" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="none" placeholder="Buscar por título, variante, SKU o código">
                        <button class="small-button" id="copy-product-search-link" type="button" disabled>COPIAR ENLACE DE BÚSQUEDA</button>
                    </div>
                    <div id="admin-product-list"></div>
                </section>

                <?php if ($user['role'] === 'admin'): ?>
                    <section class="admin-view supplier-order-view" id="view-supplier-order">
                        <div class="view-heading supplier-order-heading">
                            <div>
                                <p class="eyebrow">REPOSICIÓN DE STOCK</p>
                                <h1 class="admin-page-title">PEDIDO A PROVEEDOR</h1>
                                <p>Armá el pedido sin modificar el stock ni los precios de venta.</p>
                            </div>
                        </div>
                        <form id="supplier-order-filters" class="supplier-order-filters">
                            <div class="supplier-order-category-filter">
                                <button id="supplier-order-categories-trigger" class="icon-action-button supplier-order-category-trigger" type="button" aria-expanded="false" aria-controls="supplier-order-category-popover" aria-label="Filtrar por categorías" title="Filtrar por categorías"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                <div id="supplier-order-category-popover" class="supplier-order-category-popover" hidden>
                                    <div class="supplier-order-category-backdrop" data-close-supplier-order-categories></div>
                                    <section class="supplier-order-category-dialog" role="dialog" aria-modal="true" aria-labelledby="supplier-order-category-title" tabindex="-1">
                                        <header>
                                            <div><p class="eyebrow">FILTRO DEL PEDIDO</p><h2 id="supplier-order-category-title">CATEGORÍAS</h2></div>
                                            <button class="icon-action-button" type="button" data-close-supplier-order-categories aria-label="Cerrar categorías" title="Cerrar categorías"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                        </header>
                                        <p>Podés elegir más de una categoría.</p>
                                        <input id="supplier-order-categories-search" class="supplier-order-category-search" type="search" autocomplete="off" placeholder="Buscar categoría" aria-label="Buscar categoría">
                                        <div id="supplier-order-categories" class="supplier-order-category-list" role="group" aria-label="Categorías"></div>
                                        <footer><span id="supplier-order-categories-count" class="supplier-order-categories-count" aria-label="0 categorías elegidas">0</span><button class="primary-button fit-button" type="button" data-apply-supplier-order-categories>APLICAR</button></footer>
                                    </section>
                                </div>
                            </div>
                            <label>
                                PALABRAS CLAVE
                                <input id="supplier-order-keywords" name="keywords" type="search" autocomplete="off" placeholder="Nombre, descripción, SKU, código o variante">
                            </label>
                            <label>
                                STOCK HASTA
                                <input id="supplier-order-threshold" name="stock_threshold" type="number" inputmode="numeric" min="0" max="1000000" step="1" value="1" required>
                            </label>
                            <div class="supplier-order-filter-actions">
                                <button class="primary-button fit-button" type="submit">BUSCAR PRODUCTOS</button>
                            </div>
                        </form>
                        <p id="supplier-order-status" class="supplier-order-status" aria-live="polite"></p>
                        <div class="supplier-order-plan-header"><button class="supplier-order-clear-plan" type="button" data-supplier-order-clear-plan>LIMPIAR PLANILLA</button></div>
                        <div id="supplier-order-results" class="supplier-order-results"></div>
                        <section class="supplier-order-cart">
                            <header><h2>PEDIDO ACTUAL</h2><div><button class="icon-action-button" type="button" data-supplier-order-copy aria-label="Copiar pedido" title="Copiar pedido"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 8V5.5A1.5 1.5 0 0 0 14.5 4h-9A1.5 1.5 0 0 0 5.5 16H8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button><button class="danger-button fit-button" type="button" data-supplier-order-clear>VACIAR CARRITO</button></div></header>
                            <div id="supplier-order-cart"></div>
                        </section>
                    </section>

                    <section class="admin-view" id="view-tutorials">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">CONTENIDO EDUCATIVO</p>
                                <h1 class="admin-page-title">APRENDE</h1>
                                <p>Creá y editá los tutoriales que se muestran en la portada.</p>
                            </div>
                            <button class="primary-button fit-button" id="new-tutorial-button" type="button">NUEVO TUTORIAL</button>
                        </div>
                        <div id="tutorial-list" class="tutorial-admin-list"></div>
                    </section>

                    <section class="admin-view" id="view-categories">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">ORGANIZACIÓN DEL CATÁLOGO</p>
                                <h1 class="admin-page-title">CATEGORÍAS</h1>
                                <p>Creá, ordená y mantené categorías y subcategorías.</p>
                            </div>
                            <button class="primary-button fit-button" id="new-category-button" type="button">NUEVA CATEGORÍA</button>
                        </div>
                        <div id="category-admin-tree" class="category-admin-tree"></div>
                    </section>

                    <section class="admin-view" id="view-size-guide">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">REFERENCIA PARA CLIENTES</p>
                                <h1 class="admin-page-title">TABLA DE TALLES</h1>
                                <p>Editá la tabla tal como la verá el cliente: escribí directamente en cada celda.</p>
                            </div>
                            <a class="secondary-button fit-button" href="<?= $escape($sizeGuideUrl) ?>" target="_blank" rel="noopener">VER PAGINA</a>
                        </div>
                        <form id="size-guide-form" class="size-guide-editor">
                            <label>
                                TEXTO INTRODUCTORIO
                                <textarea id="size-guide-intro" rows="3" maxlength="1000"></textarea>
                            </label>
                            <div class="size-guide-editor-head">
                                <div>
                                    <strong>MEDIDAS</strong>
                                    <small>Una fila por talle. Duplicá una fila para cargar medidas similares más rápido.</small>
                                </div>
                                <button class="secondary-button fit-button" id="add-size-guide-row" type="button">+ AGREGAR FILA</button>
                            </div>
                            <div id="size-guide-rows" class="size-guide-editor-rows"></div>
                            <button class="primary-button fit-button" type="submit">GUARDAR TABLA DE TALLES</button>
                        </form>
                    </section>

                    <section class="admin-view" id="view-contact">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">INFORMACIÓN PARA CLIENTES</p>
                                <h1 class="admin-page-title">CONTACTO</h1>
                                <p>Estos datos se muestran en la tienda y alimentan el botón de WhatsApp.</p>
                            </div>
                        </div>
                        <form id="contact-form" class="settings-card">
                            <div class="settings-grid">
                                <label>NOMBRE DEL COMERCIO<input name="store_name" required></label>
                                <label>WHATSAPP CON CÓDIGO DE PAÍS<input name="whatsapp_number" inputmode="numeric" required></label>
                                <label>DIRECCIÓN DE RETIRO<input name="pickup_address"></label>
                                <label>HORARIOS DE ATENCIÓN<input name="business_hours" required></label>
                            </div>
                            <button class="primary-button fit-button" type="submit">GUARDAR CONTACTO</button>
                        </form>
                    </section>

                    <section class="admin-view" id="view-design">
                        <div class="view-heading"><div><p class="eyebrow">VISTA PREVIA EN TIEMPO REAL</p><h1 class="admin-page-title">DISEÑO</h1><p>Elegí una opción en el menú lateral y mirá el resultado antes de guardar.</p></div><a class="secondary-button fit-button" href="<?= $escape($storeUrl) ?>" target="_blank" rel="noopener">ABRIR TIENDA</a></div>
                        <div class="design-live-preview" id="design-live-preview">
                            <header class="design-preview-header"><img id="design-preview-logo" alt="Logo de la tienda"><span class="design-preview-text-logo" id="design-preview-text-logo" hidden></span><span>MENÚ</span><span>CARRITO</span></header>
                            <section class="design-preview-hero"><p id="design-preview-badge"></p><h2 id="design-preview-title"></h2><p id="design-preview-text"></p></section>
                            <main class="design-preview-sections" id="design-preview-sections">
                                <section data-design-preview-section="featured"><strong>PRODUCTOS DESTACADOS</strong><div class="design-preview-card-grid"><span></span><span></span><span></span></div></section>
                                <section data-design-preview-section="gallery" class="design-preview-gallery"><img id="design-preview-hero-1" alt="Foto 1"><img id="design-preview-hero-2" alt="Foto 2"><img id="design-preview-hero-3" alt="Foto 3"></section>
                                <section data-design-preview-section="categories"><strong>CATEGORÍAS</strong><div class="design-preview-category-grid"><span>SUBLIMABLES</span><span>REMERAS</span><span>PAPELES</span></div></section>
                                <section data-design-preview-section="tutorials"><strong>TUTORIALES</strong><div class="design-preview-card-grid"><span></span><span></span><span></span></div></section>
                            </main>
                            <div class="design-preview-mascots"><img id="design-preview-klaus" src="<?= $escape($storeAssetPath) ?>/klaus_checkout_sitting.png" alt="Klaus"><span id="design-preview-pulga" role="img" aria-label="Pulga">🐈</span></div>
                        </div>
                    </section>

                    <section class="admin-view" id="view-quote">
                        <div class="view-heading"><div><p class="eyebrow">HERRAMIENTA PARA CLIENTES</p><h1 class="admin-page-title">COTIZADOR</h1><p>Calcula el costo y rendimiento del papel usando el precio actual del catálogo.</p></div><a class="secondary-button fit-button" href="<?= $escape($storePath) ?>/cotizador.php" target="_blank" rel="noopener">VER COTIZADOR</a></div>
                        <form id="quote-settings-form" class="settings-card">
                            <div class="settings-grid">
                                <label class="settings-span-two"><input name="enabled" type="checkbox" value="1"> MOSTRAR EL COTIZADOR EN LA TIENDA</label>
                            </div>
                            <p class="form-hint">El cálculo contempla únicamente el papel elegido.</p>
                            <button class="primary-button fit-button" type="submit">GUARDAR COTIZADOR</button>
                        </form>
                    </section>

                    <section class="admin-view" id="view-whatsapp">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">AVISOS DE PEDIDOS</p>
                                <h1 class="admin-page-title">WHATSAPP</h1>
                                <p>Prepará mensajes claros para copiar y enviar al cliente según el estado de su venta.</p>
                            </div>
                        </div>
                        <form id="whatsapp-settings-form" class="settings-card">
                            <div class="email-template-grid">
                                <label>PEDIDO INGRESADO<textarea name="whatsapp_message_order_created" rows="4"></textarea></label>
                                <label>RECORDATORIO AMABLE<textarea name="whatsapp_message_cash_created" rows="4"></textarea></label>
                                <label>PEDIDO LISTO PARA RETIRAR<textarea name="whatsapp_message_ready_pickup" rows="4"></textarea></label>
                                <label>VENTA CANCELADA<textarea name="whatsapp_message_cancelled" rows="4"></textarea></label>
                            </div>
                            <p class="form-hint">Podés usar: <code>{{cliente}}</code>, <code>{{pedido}}</code>, <code>{{total}}</code> y <code>{{plazo}}</code>. Si dejás un texto vacío, se usa el mensaje estándar.</p>
                            <button class="primary-button fit-button" type="submit">GUARDAR MENSAJES</button>
                        </form>
                    </section>
                <?php endif ?>

                <section class="admin-view" id="view-pos">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">OPERACIÓN DE MOSTRADOR</p>
                            <h1 class="admin-page-title">PUNTO DE VENTA</h1>
                            <p>Registrá ventas y gestioná el carrito sin salir de la administración.</p>
                        </div>
                    </div>
                    <iframe class="admin-pos-frame" src="pos.php?embedded=1" title="Punto de Venta"></iframe>
                </section>

                <section class="admin-view active" id="view-orders">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">OPERACIÓN DIARIA</p>
                            <h1 class="admin-page-title">LISTA DE VENTAS <small id="open-orders-count"></small></h1>
                            <p>Consultá, imprimí, archivá o cancelá las ventas de la tienda y del mostrador.</p>
                        </div>
                    </div>
                    <div class="order-toolbar">
                        <label class="order-filter-search">
                            <span>BUSCAR</span>
                            <input id="order-search" type="search" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="none" placeholder="N.º de venta, nombre o apellido">
                        </label>
                        <label class="archive-orders-toggle">
                            <input id="show-archived-orders" type="checkbox">
                            <span>MOSTRAR TODAS</span>
                        </label>
                    </div>
                    <div id="order-actions-bar" class="order-actions-bar" hidden>
                        <strong id="selected-orders-count" class="selected-orders-count">0 ventas seleccionadas</strong>
                        <label class="bulk-actions-control">
                            <span id="bulk-order-actions-label">ACCIONES SOBRE LAS VENTAS SELECCIONADAS</span>
                            <select id="bulk-order-action">
                                <option value="">Acciones</option>
                                <option value="print_individual">Imprimir ventas individuales</option>
                                <option value="print_grouped">Imprimir y agrupar ventas</option>
                                <option value="pass_to_deliveries">Pasar Ventas</option>
                                <option value="archive">Archivar Ventas</option>
                                <option value="cancel">Cancelar Ventas</option>
                                <option value="reopen">Reabrir Ventas</option>
                            </select>
                        </label>
                    </div>
                    <div id="order-list" class="order-list"></div>
                    <div class="order-list-footer">
                        <button class="secondary-button" id="refresh-orders" type="button">⇩ Exportar lista</button>
                    </div>
                </section>

                <section class="admin-view" id="view-deliveries">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">ARMADO FÍSICO</p>
                            <h1 class="admin-page-title">ENTREGA DE PEDIDOS</h1>
                        </div>
                    </div>
                    <div id="delivery-copy-guide" class="delivery-copy-guide" hidden></div>
                    <label class="delivery-search" for="delivery-search">
                        <span>BUSCAR EN ENTREGAS Y LISTA DE VENTAS</span>
                        <input id="delivery-search" type="search" placeholder="N.º de venta, nombre o apellido" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="none">
                    </label>
                    <div class="delivery-table-wrap">
                        <table class="delivery-table">
                            <thead><tr><th>N.º</th><th></th><th>Ubicación</th><th></th><th># Orden</th><th>Nombre y Apellido</th><th></th><th>Importe</th><th></th><th>Transferencias</th></tr></thead>
                            <tbody id="delivery-slots"></tbody>
                        </table>
                    </div>
                    <section id="delivery-sales-search-results" class="delivery-sales-search-results" hidden>
                        <h2>LISTA DE VENTAS</h2>
                        <div class="delivery-table-wrap">
                            <table class="delivery-table delivery-sales-search-table">
                                <thead><tr><th>N.º venta</th><th>Nombre y Apellido</th><th>Total</th><th>Productos</th><th>Estado</th><th>Fecha</th></tr></thead>
                                <tbody id="delivery-sales-search-results-body"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <section class="admin-view" id="view-statistics">
                    <div class="view-heading statistics-heading">
                        <div>
                            <p class="eyebrow">PULSO DEL NEGOCIO</p>
                            <h1 class="admin-page-title">ESTADÍSTICAS</h1>
                            <p>Indicadores operativos de ventas archivadas, Entregas y beneficios usados.</p>
                        </div>
                        <form id="statistics-unlock-form" class="statistics-unlock-form">
                            <input name="password" type="password" autocomplete="current-password" placeholder="Contraseña" aria-label="Contraseña para ver importes" required>
                            <button class="primary-button fit-button" type="submit">VER</button>
                        </form>
                    </div>
                    <div id="statistics-content" class="statistics-content"><p class="empty-copy">Calculando estadísticas…</p></div>
                </section>

                <?php if ($user['role'] === 'admin'): ?>
                    <section class="admin-view" id="view-users">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">ACCESO INTERNO</p>
                                <h1 class="admin-page-title">USUARIOS</h1>
                                <p>Administradores y vendedores autorizados.</p>
                            </div>
                            <button class="primary-button fit-button" id="new-user-button" type="button">
                                NUEVO USUARIO
                            </button>
                        </div>
                        <div id="user-list" class="user-list"></div>
                    </section>

                    <section class="admin-view" id="view-settings">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">DATOS DEL COMERCIO</p>
                                <h1 class="admin-page-title">CONFIGURACIÓN</h1>
                                <p>Datos generales del comercio, contacto y retiro en el local.</p>
                            </div>
                        </div>
                        <form id="settings-form" class="settings-card">
                            <div class="settings-grid">
                                <label>
                                    NOMBRE DEL COMERCIO
                                    <input name="store_name" required>
                                </label>
                                <label>
                                    WHATSAPP CON CÓDIGO DE PAÍS
                                    <input name="whatsapp_number" inputmode="numeric" required>
                                </label>
                                <label>
                                    DIRECCIÓN DE RETIRO
                                    <input name="pickup_address">
                                </label>
                                <label>
                                    HORARIOS DE ATENCIÓN
                                    <input name="business_hours" placeholder="Lunes a viernes de 9 a 17 h" required>
                                </label>
                                <label>
                                    TITULAR DE LA CUENTA
                                    <input name="bank_holder" required>
                                </label>
                                <label>
                                    ALIAS
                                    <input name="bank_alias">
                                </label>
                                <label>
                                    CBU / CVU
                                    <input name="bank_cbu" inputmode="numeric" maxlength="22">
                                </label>
                                <input name="payment_window_minutes" type="hidden" value="360">
                            </div>
                            <section class="settings-subsection">
                                <p class="eyebrow">EXPERIENCIA DE COMPRA / RECOMPENSAS</p>
                                <div class="settings-grid">
                                    <label class="checkbox-setting"><input name="reward_surprise_enabled" type="checkbox" value="1"><span><strong>Sorpresa activa</strong></span></label>
                                    <label>DESCUENTO SORPRESA (%)<input name="reward_surprise_percent" type="number" min="1" max="100"></label>
                                    <label>PROBABILIDAD (%)<input name="reward_surprise_probability" type="number" min="0" max="100"></label>
                                    <label>MENSAJE SORPRESA<textarea name="reward_surprise_text" rows="2"></textarea></label>
                                    <label>MENSAJE PARA SEGUIR AGREGANDO<textarea name="reward_surprise_continue_text" rows="2"></textarea></label>
                                    <label class="checkbox-setting"><input name="reward_quantity_enabled" type="checkbox" value="1"><span><strong>Descuento por cantidad activo</strong></span></label>
                                    <label>UNIDADES MÍNIMAS<input name="reward_quantity_units" type="number" min="1"></label>
                                    <label>DESCUENTO POR CANTIDAD (%)<input name="reward_quantity_percent" type="number" min="1" max="100"></label>
                                    <label>TEXTO PREVIO ({{faltan}}, {{porcentaje}})<textarea name="reward_quantity_pending_text" rows="2"></textarea></label>
                                    <label>TEXTO AL DESBLOQUEAR ({{porcentaje}})<textarea name="reward_quantity_unlocked_text" rows="2"></textarea></label>
                                    <label class="checkbox-setting"><input name="reward_cart_animation_enabled" type="checkbox" value="1"><span><strong>Animación del carrito</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_cart_sound_enabled" type="checkbox" value="1"><span><strong>Sonido del carrito</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_checkout_celebration_enabled" type="checkbox" value="1"><span><strong>Celebración al comprar</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_checkout_confetti_enabled" type="checkbox" value="1"><span><strong>Confeti</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_microinteractions_enabled" type="checkbox" value="1"><span><strong>Microinteracciones</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_klaus_enabled" type="checkbox" value="1"><span><strong>Mostrar a Klaus en el carrito</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_klaus_animations_enabled" type="checkbox" value="1"><span><strong>Animaciones de Klaus</strong></span></label>
                                    <label class="checkbox-setting"><input name="reward_klaus_messages_enabled" type="checkbox" value="1"><span><strong>Mensajes de Klaus</strong></span></label>
                                    <label>MENSAJE FELIZ<textarea name="reward_klaus_happy_text" rows="2"></textarea></label>
                                    <label>MENSAJE CERCA DE LA RECOMPENSA<textarea name="reward_klaus_near_text" rows="2"></textarea></label>
                                    <label>MENSAJE SORPRESA<textarea name="reward_klaus_surprise_text" rows="2"></textarea></label>
                                    <label>MENSAJE COMPRA LISTA<textarea name="reward_klaus_complete_text" rows="2"></textarea></label>
                                </div>
                            </section>
                            <section class="settings-subsection">
                                <p class="eyebrow">PULGA / MASCOTA AMBIENTAL</p>
                                <div class="settings-grid">
                                    <label class="checkbox-setting"><input name="pulga_enabled" type="checkbox" value="1"><span><strong>Activar a Pulga</strong></span></label>
                                    <label>MÁXIMO DE ESPERA (30 A 45 SEGUNDOS)<input name="pulga_frequency_seconds" type="number" min="30" max="45"></label>
                                    <label class="checkbox-setting"><input name="pulga_animations_enabled" type="checkbox" value="1"><span><strong>Activar animaciones de Pulga</strong></span></label>
                                </div>
                            </section>
                            <button class="primary-button fit-button" type="submit">
                                GUARDAR CONFIGURACIÓN
                            </button>
                        </form>
                        <section class="settings-card backup-card">
                            <p class="eyebrow">RESPALDOS</p>
                            <h2>Copias seguras</h2>
                            <p>La tarea programada genera una copia automática diaria, verificada, de la base, comprobantes y fotos cargadas. Se conservan las últimas 30 copias automáticas; las manuales no se eliminan solas.</p>
                            <button class="primary-button fit-button" id="create-backup" type="button">CREAR RESPALDO AHORA</button>
                        </section>
                        <form id="ses-test-form" class="settings-card backup-card">
                            <p class="eyebrow">CORREOS AUTOMÁTICOS</p>
                            <h2>Prueba de Amazon SES</h2>
                            <p>Las credenciales se guardan únicamente en el archivo privado del servidor. Este botón no activa los avisos de ventas: solo verifica la conexión con SES.</p>
                            <label>DESTINATARIO DE PRUEBA<input name="recipient" type="email" value="ventas@laboratorio-digital.com.ar" required autocomplete="off"></label>
                            <button class="primary-button fit-button" type="submit">ENVIAR PRUEBA</button>
                        </form>
                    </section>

                    <section class="admin-view" id="view-maintenance">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">OPERACIÓN DE LA TIENDA</p>
                                <h1 class="admin-page-title">MANTENIMIENTO</h1>
                                <p>Podés pausar temporalmente el carrito sin cerrar el catálogo al público.</p>
                            </div>
                        </div>
                        <form id="maintenance-form" class="settings-card">
                            <label class="checkbox-setting">
                                <input name="cart_maintenance_enabled" type="checkbox" value="1">
                                <span>
                                    <strong>Bloquear el carrito de compras</strong>
                                    <small>La tienda seguirá disponible para navegar. Las personas no podrán agregar, modificar ni confirmar productos en el carrito.</small>
                                </span>
                            </label>
                            <button class="primary-button fit-button" type="submit">GUARDAR MANTENIMIENTO</button>
                        </form>
                    </section>
                <?php endif ?>
            </main>
        </div>
    <?php endif ?>

    <div class="modal" id="modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <button class="modal-close" type="button" data-close-modal aria-label="Cerrar">×</button>
            <div id="modal-content"></div>
        </section>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <?php if ($user): ?><button class="admin-klaus" id="admin-klaus" type="button" aria-label="Acariciar a Klaus"><img class="admin-klaus-image" src="<?= $escape($storeAssetPath) ?>/klaus_checkout_sitting.png" alt=""></button><?php endif ?>

    <script id="admin-app-data" type="application/json"><?=
        json_encode([
            'api_url' => $apiUrl,
            'csrf_token' => $app['csrf_token'],
            'user' => $user,
            'setup_required' => $setupRequired,
            'size_guide_url' => $sizeGuideUrl,
            'store_url' => $storeUrl,
            'pulga' => [
                'enabled' => $publicSettings['pulga_enabled'] ?? '1',
                'frequency_seconds' => $publicSettings['pulga_frequency_seconds'] ?? '45',
                'animations_enabled' => $publicSettings['pulga_animations_enabled'] ?? '1',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    ?></script>
    <?php if ($user): ?><script src="<?= $escape($storeAssetPath) ?>/pulga.js?v=<?= $escape($pulgaJsVersion) ?>" defer></script><script src="<?= $escape($storeAssetPath) ?>/klaus.js?v=<?= $escape($klausJsVersion) ?>" defer></script><?php endif ?>
    <script src="<?= $escape($adminAssetPath) ?>/admin.js?v=<?= $escape($adminJsVersion) ?>" defer></script>
</body>
</html>
