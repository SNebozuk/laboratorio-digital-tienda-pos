<?php
declare(strict_types=1);

use LaboratorioDigital\Config;

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/app/Config.php';

$config = Config::load($projectRoot);
$token = isset($_GET['token']) && is_string($_GET['token']) ? trim($_GET['token']) : '';
if (
    $token === ''
    || !hash_equals((string) ($config['maintenance_token_hash'] ?? ''), hash('sha256', $token))
) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$app = require $projectRoot . '/app/container.php';
$insert = $app['pdo']->prepare(
    'INSERT INTO mail_queue(recipient, subject, template, payload_json)
     VALUES(:recipient, :subject, :template, :payload)'
);
$insert->execute([
    'recipient' => (string) $config['sales_notification_email'],
    'subject' => 'Prueba de avisos · Laboratorio Digital',
    'template' => 'internal_order',
    'payload' => json_encode([
        'audience' => 'internal',
        'public_number' => '#PRUEBA',
        'customer_name' => 'Prueba del sistema',
        'customer_phone' => '—',
        'items' => [],
        'total_cents' => 0,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
]);

echo json_encode($app['mail']->process(1), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
