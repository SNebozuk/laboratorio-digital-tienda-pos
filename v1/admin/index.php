<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
$user = $app['auth']->user();
$setupRequired = (int) $app['pdo']->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
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
    <link rel="icon" href="<?= $escape($storeAssetPath) ?>/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $escape($storeAssetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($adminAssetPath) ?>/admin.css?v=<?= $escape($assetVersion) ?>">
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
                <a class="brand admin-brand" href="./">
                    <span class="brand-mark">LD</span>
                    <span>
                        <strong>LABORATORIO DIGITAL</strong>
                        <small>ADMINISTRACIÓN</small>
                    </span>
                </a>
                <nav class="admin-nav">
                    <div class="admin-nav-sales">
                        <button class="admin-nav-button active admin-nav-orders" type="button" data-view="orders">Lista de Ventas <b id="orders-badge" class="nav-notification-badge" hidden>0</b></button>
                        <button class="admin-nav-button" type="button" data-view="deliveries">Entrega de pedidos</button>
                    </div>
                    <button class="admin-nav-button" type="button" data-view="products">
                        Productos
                    </button>
                    <a class="admin-nav-button" href="pos.php" target="laboratorio-pos" rel="opener">Punto de Venta</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <button class="admin-nav-button" type="button" data-view="tutorials">
                            Aprende
                        </button>
                        <button class="admin-nav-button" type="button" data-view="categories">
                            Categorías
                        </button>
                        <button class="admin-nav-button" type="button" data-view="size-guide">
                            Tabla de Talles
                        </button>
                        <button class="admin-nav-button" type="button" data-view="contact">
                            Contacto
                        </button>
                        <button class="admin-nav-button" type="button" data-view="design">
                            Diseño
                        </button>
                        <button class="admin-nav-button" type="button" data-view="whatsapp">
                            WhatsApp
                        </button>
                        <button class="admin-nav-button" type="button" data-view="users">
                            Usuarios
                        </button>
                        <button class="admin-nav-button" type="button" data-view="settings">
                            Configuración
                        </button>
                        <button class="admin-nav-button" type="button" data-view="maintenance">
                            Mantenimiento
                        </button>
                    <?php endif ?>
                </nav>
                <div class="admin-user">
                    <span class="admin-greeting">Hola, <?= htmlspecialchars(explode(' ', $user['name'])[0], ENT_QUOTES, 'UTF-8') ?> 👋</span>
                    <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></small>
                    <a class="secondary-button admin-store-link" href="<?= $escape($storeUrl) ?>" target="_blank" rel="noopener">VER TIENDA ↗</a>
                    <button class="secondary-button" id="logout-button" type="button">Salir</button>
                </div>
            </aside>

            <main class="admin-main">
                <header class="admin-mobile-header">
                    <strong>LABORATORIO DIGITAL</strong>
                    <select id="mobile-view">
                        <option value="orders">Lista de Ventas</option>
                        <option value="deliveries">Entrega de Pedidos</option>
                        <option value="products">Productos</option>
                        <?php if ($user['role'] === 'admin'): ?>
                            <option value="tutorials">Aprende</option>
                            <option value="categories">Categorías</option>
                            <option value="size-guide">Tabla de Talles</option>
                            <option value="contact">Contacto</option>
                            <option value="design">Diseño</option>
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
                        <div class="view-heading"><div><p class="eyebrow">PORTADA DE LA TIENDA</p><h1 class="admin-page-title">DISEÑO</h1><p>Modificá los textos, el logo y los enlaces visibles sin tocar el código.</p></div></div>
                        <form id="design-form" class="settings-card">
                            <details class="design-panel" open>
                                <summary>CONTENIDO PRINCIPAL</summary>
                                <div class="settings-grid">
                                    <label>ETIQUETA SUPERIOR<input name="hero_badge" maxlength="120" required></label>
                                    <label>TÍTULO PRINCIPAL<input name="hero_title" maxlength="160" required></label>
                                    <label class="settings-span-two">TEXTO PRINCIPAL<textarea name="hero_text" rows="3" maxlength="500" required></textarea></label>
                                    <label>ENLACE DEL TÍTULO (OPCIONAL)<input name="hero_link" placeholder="https://... o /v1/"></label>
                                </div>
                            </details>
                            <details class="design-panel">
                                <summary>IDENTIDAD VISUAL</summary>
                                <div class="design-branding" aria-labelledby="design-branding-title">
                                    <div><strong id="design-branding-title">LOGO</strong><small>Este logo se muestra en la cabecera de la tienda. Podés elegir otra imagen cuando quieras.</small></div>
                                    <label>ENLACE DEL LOGO (OPCIONAL)<input name="logo_link" placeholder="https://... o /v1/"></label>
                                    <label>REEMPLAZAR LOGO<input name="logo_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="logo_path" type="hidden"></label>
                                    <div class="design-logo-actions"><button id="restore-default-logo" class="secondary-button" type="button">USAR LOGO LABORATORIO DIGITAL</button><span>Formatos: JPG, PNG o WebP.</span></div>
                                    <img id="design-logo-preview" class="variant-image-preview" alt="Vista previa del logo">
                                </div>
                            </details>
                            <details class="design-panel">
                                <summary>FOTOS DE PORTADA</summary>
                                <div class="design-published-images"><small>Elegí una foto para reemplazarla. Se verá inmediatamente en la vista previa al guardar.</small><div class="design-image-grid">
                                    <label>FOTO 1<input name="hero_1_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="hero_1_path" type="hidden"><img id="design-hero-1-preview" alt="Foto publicada 1"></label>
                                    <label>FOTO 2<input name="hero_2_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="hero_2_path" type="hidden"><img id="design-hero-2-preview" alt="Foto publicada 2"></label>
                                    <label>FOTO 3<input name="hero_3_file" type="file" accept="image/jpeg,image/png,image/webp"><input name="hero_3_path" type="hidden"><img id="design-hero-3-preview" alt="Foto publicada 3"></label>
                                </div></div>
                            </details>
                            <details class="design-panel">
                                <summary>ORDEN DE LAS SECCIONES</summary>
                                <p class="design-panel-help">Elegí la posición de cada sección de la portada. Los bloques sin contenido no se mostrarán.</p>
                                <input name="section_order" type="hidden">
                                <div class="design-section-order">
                                    <label>PRODUCTOS DESTACADOS<select data-design-section="featured"><option value="1">1.º</option><option value="2">2.º</option><option value="3">3.º</option><option value="4">4.º</option></select></label>
                                    <label>FOTOS DE PORTADA<select data-design-section="gallery"><option value="1">1.º</option><option value="2">2.º</option><option value="3">3.º</option><option value="4">4.º</option></select></label>
                                    <label>CATEGORÍAS<select data-design-section="categories"><option value="1">1.º</option><option value="2">2.º</option><option value="3">3.º</option><option value="4">4.º</option></select></label>
                                    <label>TUTORIALES<select data-design-section="tutorials"><option value="1">1.º</option><option value="2">2.º</option><option value="3">3.º</option><option value="4">4.º</option></select></label>
                                </div>
                            </details>
                            <button class="primary-button fit-button" type="submit">GUARDAR DISEÑO</button>
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
                                <a class="order-quick-link" href="pos.php" target="laboratorio-pos" rel="opener" title="Atajo: F3">⊕ ABRIR PUNTO DE VENTA <small>F3</small></a>
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
                    <a href="./?view=orders" class="pos-back-link delivery-back-link">← Administración</a>
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">ARMADO FÍSICO</p>
                            <h1 class="admin-page-title">ENTREGA DE PEDIDOS</h1>
                            <p>Las ubicaciones del 1 al 100 son fijas. Editá directamente cada casillero.</p>
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
                            <button class="primary-button fit-button" type="submit">
                                GUARDAR CONFIGURACIÓN
                            </button>
                        </form>
                        <section class="settings-card backup-card">
                            <p class="eyebrow">RESPALDOS</p>
                            <h2>Copias seguras</h2>
                            <p>Se genera una copia automática diaria de la base y las fotos cargadas. Se conservan las últimas 30 copias automáticas; las manuales no se eliminan solas.</p>
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

    <script id="admin-app-data" type="application/json"><?=
        json_encode([
            'api_url' => $apiUrl,
            'csrf_token' => $app['csrf_token'],
            'user' => $user,
            'setup_required' => $setupRequired,
            'size_guide_url' => $sizeGuideUrl,
            'store_url' => $storeUrl,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    ?></script>
    <script src="<?= $escape($adminAssetPath) ?>/admin.js?v=<?= $escape($assetVersion) ?>" defer></script>
</body>
</html>
