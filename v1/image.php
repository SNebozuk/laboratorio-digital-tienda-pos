<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$path = (string) ($_GET['path'] ?? '');
if (!preg_match('#^\d{4}/\d{2}/[a-f0-9]{48}\.(?:jpg|png|webp)$#', $path)) {
    http_response_code(404);
    exit;
}

$root = rtrim((string) $app['config']['storage_path'], '/\\') . '/uploads/products';
$file = $root . '/' . $path;
if (!is_file($file) || !is_readable($file)) {
    http_response_code(404);
    exit;
}

$mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($file));
header('Cache-Control: public, max-age=604800, immutable');
header('X-Content-Type-Options: nosniff');
readfile($file);
