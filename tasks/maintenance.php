<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$app = require dirname(__DIR__) . '/app/container.php';

$expired = $app['stock']->expireOrders();
$mail = $app['mail']->process(30);

echo json_encode([
    'ok' => true,
    'expired_orders' => $expired,
    'mail' => $mail,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
echo PHP_EOL;
