<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$adminAssetPath = $storePath . '/admin/assets';
$assetVersion = static fn (string $path): string => substr(hash_file('sha256', $path) ?: '1', 0, 12);
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Buscador IA · Laboratorio Digital</title>
    <link rel="icon" href="<?= $escape($storePath) ?>/favicon.php" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/app.css?v=<?= $escape($assetVersion(__DIR__ . '/assets/app.css')) ?>">
    <link rel="stylesheet" href="<?= $escape($adminAssetPath) ?>/admin.css?v=<?= $escape($assetVersion(__DIR__ . '/admin/assets/admin.css')) ?>">
</head>
<body class="admin-body">
    <main class="admin-main">
        <section class="admin-view active ai-search-view" id="view-ai-search">
            <div class="view-heading ai-search-heading">
                <div>
                    <h1 class="admin-page-title">BUSCADOR IA</h1>
                    <p>Probá búsquedas sobre el catálogo real. Esta página es solo de consulta.</p>
                </div>
            </div>

            <div class="ai-search-layout">
                <section class="ai-search-chat settings-card" aria-label="Búsqueda de productos">
                    <div class="ai-search-messages" id="ai-search-messages">
                        <div class="ai-search-message ai-search-message-assistant"><small>ASISTENTE</small><p>Escribí una búsqueda para consultar el catálogo real.</p></div>
                    </div>
                    <div class="ai-search-products" id="ai-search-products" aria-label="Resultados del catálogo"></div>
                    <div class="ai-search-composer">
                        <textarea id="ai-search-input" rows="2" placeholder="Escribí como lo haría un cliente…" aria-label="Mensaje de prueba"></textarea>
                        <div class="ai-search-composer-actions">
                            <button class="primary-button" id="ai-search-submit" type="button">BUSCAR</button>
                        </div>
                    </div>
                </section>
                <aside class="ai-search-interpretation settings-card">
                    <p class="eyebrow">INTERPRETACIÓN</p>
                    <dl id="ai-search-interpretation">
                        <div><dt>Búsqueda</dt><dd>Sin consulta</dd></div>
                        <div><dt>Resultados</dt><dd>—</dd></div>
                    </dl>
                </aside>
            </div>
        </section>
    </main>
    <script>window.aiSearchPreview = { apiUrl: <?= json_encode($storePath . '/api.php', JSON_UNESCAPED_SLASHES) ?>, storeUrl: <?= json_encode($storePath === '' ? '/' : $storePath . '/', JSON_UNESCAPED_SLASHES) ?> };</script>
    <script src="<?= $escape($assetPath) ?>/search-normalizer.js?v=<?= $escape($assetVersion(__DIR__ . '/assets/search-normalizer.js')) ?>"></script>
    <script src="<?= $escape($assetPath) ?>/ai-search-preview.js?v=<?= $escape($assetVersion(__DIR__ . '/assets/ai-search-preview.js')) ?>"></script>
</body>
</html>
