<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
$user = $app['auth']->user();
$setupRequired = (int) $app['pdo']->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;

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
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="assets/admin.css">
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
                        <label>Contraseña<input name="password" type="password" minlength="12" required autocomplete="new-password"></label>
                        <label>Clave de instalación<input name="setup_token" type="password" required autocomplete="off"></label>
                        <button class="primary-button" type="submit">CREAR ADMINISTRADOR</button>
                    </form>
                <?php else: ?>
                    <p class="eyebrow">ACCESO INTERNO</p>
                    <h1>INGRESAR</h1>
                    <form id="login-form">
                        <label>Email<input name="email" type="email" required autocomplete="username"></label>
                        <label>Contraseña<input name="password" type="password" required autocomplete="current-password"></label>
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
                    <button class="admin-nav-button active" type="button" data-view="products">
                        Productos
                    </button>
                    <button class="admin-nav-button" type="button" data-view="pos">
                        Punto de venta
                    </button>
                    <button class="admin-nav-button" type="button" data-view="orders">
                        Ventas y pedidos
                    </button>
                    <button class="admin-nav-button" type="button" data-view="cash">
                        Caja
                    </button>
                    <button class="admin-nav-button" type="button" data-view="reports">
                        Reportes
                    </button>
                    <?php if ($user['role'] === 'admin'): ?>
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
                        <option value="products">Productos</option>
                        <option value="pos">Punto de venta</option>
                        <option value="orders">Ventas y pedidos</option>
                        <option value="cash">Caja</option>
                        <option value="reports">Reportes</option>
                        <?php if ($user['role'] === 'admin'): ?>
                            <option value="users">Usuarios</option>
                            <option value="settings">Configuración</option>
                        <?php endif ?>
                    </select>
                </header>

                <section class="admin-view active" id="view-products">
                    <div class="view-heading">
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

                <section class="admin-view" id="view-pos">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">MOSTRADOR</p>
                            <h1>PUNTO DE VENTA</h1>
                            <p>Buscá o escaneá un código y cargá varios productos.</p>
                        </div>
                    </div>
                    <div class="pos-shell">
                        <div>
                            <div class="search-wrap pos-search-wrap">
                                <label for="pos-search">Buscar productos</label>
                                <input id="pos-search" type="search" autocomplete="off" placeholder="Producto, talle, SKU o código de barras">
                                <div id="pos-suggestions" class="suggestions"></div>
                            </div>
                            <div id="pos-products" class="pos-products"></div>
                        </div>
                        <aside class="pos-cart">
                            <p class="eyebrow">VENTA ACTUAL</p>
                            <h2>RESUMEN</h2>
                            <div id="pos-cart-lines" class="cart-lines"></div>
                            <div class="order-total">
                                <span>Total</span>
                                <strong id="pos-total">$ 0</strong>
                            </div>
                            <label>Cliente<input id="pos-customer" value="Consumidor final"></label>
                            <label>
                                Medio de pago
                                <select id="pos-payment">
                                    <option value="cash">Efectivo</option>
                                    <option value="bank_transfer">Transferencia</option>
                                    <option value="debit_card">Débito</option>
                                    <option value="credit_card">Crédito</option>
                                </select>
                            </label>
                            <button class="primary-button" id="complete-sale-button" type="button" disabled>
                                COBRAR E IMPRIMIR
                            </button>
                        </aside>
                    </div>
                </section>

                <section class="admin-view" id="view-orders">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">HISTORIAL ÚNICO</p>
                            <h1>VENTAS Y PEDIDOS</h1>
                            <p>Pedidos web y ventas de mostrador en una sola lista.</p>
                        </div>
                        <button class="secondary-button fit-button" id="refresh-orders" type="button">
                            ACTUALIZAR
                        </button>
                    </div>
                    <div id="order-list" class="order-list"></div>
                </section>

                <section class="admin-view" id="view-cash">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">CONTROL DIARIO</p>
                            <h1>CAJA</h1>
                            <p>Apertura, movimientos y arqueo de cierre.</p>
                        </div>
                    </div>
                    <div id="cash-content"></div>
                </section>

                <section class="admin-view" id="view-reports">
                    <div class="view-heading">
                        <div>
                            <p class="eyebrow">INFORMACIÓN OPERATIVA</p>
                            <h1>REPORTES</h1>
                            <p>Ventas, pedidos activos, stock bajo y movimientos recientes.</p>
                        </div>
                        <div class="button-row">
                            <button class="secondary-button fit-button" id="refresh-reports" type="button">
                                ACTUALIZAR
                            </button>
                            <?php if ($user['role'] === 'admin'): ?>
                                <button class="primary-button fit-button" id="create-backup" type="button">
                                    CREAR RESPALDO
                                </button>
                            <?php endif ?>
                        </div>
                    </div>
                    <div id="report-content"></div>
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
                                <label>
                                    TAMAÑO MÁXIMO DEL COMPROBANTE · MB
                                    <input name="proof_max_mb" type="number" min="1" max="20" step="1" required>
                                </label>
                            </div>
                            <button class="primary-button fit-button" type="submit">
                                GUARDAR CONFIGURACIÓN
                            </button>
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
            'api_url' => '../../api.php',
            'csrf_token' => $app['csrf_token'],
            'user' => $user,
            'setup_required' => $setupRequired,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    ?></script>
    <script src="assets/admin.js" defer></script>
</body>
</html>
