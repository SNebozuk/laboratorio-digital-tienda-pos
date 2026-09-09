<?php
declare(strict_types=1);

$app = require __DIR__ . '/app/container.php';
$storePath = trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storeUrl = 'https://laboratoriodigital.com.ar/' . ($storePath === '' ? '' : $storePath . '/');
header('Content-Type: application/xml; charset=UTF-8');
$escapeXml = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?= $escapeXml($storeUrl) ?></loc></url>
    <?php foreach ($app['products']->publicCatalog() as $product): ?>
    <url><loc><?= $escapeXml($storeUrl . '?producto=' . $product['id']) ?></loc></url>
    <?php endforeach ?>
</urlset>
