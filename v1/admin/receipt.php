<?php
declare(strict_types=1);

$app = require dirname(__DIR__, 2) . '/app/container.php';
if (!$app['auth']->user()) {
    header('Location: ./');
    exit;
}
$order = $app['orders']->orderDetail((int) ($_GET['id'] ?? 0));

header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

function receiptText(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function receiptMoney(int $cents): string
{
    return '$ ' . number_format($cents / 100, 0, ',', '.');
}
$unitCount = array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $order['items']));
$items = $order['items'];
usort($items, static function (array $first, array $second): int {
    $byProduct = strnatcasecmp((string) $first['product_name'], (string) $second['product_name']);
    return $byProduct !== 0
        ? $byProduct
        : strnatcasecmp((string) $first['variant_name'], (string) $second['variant_name']);
});
$receiptAssetVersion = substr(hash(
    'sha256',
    (string) @file_get_contents(__DIR__ . '/assets/receipt.css')
        . (string) @file_get_contents(__DIR__ . '/assets/receipt.js')
), 0, 12);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= receiptText($order['public_number']) ?></title>
    <link rel="stylesheet" href="assets/receipt.css?v=<?= receiptText($receiptAssetVersion) ?>">
</head>
<body>
    <main class="receipt">
        <header class="receipt-header">
            <div class="receipt-title-row">
                <div>
                    <h1><?= receiptText($order['public_number']) ?></h1>
                    <p>Control&aacute; cada producto antes de entregarlo.</p>
                </div>
                <div class="receipt-logo-text" aria-label="Laboratorio Digital">
                    <strong>LABORATORIO</strong>
                    <span>DIGITAL</span>
                </div>
            </div>
        </header>

        <dl>
            <div><dt>Cliente</dt><dd><?= receiptText($order['customer_name']) ?></dd></div>
            <div><dt>Contacto</dt><dd><?= receiptText($order['customer_phone'] ?: 'Sin teléfono informado') ?></dd></div>
            <div><dt>Fecha</dt><dd><?= receiptText($order['created_at']) ?></dd></div>
        </dl>

        <table>
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Variante</th>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="quantity"><?= (int) $item['quantity'] ?></td>
                        <td class="receipt-variant"><?= preg_match('/^única$/iu', trim((string) $item['variant_name'])) === 1 ? '—' : receiptText($item['variant_name']) ?></td>
                        <?php $productLength = function_exists('mb_strlen') ? mb_strlen((string) $item['product_name'], 'UTF-8') : strlen((string) $item['product_name']); ?>
                        <td class="receipt-product <?= $productLength > 70 ? 'receipt-product-very-long' : ($productLength > 52 ? 'receipt-product-long' : '') ?>"><?= receiptText($item['product_name']) ?></td>
                        <td class="receipt-sku"><?= receiptText($item['sku']) ?></td>
                        <td class="receipt-price"><?= receiptMoney((int) $item['unit_price_cents']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <p class="total">
            <span><?= $unitCount ?> <?= $unitCount === 1 ? 'unidad' : 'unidades' ?> · Total</span>
            <strong><?= receiptMoney((int) $order['total_cents']) ?></strong>
        </p>
        <p class="footer">
            Comprobante interno no fiscal · Retiro en el local
        </p>
    </main>
    <script src="assets/receipt.js?v=<?= receiptText($receiptAssetVersion) ?>"></script>
</body>
</html>
