<?php
declare(strict_types=1);

// SSH/CLI only. There is no HTTP endpoint and no new credential to store.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', 'stderr');
$app = require dirname(__DIR__) . '/app/container.php';
require_once dirname(__DIR__) . '/app/AdminConnector.php';
session_write_close();
$connector = new LaboratorioDigital\AdminConnector($app);
while (($line = fgets(STDIN, 2 * 1024 * 1024 + 1)) !== false) {
    try {
        if (strlen($line) > 2 * 1024 * 1024 || !str_ends_with($line, "\n")) throw new InvalidArgumentException('Solicitud demasiado extensa o incompleta.');
        $request = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($request) || !is_string($request['tool'] ?? null) || !is_array($request['arguments'] ?? null)) throw new InvalidArgumentException('Solicitud inválida.');
        $result = ['ok' => true, 'result' => $connector->call($request['tool'], $request['arguments'])];
    } catch (Throwable $error) { $result = ['ok' => false, 'error' => $connector->safeError($error)]; }
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
    flush();
}
