<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/Http.php';
require_once dirname(__DIR__, 2) . '/app/Auth.php';
require_once dirname(__DIR__, 2) . '/app/WhatsAppWorkspace.php';
use LaboratorioDigital\Http;
try {
    $app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
    (new LaboratorioDigital\Auth($app['pdo']))->requireAdmin();
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET','POST'], true)) Http::json(['ok'=>false,'error'=>'Método no admitido.'],405);
    $input = $method === 'POST' ? Http::input() : [];
    if ($method === 'POST') Http::requireCsrf($input);
    session_write_close();
    $workspace = new WhatsAppWorkspace($app['pdo'], require dirname(__DIR__, 2) . '/app/whatsapp-config.php');
    $action = (string)($_GET['action'] ?? 'state');
    if ($method==='GET' && $action==='media') {
        $media=$workspace->media((string)($_GET['id']??''));
        header('Content-Type: '.$media['mime']);header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="whatsapp-'.preg_replace('/\D/','',(string)($_GET['id']??'')).'"');
        echo $media['bytes'];exit;
    }
    if ($method === 'GET') {
        $result = match ($action) {
            'state' => $workspace->state(),
            'inbox' => $workspace->inbox(isset($_GET['phone']) ? (string)$_GET['phone'] : null),
            'templates' => $workspace->templates(isset($_GET['after']) ? (string)$_GET['after'] : null),
            'profile' => $workspace->request('GET', (string)(require dirname(__DIR__, 2) . '/app/whatsapp-config.php')['phone_number_id'], ['fields'=>'display_phone_number,verified_name,quality_rating,status']),
            default => throw new InvalidArgumentException('Acción inválida.'),
        };
    } else {
        $result = match ($action) {
            'save' => $workspace->save($input),
            'submit' => $workspace->submit((int)($input['id'] ?? 0)),
            'delete' => $workspace->delete((int)($input['id'] ?? 0)),
            'send' => $workspace->send($input, $_FILES['file'] ?? null),
            default => throw new InvalidArgumentException('Acción inválida.'),
        };
    }
    Http::json(['ok'=>true] + $result);
} catch (LaboratorioDigital\AuthorizationException $e) {
    Http::json(['ok'=>false,'error'=>$e->getMessage()],403);
} catch (PDOException $e) {
    Http::json(['ok'=>false,'error'=>'No se pudo acceder al almacenamiento de WhatsApp.'],500);
} catch (InvalidArgumentException|RuntimeException $e) {
    Http::json(['ok'=>false,'error'=>$e->getMessage()],422);
} catch (Throwable $e) {
    Http::json(['ok'=>false,'error'=>'No se pudo completar la operación.'],500);
}
