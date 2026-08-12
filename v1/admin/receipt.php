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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= receiptText($order['public_number']) ?></title>
    <link rel="stylesheet" href="assets/receipt.css">
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
                    <th>OK</th><th>Producto</th>
                    <th>Cant.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td class="check-cell"><span class="check-box"></span></td>
                        <td><div class="receipt-product"><?php if (!empty($item['image_path'])): ?><img src="<?= receiptText($item['image_path']) ?>" alt=""><?php endif ?><span><strong><?= receiptText($item['product_name']) ?></strong><small><?= receiptText($item['variant_name']) ?> · SKU: <?= receiptText($item['sku']) ?></small></span></div></td>
                        <td class="quantity"><?= (int) $item['quantity'] ?></td>
                        <td><?= receiptMoney((int) $item['line_total_cents']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <p class="total">
            <span><?= $unitCount ?> <?= $unitCount === 1 ? 'unidad' : 'unidades' ?> · Total</span>
            <strong><?= receiptMoney((int) $order['total_cents']) ?></strong>
        </p>
        <section class="preparation-check"><strong>Control final</strong><span><i></i> Productos completos</span><span><i></i> Cliente identificado</span><span><i></i> Entrega verificada</span></section>
        <p class="footer">
            Comprobante interno no fiscal · Retiro en el local
        </p>
    </main>
    <script src="assets/receipt.js"></script>
</body>
</html>
