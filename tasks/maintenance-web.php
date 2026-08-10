<?php
declare(strict_types=1);

use LaboratorioDigital\Config;

if (PHP_SAPI === 'cli') {
    http_response_code(404);
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow', true);

$projectRoot = dirname(__DIR__);
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

http_response_code(204);
