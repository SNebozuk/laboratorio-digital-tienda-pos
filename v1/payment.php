<?php
declare(strict_types=1);

use LaboratorioDigital\AuthorizationException;
use LaboratorioDigital\Http;

$app = require dirname(__DIR__) . '/app/container.php';
Http::noCache();
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$orderId = filter_input(INPUT_GET, 'order', FILTER_VALIDATE_INT) ?: 0;
$uploadToken = strtolower(trim((string) ($_GET['token'] ?? '')));
$order = null;
$invalidLink = false;

try {
    if ($orderId < 1 || !preg_match('/^[a-f0-9]{48}$/', $uploadToken)) {
        throw new AuthorizationException('Enlace inválido.');
    }
    $order = $app['proofs']->publicStatus($orderId, $uploadToken);
} catch (Throwable $exception) {
    $invalidLink = true;
    http_response_code(404);
}

$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
$money = static fn (int $cents): string => '$ '
    . number_format($cents / 100, 0, ',', '.');
$displayDate = static function (mixed $value): string {
    if (!$value) {
        return '';
    }
    try {
        return (new DateTimeImmutable((string) $value))->format('d/m/Y H:i');
    } catch (Throwable) {
        return (string) $value;
    }
};

$statusMessages = [
    'pending_payment' => 'Subí el comprobante para informar el pago y reservar el stock.',
    'payment_reported' => 'Recibimos el comprobante. El stock está reservado mientras verificamos el pago.',
    'rejected' => 'El comprobante anterior no pudo validarse. Podés cargar uno nuevo dentro del plazo indicado.',
    'paid_prepare' => 'El pago fue aprobado y estamos preparando el pedido.',
    'ready_pickup' => 'El pedido está listo para retirar en el local.',
    'delivered' => 'El pedido fue entregado.',
    'cancelled' => 'El pedido fue cancelado y ya no admite comprobantes.',
];
$statusMessage = $order
    ? ($statusMessages[$order['status']] ?? 'El pedido continúa en proceso.')
    : '';
