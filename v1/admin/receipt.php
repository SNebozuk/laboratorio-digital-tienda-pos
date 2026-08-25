<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
if (!$app['auth']->user()) { header('Location: ./'); exit; }
function receiptText(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function receiptMoney(int $cents): string { return '$ ' . number_format($cents / 100, 0, ',', '.'); }
function receiptDiscountSource(mixed $type): string {
    $labels = ['quantity' => 'por cantidad', 'surprise' => 'sorpresa', 'klaus' => 'premio de Klaus'];
    $sources = array_filter(explode('+', (string) $type));
    return implode(' + ', array_map(static fn (string $source): string => $labels[$source] ?? 'promoción', $sources)) ?: 'promoción';
}
function receiptArgentinaDate(mixed $value): string {
    $source = trim((string) $value);
    if ($source === '') return '';
    try {
        $utc = new DateTimeZone('UTC');
        $argentina = new DateTimeZone('America/Argentina/Buenos_Aires');
        return (new DateTimeImmutable($source, $utc))->setTimezone($argentina)->format('d/m/Y · H:i');
    } catch (Throwable) {
        return $source;
    }
}
$rawIds = isset($_GET['ids']) ? explode(',', (string) $_GET['ids']) : [(string) ($_GET['id'] ?? '')];
$ids = array_values(array_unique(array_filter(array_map('intval', $rawIds), static fn (int $id): bool => $id > 0)));
if (!$ids) { http_response_code(404); exit; }
$orders = array_map(static fn (int $id): array => $app['orders']->orderDetail($id), array_slice($ids, 0, 30));
$batch = count($orders) > 1;
$individual = $batch && (string) ($_GET['layout'] ?? '') === 'individual';
$title = $individual ? 'VENTAS INDIVIDUALES' : ($batch ? 'VENTAS SELECCIONADAS' : $orders[0]['public_number']);
$assetVersion = substr(hash('sha256', (string) @file_get_contents(__DIR__ . '/assets/receipt.css') . (string) @file_get_contents(__DIR__ . '/assets/receipt.js')), 0, 12);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" href="../assets/favicon.png" type="image/png"><title><?= receiptText($batch ? 'Ventas seleccionadas' : $orders[0]['public_number']) ?></title><link rel="stylesheet" href="assets/receipt.css?v=<?= receiptText($assetVersion) ?>"></head><body>
<main class="receipt <?= $batch ? 'receipt-batch' : '' ?> <?= $individual ? 'receipt-individual' : '' ?>">
<header class="receipt-header"><div class="receipt-title-row"><div><h1><?= receiptText($title) ?></h1><p><?= $batch ? count($orders) . ($individual ? ' ventas, una por hoja.' : ' ventas incluidas en esta impresión.') : 'Controlá cada producto antes de entregarlo.' ?></p></div><div class="receipt-logo-text" aria-label="Laboratorio Digital"><strong>LABORATORIO</strong><span>DIGITAL</span></div></div></header>
<?php foreach ($orders as $order):
    $items = $order['items'];
    usort($items, static fn (array $a, array $b): int => strnatcasecmp((string) $a['product_name'], (string) $b['product_name']) ?: strnatcasecmp((string) $a['variant_name'], (string) $b['variant_name']));
    $itemsByProduct = [];
    foreach ($items as $item) {
        $itemsByProduct[(string) $item['product_name']][] = $item;
    }
    $unitCount = array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $items)); ?>
<?php
    $deliveryLocation = '';
    if (!empty($order['delivery_slot_number'])) {
        $locationQuery = $app['pdo']->prepare('SELECT location FROM delivery_slots WHERE slot_number = :slot');
        $locationQuery->execute(['slot' => (int) $order['delivery_slot_number']]);
        $deliveryLocation = trim((string) $locationQuery->fetchColumn());
    }
?>
<section class="receipt-order"><?php if ($batch): ?><h2><?= receiptText($order['public_number']) ?></h2><?php endif; ?><?php if (!empty($order['delivery_slot_number'])): ?><div class="receipt-delivery-slot"><strong><?= (int) $order['delivery_slot_number'] ?></strong><?php if ($deliveryLocation !== ''): ?><span>UBICACIÓN <?= receiptText($deliveryLocation) ?></span><?php endif; ?></div><?php endif; ?>
<dl><div><dt>Cliente</dt><dd><?= receiptText($order['customer_name']) ?></dd></div><div><dt>Contacto</dt><dd><?= receiptText($order['customer_phone'] ?: 'Sin teléfono informado') ?></dd></div><div><dt>Fecha</dt><dd><?= receiptText(receiptArgentinaDate($order['created_at'])) ?></dd></div></dl>
<div class="receipt-items"><?php foreach ($itemsByProduct as $productName => $productItems): ?><section class="receipt-product-group"><h3><?= receiptText($productName) ?></h3><?php foreach ($productItems as $item): ?><div class="receipt-item"><span class="quantity"><?= (int) $item['quantity'] ?></span><span class="receipt-variant"><?= preg_match('/^única$/iu', trim((string) $item['variant_name'])) === 1 ? 'Sin variante' : receiptText($item['variant_name']) ?></span><span class="receipt-item-details"><?php if (trim((string) $item['sku']) !== ''): ?>SKU <?= receiptText($item['sku']) ?> · <?php endif; ?><?= receiptMoney((int) $item['unit_price_cents']) ?></span></div><?php endforeach; ?></section><?php endforeach; ?></div>
<p class="total"><span><?= $unitCount ?> <?= $unitCount === 1 ? 'unidad' : 'unidades' ?> · Subtotal <?= receiptMoney((int) $order['subtotal_cents']) ?><?php if ((int) ($order['discount_cents'] ?? 0) > 0): ?><br>Descuento <?= receiptText(receiptDiscountSource($order['discount_type'] ?? '')) ?> <?= (int) $order['discount_percent'] ?>%: −<?= receiptMoney((int) $order['discount_cents']) ?><?php endif; ?><br>Total</span><strong><?= receiptMoney((int) $order['total_cents']) ?></strong></p></section>
<?php endforeach; ?><p class="footer">Comprobante interno no fiscal · Retiro en el local</p></main><script src="assets/receipt.js?v=<?= receiptText($assetVersion) ?>"></script></body></html>
