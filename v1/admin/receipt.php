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
        <header>
            <p class="brand">LABORATORIO DIGITAL</p>
            <h1>COMPROBANTE INTERNO</h1>
            <strong><?= receiptText($order['public_number']) ?></strong>
        </header>

        <dl>
            <div><dt>Fecha</dt><dd><?= receiptText($order['created_at']) ?></dd></div>
            <div><dt>Cliente</dt><dd><?= receiptText($order['customer_name']) ?></dd></div>
        </dl>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <strong><?= receiptText($item['product_name']) ?></strong><br>
                            <small><?= receiptText($item['variant_name']) ?></small>
                        </td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= receiptMoney((int) $item['unit_price_cents']) ?></td>
                        <td><?= receiptMoney((int) $item['line_total_cents']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <p class="total">
            <span>Total</span>
            <strong><?= receiptMoney((int) $order['total_cents']) ?></strong>
        </p>
        <p class="footer">
            Comprobante interno no fiscal · Retiro en el local
        </p>
    </main>
    <script src="assets/receipt.js"></script>
</body>
</html>
