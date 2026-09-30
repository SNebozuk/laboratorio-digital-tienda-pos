<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/WhatsAppWorkspace.php';
$config = require dirname(__DIR__, 2) . '/app/whatsapp-config.php';
header('Cache-Control: no-store');
if (!$config['enabled'] || $config['app_secret'] === '' || $config['verify_token'] === '') { http_response_code(503); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['hub_mode'] ?? '') !== 'subscribe' || !hash_equals($config['verify_token'],(string)($_GET['hub_verify_token'] ?? ''))) { http_response_code(403); exit; }
    header('Content-Type: text/plain');
    echo (string)($_GET['hub_challenge'] ?? '');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$raw = file_get_contents('php://input', false, null, 0, 2 * 1024 * 1024 + 1);
if (!is_string($raw) || strlen($raw) > 2 * 1024 * 1024) { http_response_code(413); exit; }
$signature = 'sha256=' . hash_hmac('sha256',$raw,$config['app_secret']);
if (!hash_equals($signature,(string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ''))) { http_response_code(403); exit; }
$payload = json_decode($raw,true);
if (!is_array($payload) || ($payload['object'] ?? '') !== 'whatsapp_business_account') { http_response_code(400); exit; }
try {
    $app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
    session_write_close();
    $workspace = new WhatsAppWorkspace($app['pdo'],$config);
    $workspace->ingest($payload);
    header('Content-Type: text/plain'); echo 'EVENT_RECEIVED';
} catch (Throwable $e) { http_response_code(500); }
