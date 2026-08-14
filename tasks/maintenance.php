<?php
declare(strict_types=1);

use LaboratorioDigital\Config;

$projectRoot = dirname(__DIR__);

if (PHP_SAPI !== 'cli') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow', true);

    require_once $projectRoot . '/app/Config.php';
    $config = Config::load($projectRoot);
    $token = isset($_GET['token']) && is_string($_GET['token'])
        ? trim($_GET['token'])
        : '';
    $expectedHash = (string) ($config['maintenance_token_hash'] ?? '');

    if (
        $token === ''
        || $expectedHash === ''
        || !hash_equals($expectedHash, hash('sha256', $token))
    ) {
        http_response_code(404);
        exit;
    }

    $app = require $projectRoot . '/app/container.php';
    $app['stock']->expireOrders();
    $app['backups']->createDailyIfDue();
    http_response_code(204);
    exit;
}

$app = require $projectRoot . '/app/container.php';
$expired = $app['stock']->expireOrders();
$backup = $app['backups']->createDailyIfDue();

echo json_encode([
    'ok' => true,
    'expired_orders' => $expired,
    'automatic_backup_created' => $backup !== null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
echo PHP_EOL;
