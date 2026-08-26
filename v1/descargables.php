<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$design = $app['settings']->design();
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$assetVersion = substr(hash('sha256', (string) @file_get_contents(__DIR__ . '/assets/app.css') . (string) @file_get_contents(__DIR__ . '/assets/light.css')), 0, 12);
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
header('Location: ' . $storeUrl, true, 302);
exit;
