<?php
declare(strict_types=1);
$app = require dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/OrderTracking.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
$tracking = (new LaboratorioDigital\OrderTracking($app['pdo']))->lookup((string) ($_GET['token'] ?? ''));
if (!$tracking) http_response_code(404);
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$money = static fn ($value) => '$ ' . number_format((int) $value / 100, 2, ',', '.');
$messages = ['Recibida' => 'Recibimos tu pedido y está en nuestra lista de ventas.', 'En preparación' => 'Tu pedido está en Entrega de pedidos, pendiente de armado.', 'Listo para entregar' => 'Tu pedido ya está preparado para entregar.', 'Cancelada' => 'Esta venta fue cancelada.', 'Archivada' => 'Esta venta ya no está activa.'];
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Seguimiento de compras · Laboratorio Digital</title><link rel="stylesheet" href="assets/seguimiento.css"></head>
<body><main><header><p class="brand">Laboratorio Digital</p><h1>Seguimiento de tus compras</h1><p>Consultá el estado actualizado y el detalle de tus pedidos.</p></header>
<?php if (!$tracking): ?><section><h2>Enlace no disponible</h2><p>Revisá el enlace recibido en el email de tu compra.</p></section>
<?php else: ?>
<?php foreach (array_merge([$tracking['order']], $tracking['others']) as $index => $order): ?>
<?php if ($index === 1): ?><h2 class="other">Tus otras compras activas</h2><?php endif; ?>
<section><div class="order-heading"><h2>Pedido <?= $escape($order['number']) ?></h2><span class="status"><?= $escape($order['state']) ?></span></div>
<p><?= $escape($messages[$order['state']]) ?></p><p class="date">Compra realizada el <?= $escape(date('d/m/Y', strtotime($order['created_at']))) ?></p>
<ul><?php foreach ($order['items'] as $item): ?><li><div><strong><?= $escape($item['product_name']) ?></strong><?php if ($item['variant_name'] && !preg_match('/^única$/iu', $item['variant_name'])): ?><span><?= $escape($item['variant_name']) ?></span><?php endif; ?><span><?= (int) $item['quantity'] ?> × <?= $money($item['unit_price_cents']) ?></span></div><strong><?= $money($item['line_total_cents']) ?></strong></li><?php endforeach; ?></ul>
<?php if ($order['discount_cents']): ?><p>Descuento: <?= $money($order['discount_cents']) ?></p><?php endif; ?><p class="total">Total <strong><?= $money($order['total_cents']) ?></strong></p></section>
<?php endforeach; ?>
<?php if (!$tracking['others']): ?><p>No tenés otras compras activas.</p><?php endif; ?><a class="refresh" href="?token=<?= $escape($_GET['token']) ?>">Actualizar estado</a>
<?php endif; ?><footer>Guardá este enlace para volver a consultar tus compras.</footer></main></body></html>
