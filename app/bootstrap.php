<?php
declare(strict_types=1);

use LaboratorioDigital\Config;
use LaboratorioDigital\Database;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';

$projectRoot = dirname(__DIR__);
$config = Config::load($projectRoot);

date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Conserva el acceso administrativo durante 30 días, sin volverlo permanente.
    $sessionLifetime = 60 * 60 * 24 * 30;
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    session_name($config['session_name']);
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        // Ferozo puede terminar HTTPS delante de PHP. Consideramos también el
        // encabezado del proxy para conservar la cookie como segura.
        'secure' => (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        ),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = Database::connect(
    $config['database_path'],
    $projectRoot . '/database/schema.sql'
);

return [
    'root' => $projectRoot,
    'config' => $config,
    'pdo' => $pdo,
    'csrf_token' => $_SESSION['csrf_token'],
];
