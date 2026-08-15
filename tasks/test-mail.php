<?php
declare(strict_types=1);

use LaboratorioDigital\Config;

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/app/Config.php';
$config = Config::load($projectRoot);
$token = isset($_GET['token']) && is_string($_GET['token']) ? trim($_GET['token']) : '';
if ($token === '' || !hash_equals((string) $config['maintenance_token_hash'], hash('sha256', $token))) {
    http_response_code(404);
    exit;
}

$app = require $projectRoot . '/app/container.php';
$recipient = (string) $config['sales_notification_email'];
$insert = $app['pdo']->prepare(
    'INSERT INTO mail_queue(order_id, recipient, subject, template, payload_json)
     VALUES(NULL, :recipient, :subject, :template, :payload)'
);
$insert->execute([
    'recipient' => $recipient,
    'subject' => 'Prueba de avisos · Laboratorio Digital',
    'template' => 'diagnostic',
    'payload' => json_encode([
        'audience' => 'internal',
        'public_number' => '#PRUEBA',
        'customer_name' => 'Prueba del sistema',
        'customer_phone' => '—',
        'total_cents' => 0,
        'items' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
]);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
echo json_encode($app['mail']->process(1), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
