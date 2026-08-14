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
                    <button class="admin-nav-button" type="button" data-view="orders">
                        Lista de Ventas
                    </button>
                    <button class="admin-nav-button active" type="button" data-view="products">
                        Productos
                    </button>
                    <button class="admin-nav-button" type="button" data-view="pos">
                        Punto de Venta
                    </button>
                    <?php if ($user['role'] === 'admin'): ?>
                        <button class="admin-nav-button" type="button" data-view="categories">
                            Categorías
                        </button>
                        <button class="admin-nav-button" type="button" data-view="size-guide">
                            Tabla de Talles
                        </button>
                        <button class="admin-nav-button" type="button" data-view="contact">
                            Contacto
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
                    <?php endif ?>
                </nav>
                <div class="admin-user">
                    <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></small>
                    <button class="secondary-button" id="logout-button" type="button">Salir</button>
                </div>
            </aside>

            <main class="admin-main">
                <header class="admin-mobile-header">
                    <strong>LABORATORIO DIGITAL</strong>
                    <select id="mobile-view">
                        <option value="orders">Lista de Ventas</option>
                        <option value="products">Productos</option>
                        <option value="pos">Punto de Venta</option>
                        <?php if ($user['role'] === 'admin'): ?>
                            <option value="categories">Categorías</option>
                            <option value="size-guide">Tabla de Talles</option>
                            <option value="contact">Contacto</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="users">Usuarios</option>
                            <option value="settings">Configuración</option>
                        <?php endif ?>
                    </select>
                </header>

                <section class="admin-view active" id="view-products">
                    <div class="view-heading order-page-heading">
                        <div>
                            <p class="eyebrow">CATÁLOGO Y STOCK</p>
                            <h1>PRODUCTOS</h1>
                            <p>Precio y stock se editan directamente por variante.</p>
                        </div>
                        <button class="primary-button fit-button" id="new-product-button" type="button">
                            NUEVO PRODUCTO
                        </button>
                    </div>
                    <div class="admin-search">
                        <input id="admin-product-search" type="search" placeholder="Buscar por título, variante, SKU o código">
                    </div>
                    <div id="admin-product-list"></div>
                </section>

                <?php if ($user['role'] === 'admin'): ?>
                    <section class="admin-view" id="view-categories">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">ORGANIZACIÓN DEL CATÁLOGO</p>
                                <h1>CATEGORÍAS</h1>
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
                                <h1>TABLA DE TALLES</h1>
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
                                <h1>CONTACTO</h1>
                                <p>Estos datos se muestran en la tienda y alimentan el botón de WhatsApp.</p>
                            </div>
                        </div>
                        <form id="contact-form" class="settings-card">
                            <div class="settings-grid">
                                <label>NOMBRE DEL COMERCIO<input name="store_name" required></label>
                                <label>EMAIL DE VENTAS<input name="sales_email" type="email" required></label>
                                <label>WHATSAPP CON CÓDIGO DE PAÍS<input name="whatsapp_number" inputmode="numeric" required></label>
                                <label>DIRECCIÓN DE RETIRO<input name="pickup_address"></label>
                                <label>HORARIOS DE ATENCIÓN<input name="business_hours" required></label>
                            </div>
                            <button class="primary-button fit-button" type="submit">GUARDAR CONTACTO</button>
                        </form>
                    </section>

                    <section class="admin-view" id="view-whatsapp">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">AVISOS DE PEDIDOS</p>
                                <h1>WHATSAPP</h1>
                                <p>Configurá el remitente, servidor SMTP y los mensajes que recibe el cliente.</p>
                            </div>
                        </div>
                        <form id="whatsapp-settings-form" class="settings-card">
                            <div class="email-settings-section">
                                <label class="checkbox-setting"><input name="mail_enabled" type="checkbox" value="1"><span><strong>ACTIVAR ENVÍO AUTOMÁTICO</strong><small>Solo activalo después de probar la casilla. La contraseña SMTP no se muestra ni se guarda en el navegador.</small></span></label>
                            </div>
                            <div class="settings-grid">
                                <label>CASILLA REMITENTE<input name="mail_from" type="email" required></label>
                                <label>NOMBRE DEL REMITENTE<input name="mail_from_name" required></label>
                                <label>RESPONDER A<input name="mail_reply_to" type="email" required></label>
                                <label>SERVIDOR SMTP<input name="mail_smtp_host" required placeholder="a0160161.ferozo.com"></label>
                                <label>PUERTO SMTP<input name="mail_smtp_port" type="number" min="1" max="65535" required></label>
                                <label>CIFRADO<select name="mail_smtp_encryption"><option value="ssl">SSL · recomendado para puerto 465</option><option value="tls">TLS / STARTTLS</option><option value="none">Sin cifrado</option></select></label>
                                <label>USUARIO SMTP<input name="mail_smtp_username" type="email" required></label>
                            </div>
                            <p class="form-hint">Para proteger la casilla, la contraseña SMTP se conserva únicamente en la configuración privada del servidor, fuera de esta pantalla.</p>
                            <div class="email-diagnostics" id="email-diagnostics" aria-live="polite"></div>
                            <button class="secondary-button fit-button" id="refresh-mail-diagnostics" type="button">REVISAR ESTADO DEL ENVÃO</button>
                            <div class="email-template-grid">
                                <label>PEDIDO POR TRANSFERENCIA<textarea name="whatsapp_message_order_created" rows="4"></textarea></label>
                                <label>PEDIDO EN EFECTIVO<textarea name="whatsapp_message_cash_created" rows="4"></textarea></label>
                                <label>PEDIDO LISTO PARA RETIRAR<textarea name="whatsapp_message_ready_pickup" rows="4"></textarea></label>
                                <label>PEDIDO CANCELADO<textarea name="whatsapp_message_cancelled" rows="4"></textarea></label>
                            </div>
                            <p class="form-hint">Podés usar: <code>{{cliente}}</code>, <code>{{pedido}}</code>, <code>{{total}}</code> y <code>{{plazo}}</code>. Si dejás un texto vacío, se usa el mensaje estándar.</p>
                            <button class="primary-button fit-button" type="submit">GUARDAR E-MAILS</button>
                        </form>
                    </section>
                <?php endif ?>

                <section class="admin-view" id="view-pos">
                    <div class="view-heading pos-launch-heading">
                        <div>
                            <p class="eyebrow">MOSTRADOR</p>
                            <h1>PUNTO DE VENTA</h1>
                            <p>Usá una pantalla completa para buscar o escanear productos y registrar la venta con comodidad.</p>
                        </div>
                    </div>
                    <section class="pos-launch-card">
                        <div class="pos-launch-icon" aria-hidden="true">⌁</div>
                        <div>
                            <strong>CAJA Y MOSTRADOR</strong>
                            <p>El Punto de Venta se abre en una página independiente, optimizada para trabajar con teclado, lector de código y pantalla completa.</p>
                        </div>
                        <a class="primary-button" href="pos.php">ABRIR PUNTO DE VENTA</a>
                    </section>
                </section>

                <section class="admin-view" id="view-orders">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">OPERACIÓN DIARIA</p>
                            <h1>VENTAS <small id="open-orders-count"></small></h1>
                            <p>Controlá pagos, preparación, retiros y ventas de mostrador.</p>
                        </div>
                        <div class="order-page-actions">
                            <button class="secondary-button" id="order-auto-cancel-info" type="button">⚙ Cancelación automática</button>
                            <button class="secondary-button" id="refresh-orders" type="button">⇩ Exportar lista</button>
                            <button class="primary-button" type="button" data-view="pos">⊕ Agregar orden de compra</button>
                        </div>
                    </div>
                    <div class="order-toolbar">
                        <label class="order-filter-search">
                            <span>BUSCAR</span>
                            <input id="order-search" type="search" placeholder="N.º de venta, nombre o apellido">
                        </label>
                        <label>
                            <span>ORIGEN</span>
                            <select id="order-channel-filter">
                                <option value="">Todos los orígenes</option>
                                <option value="web">Tienda web</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="pos">Mostrador</option>
                            </select>
                        </label>
                        <label>
                            <span>PERÍODO</span>
                            <select id="order-date-filter">
                                <option value="">Cualquier fecha</option>
                                <option value="today">Hoy</option>
                                <option value="week">Últimos 7 días</option>
                                <option value="month">Este mes</option>
                            </select>
                        </label>
                        <label class="archive-orders-toggle">
                            <input id="show-archived-orders" type="checkbox">
                            <span>MOSTRAR TODAS</span>
                        </label>
                    </div>
                    <div id="order-actions-bar" class="order-actions-bar" hidden>
                        <strong id="selected-orders-count" class="selected-orders-count">0 ventas seleccionadas</strong>
                        <label class="bulk-actions-control">
                            <span>ACCIONES SOBRE LAS VENTAS SELECCIONADAS</span>
                            <select id="bulk-order-action">
                                <option value="">Acciones</option>
                                <option value="archive">Archivar Ventas</option>
                                <option value="cancel">Cancelar Ventas</option>
                                <option value="reopen">Reabrir Ventas</option>
                            </select>
                        </label>
                    </div>
                    <div id="order-list" class="order-list"></div>
                </section>

                <?php if ($user['role'] === 'admin'): ?>
                    <section class="admin-view" id="view-users">
                        <div class="view-heading">
                            <div>
                                <p class="eyebrow">ACCESO INTERNO</p>
                                <h1>USUARIOS</h1>
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
                                <h1>CONFIGURACIÓN</h1>
                                <p>Transferencias, plazos, contacto y retiro en el local.</p>
                            </div>
                        </div>
                        <form id="settings-form" class="settings-card">
                            <div class="settings-grid">
                                <label>
                                    NOMBRE DEL COMERCIO
                                    <input name="store_name" required>
                                </label>
                                <label>
                                    EMAIL DE VENTAS
                                    <input name="sales_email" type="email" required>
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
                                <label>
                                    PLAZO PARA INFORMAR PAGO · MINUTOS
                                    <input name="payment_window_minutes" type="number" min="15" max="10080" step="15" required>
                                </label>
                                <label>
                                    PLAZO TRAS RECHAZO · MINUTOS
                                    <input name="rejected_retry_minutes" type="number" min="15" max="10080" step="15" required>
                                </label>
                            </div>
                            <button class="primary-button fit-button" type="submit">
                                GUARDAR CONFIGURACIÓN
                            </button>
                        </form>
                        <section class="settings-card demo-reset-card">
                            <p class="eyebrow">MODO PRUEBA</p>
                            <h2>Limpiar datos de prueba</h2>
                            <p>Vacía todas las ventas y conserva cuatro productos con fotos y variantes para realizar pruebas.</p>
                            <button class="danger-button" id="reset-demo-data" type="button">VACIAR VENTAS Y DEJAR 4 PRODUCTOS</button>
                        </section>
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
