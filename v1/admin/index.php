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
$assetVersion = static fn (string $path): string => (string) (filemtime($path) ?: 1);
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
    <link rel="icon" href="<?= $escape($storeAssetPath) ?>/favicon.png?v=20260826" type="image/png">
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
            <aside class="admin-sidebar">
                <div class="admin-sidebar-brand-row">
                    <a class="brand admin-brand" href="./">
                        <span class="brand-mark">LD</span>
                        <span>
                            <strong>LABORATORIO DIGITAL</strong>
                            <small>ADMINISTRACIÓN</small>
                        </span>
                    </a>
                    <button class="icon-button admin-sidebar-toggle" id="admin-sidebar-toggle" type="button" aria-expanded="true" aria-label="Plegar menú lateral" title="Plegar menú lateral">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"></path></svg>
                    </button>
                </div>
                <nav class="admin-nav">
                    <div class="admin-nav-sales">
                        <button class="admin-nav-button admin-nav-orders" type="button" data-view="orders">Lista de Ventas <b id="orders-badge" class="nav-notification-badge" hidden>0</b></button>
                        <button class="admin-nav-button admin-nav-deliveries" type="button" data-view="deliveries">Entrega de pedidos <b id="deliveries-badge" class="nav-notification-badge" hidden>0</b></button>
                    </div>
                    <button class="admin-nav-button" type="button" data-view="statistics">Estadísticas</button>
                    <div class="admin-nav-products">
                        <button class="admin-nav-button" type="button" data-view="products">
                            Productos
                        </button>
                        <?php if ($user['role'] === 'admin'): ?>
                            <button class="admin-nav-button admin-nav-subitem" type="button" data-view="supplier-order">Pedido a proveedor</button>
                        <?php endif ?>
                    </div>
                    <button class="admin-nav-button" type="button" data-open-pos>Punto de Venta</button>
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
                                <label>ENLACE DEL LOGO<input name="logo_link" placeholder="https://... o /v1/"></label>
                                <label>REEMPLAZAR LOGO<input name="logo_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="logo_path" type="hidden"></label>
                                <button id="restore-default-logo" class="secondary-button" type="button">USAR LOGO ORIGINAL</button>
                                <img id="design-logo-preview" class="admin-sidebar-design-image" alt="Vista previa del logo">
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
                    <button class="secondary-button admin-store-link" id="admin-store-button" type="button">VER TIENDA ↗</button>
                    <div class="admin-user-icon-actions">
                        <?php if ($user['role'] === 'admin'): ?>
                            <button class="icon-button admin-settings-icon" id="admin-sidebar-settings-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-sidebar-settings-menu" aria-label="Abrir configuración" title="Configuración">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.09a2 2 0 0 1 1 1.73v.5a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.73l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        <?php endif ?>
                        <button class="icon-button admin-logout-icon" id="logout-button" type="button" aria-label="Salir" title="Salir">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16M6 21V3h11v18M13 12h.01"></path></svg>
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
                            <div class="supplier-order-actions">
                                <button class="secondary-button fit-button" type="button" data-supplier-order-back>VOLVER</button>
                                <button class="secondary-button fit-button" type="button" data-supplier-order-preview>VER TEXTO PARA WHATSAPP</button>
                                <button class="secondary-button fit-button" type="button" data-supplier-order-copy>COPIAR PEDIDO</button>
                                <button class="danger-button fit-button" type="button" data-supplier-order-clear>VACIAR PEDIDO</button>
                            </div>
                        </div>
                        <form id="supplier-order-filters" class="supplier-order-filters">
                            <div class="supplier-order-category-filter">
                                <button id="supplier-order-categories-trigger" class="supplier-order-category-trigger" type="button" aria-expanded="false" aria-controls="supplier-order-category-popover">CATEGORÍAS</button>
                                <div id="supplier-order-category-popover" class="supplier-order-category-popover" hidden>
                                    <div class="supplier-order-category-backdrop" data-close-supplier-order-categories></div>
                                    <section class="supplier-order-category-dialog" role="dialog" aria-modal="true" aria-labelledby="supplier-order-category-title" tabindex="-1">
                                        <header>
                                            <div><p class="eyebrow">FILTRO DEL PEDIDO</p><h2 id="supplier-order-category-title">CATEGORÍAS</h2></div>
                                            <button class="icon-action-button" type="button" data-close-supplier-order-categories aria-label="Cerrar categorías" title="Cerrar categorías"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                        </header>
                                        <p>Podés elegir más de una categoría.</p>
                                        <div id="supplier-order-categories" class="supplier-order-category-list" role="group" aria-label="Categorías"></div>
                                        <footer><button class="primary-button fit-button" type="button" data-apply-supplier-order-categories>APLICAR</button></footer>
                                    </section>
                                </div>
                            </div>
                            <label>
                                PALABRAS CLAVE
                                <input id="supplier-order-keywords" name="keywords" type="search" autocomplete="off" placeholder="Nombre, descripción, SKU, código o variante">
                            </label>
                            <label>
                                MOSTRAR PRODUCTOS CON STOCK MENOR A
                                <input id="supplier-order-threshold" name="stock_threshold" type="number" inputmode="numeric" min="0" max="1000000" step="1" value="1" required>
                            </label>
                            <div class="supplier-order-filter-actions">
                                <button class="primary-button fit-button" type="submit">BUSCAR PRODUCTOS</button>
                                <button class="icon-action-button" type="button" data-supplier-order-reset-filters aria-label="Limpiar filtros" title="Limpiar filtros">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6.5 7l1 13h9l1-13M10 11v5M14 11v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                        </form>
                        <p id="supplier-order-status" class="supplier-order-status" aria-live="polite"></p>
                        <div id="supplier-order-results" class="supplier-order-results"></div>
                        <section id="supplier-order-preview" class="supplier-order-preview" hidden>
                            <div>
                                <p class="eyebrow">MENSAJE EDITABLE</p>
                                <h2>TEXTO PARA WHATSAPP</h2>
                            </div>
                            <textarea id="supplier-order-whatsapp-text" rows="8" maxlength="20000" aria-label="Texto para WhatsApp"></textarea>
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
                            <header class="design-preview-header"><img id="design-preview-logo" alt="Logo de la tienda"><span>MENÚ</span><span>CARRITO</span></header>
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

                <section class="admin-view active" id="view-orders">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">OPERACIÓN DIARIA</p>
                            <h1 class="admin-page-title">LISTA DE VENTAS <small id="open-orders-count"></small></h1>
                            <p>Consultá, imprimí, archivá o cancelá las ventas de la tienda y del mostrador.</p>
                        </div>
                        <div class="order-page-actions">
                            <div class="order-quick-links">
                                <button class="order-quick-link" id="open-deliveries" type="button" title="Atajo: F2">→ ENTREGA DE PEDIDOS <small>F2</small></button>
                                <button class="order-quick-link order-quick-link-pos" type="button" data-open-pos title="Atajo: F3">⊕ ABRIR PUNTO DE VENTA <small>F3</small></button>
                            </div>
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
                        <span>BUSCAR EN ENTREGAS</span>
                        <input id="delivery-search" type="search" placeholder="N.º de venta, nombre o apellido" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="none">
                    </label>
                    <div class="delivery-table-wrap">
                        <table class="delivery-table">
                            <thead><tr><th>N.º</th><th></th><th>Ubicación</th><th></th><th># Orden</th><th>Nombre y Apellido</th><th></th><th>Importe</th><th></th><th>Transferencias</th></tr></thead>
                            <tbody id="delivery-slots"></tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-view" id="view-statistics">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">PULSO DEL NEGOCIO</p>
                            <h1 class="admin-page-title">ESTADÍSTICAS</h1>
                            <p>Una mirada simple a las ventas archivadas, Entregas y beneficios usados.</p>
                        </div>
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
