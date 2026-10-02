<?php
declare(strict_types=1);

header('Cache-Control: no-store');
header('Content-Type: text/plain; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$raw = file_get_contents('php://input', false, null, 0, 16385);
if (!is_string($raw) || strlen($raw) > 16384) { http_response_code(413); exit; }
$event = json_decode($raw, true);
if (!is_array($event)) { http_response_code(400); exit; }
require_once dirname(__DIR__, 2) . '/app/MercadoLibreService.php';
require_once dirname(__DIR__, 2) . '/app/Http.php';
try {
    $app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
    session_write_close();
    $meli = new LaboratorioDigital\MercadoLibreService($app['pdo'], $app['config']);
    $meli->receiveNotification($event);
} catch (InvalidArgumentException) { http_response_code(400); exit; }
catch (Throwable) { http_response_code(503); exit; }

// Persist first and acknowledge before making requests to MeLi.
ignore_user_abort(true);
header('Content-Length: 2');
echo 'OK';
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
else { while (ob_get_level() > 0) ob_end_flush(); flush(); }
try { $meli->processNotifications(); } catch (Throwable $error) { error_log('MeLi: no se pudo procesar la cola de stock.'); }
