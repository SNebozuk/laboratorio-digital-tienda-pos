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
$app['pdo']->prepare("DELETE FROM mail_queue WHERE subject = 'Prueba de avisos · Laboratorio Digital'")->execute();
$send = new ReflectionMethod($app['mail'], 'send');
$send->setAccessible(true);
try {
    $send->invoke(
        $app['mail'],
        (string) $config['sales_notification_email'],
        'Prueba de avisos · Laboratorio Digital',
        '<p>La configuración de avisos internos funciona correctamente.</p>'
    );
    echo json_encode(['sent' => true], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['sent' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}