$isCashOrder = $order && ($order['payment_method'] ?? '') === 'cash';
if ($isCashOrder && in_array($order['status'], ['pending_payment', 'ready_pickup'], true)) {
    $statusMessage = 'Pago en efectivo al retirar. El stock está reservado únicamente hasta el horario indicado.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <title>Seguimiento de pedido · Laboratorio Digital</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="payment-page">
    <header class="store-header">
        <a class="brand" href="./" aria-label="Laboratorio Digital, catálogo">
            <span class="brand-mark">LD</span>
            <span>
                <strong>LABORATORIO DIGITAL</strong>
                <small>SEGUIMIENTO DE PEDIDO</small>
            </span>
        </a>
        <a class="header-link" href="./">VOLVER AL CATÁLOGO</a>
    </header>

    <main class="payment-shell">
        <?php if ($invalidLink): ?>
            <section class="payment-card payment-card-centered">
                <p class="eyebrow">ENLACE NO DISPONIBLE</p>
                <h1>NO PUDIMOS ABRIR EL PEDIDO</h1>
                <p class="payment-lead">
                    El enlace es incorrecto o fue reemplazado por uno más nuevo.
                    Revisá el último email recibido de Laboratorio Digital.
                </p>
                <a class="primary-button button-link" href="./">IR AL CATÁLOGO</a>
            </section>
        <?php else: ?>
            <section class="payment-card">
                <div class="payment-heading">
                    <div>
                        <p class="eyebrow">PEDIDO <?= $escape($order['public_number']) ?></p>
                        <h1>SEGUIMIENTO Y PAGO</h1>
                    </div>
                    <span
                        class="status-pill status-<?= $escape($order['status']) ?>"
                        id="payment-status"
                    >
                        <?= $escape($order['status_label']) ?>
                    </span>
                </div>

                <div class="payment-status-copy" id="payment-status-copy">
                    <?= $escape($statusMessage) ?>
                </div>

                <div class="payment-total">
                    <span>Total del pedido</span>
                    <strong><?= $escape($money((int) $order['total_cents'])) ?></strong>
                </div>

                <div class="payment-items" aria-label="Productos del pedido">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="payment-item">
                            <span>
                                <strong><?= $escape($item['product_name']) ?></strong>
                                <small>
                                    <?= (int) $item['quantity'] ?> ×
                                    <?= $escape($item['variant_name']) ?>
                                </small>
                            </span>
                            <strong><?= $escape($money((int) $item['line_total_cents'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($isCashOrder && in_array($order['status'], ['pending_payment', 'ready_pickup'], true)): ?>
                    <div class="cash-confirmation">
                        <span>Reserva para pago en efectivo</span>
                        <strong>SOLO POR 2 HORAS</strong>
                        <span>Hasta el <?= $escape($displayDate($order['deadline_at'])) ?> h</span>
                    </div>
                    <div class="cash-expiry-explanation">
                        <strong>Retirá y pagá antes de ese horario.</strong>
                        <p>Al vencer el plazo, el pedido se cancela automáticamente y los productos vuelven al stock para otros clientes.</p>
                    </div>
                <?php elseif ($order['can_upload']): ?>
                    <section class="bank-panel" aria-labelledby="bank-title">
                        <p class="eyebrow" id="bank-title">DATOS DE TRANSFERENCIA</p>
                        <div class="bank-row">
                            <span>Titular</span>
                            <strong><?= $escape($order['bank']['holder']) ?></strong>
                        </div>
                        <div class="bank-row">
                            <span>Alias</span>
                            <strong><?= $escape($order['bank']['alias'] ?: 'Pendiente de configurar') ?></strong>
                            <?php if ($order['bank']['alias']): ?>
                                <button class="copy-button" type="button" data-copy="<?= $escape($order['bank']['alias']) ?>">COPIAR</button>
                            <?php endif; ?>
                        </div>
                        <div class="bank-row">
                            <span>CBU</span>
                            <strong><?= $escape($order['bank']['cbu'] ?: 'Pendiente de configurar') ?></strong>
                            <?php if ($order['bank']['cbu']): ?>
                                <button class="copy-button" type="button" data-copy="<?= $escape($order['bank']['cbu']) ?>">COPIAR</button>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="deadline-box">
                        Podés cargar el comprobante hasta el
                        <strong><?= $escape($displayDate($order['deadline_at'])) ?> h</strong>.
                        <?php if ($order['status'] === 'rejected'): ?>
                            El stock continúa reservado durante este plazo.
                        <?php else: ?>
                            Al cargarlo volveremos a validar y reservar el stock disponible.
                        <?php endif; ?>
                    </div>

                    <form class="payment-upload" id="public-proof-form">
                        <label for="public-proof">
                            Comprobante JPG, PNG o PDF
                            <small>
                                Máximo
                                <?= max(1, (int) ceil($order['proof_max_bytes'] / 1024 / 1024)) ?>
                                MB
                            </small>
                        </label>
                        <input
                            id="public-proof"
                            name="proof"
                            type="file"
                            accept="image/jpeg,image/png,application/pdf"
                            required
                        >
                        <button class="primary-button" type="submit">
                            SUBIR COMPROBANTE
                        </button>
                    </form>
                    <div class="form-feedback" id="payment-feedback" role="status" aria-live="polite"></div>

                <?php elseif ($order['status'] === 'pending_payment' || $order['status'] === 'rejected'): ?>
                    <div class="deadline-box deadline-expired">
                        El plazo para cargar el comprobante venció. La cancelación
                        y liberación del stock se completarán automáticamente.
                    </div>
                <?php endif; ?>

                <?php if ($order['pickup_address']): ?>
                    <p class="pickup-copy">
                        Retiro únicamente en:
                        <strong><?= $escape($order['pickup_address']) ?></strong>
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php if (!$invalidLink): ?>
        <script id="payment-data" type="application/json"><?=
            json_encode([
                'api_url' => '../api.php',
                'csrf_token' => $app['csrf_token'],
                'order_id' => $order['id'],
                'upload_token' => $uploadToken,
                'proof_max_bytes' => $order['proof_max_bytes'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
        ?></script>
        <script src="assets/payment.js" defer></script>
    <?php endif; ?>
</body>
</html>
