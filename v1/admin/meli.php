<?php
declare(strict_types=1);

use LaboratorioDigital\Http;
use LaboratorioDigital\MercadoLibreService;

$app = require dirname(__DIR__, 2) . '/app/container.php';
require_once $app['root'] . '/app/MercadoLibreService.php';
Http::noCache();
header('Referrer-Policy: no-referrer');
$callback = isset($_GET['code']) || isset($_GET['error']) || isset($_GET['state']);
$lock = null;
try {
    $app['auth']->requireAdmin();
    $service = new MercadoLibreService($app['pdo'], $app['config']);
    // Refresh tokens are single-use: serialize verification and authorization.
    $lock = fopen($app['config']['storage_path'] . '/meli-oauth.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Hay una verificación en curso. Intentá nuevamente en unos segundos.');
    }
    if ($callback) {
        $service->callback($_GET);
        $_SESSION['meli_result'] = 'Autorización recibida. Verificá la conexión.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = Http::input();
        Http::requireCsrf($input);
        $authorizedAction = true;
        $result = match ($input['action'] ?? '') {
            'listing_status' => $service->changeListingStatus((string) ($input['item_id'] ?? ''), (string) ($input['status'] ?? '')),
            'delete_listing' => $service->deleteListing((string) ($input['item_id'] ?? '')),
            'size_chart' => $service->sizeChart((string) ($input['chart_id'] ?? '')),
            'preview_listing_price' => $service->previewListingPrice((string) ($input['item_id'] ?? '')),
            'apply_listing_price' => $service->applyListingPrice((string) ($input['item_id'] ?? ''), (string) ($input['quote_token'] ?? '')),
            'sync_listing_stock' => $service->synchronizeListingStock((string) ($input['item_id'] ?? ''), (int) $app['auth']->user()['id']),
            'calculate_price' => $service->calculateProductPrice($input),
            'publication_variants' => $service->publicationVariants((int) ($input['product_id'] ?? 0)),
            'finish_publication' => $service->finishPublication((int) ($input['product_id'] ?? 0)),
            'validate_publication' => $service->validatePublication((int) ($input['product_id'] ?? 0), (int) ($input['variant_id'] ?? 0)),
            'publish_product' => $service->publishProduct((int) ($input['product_id'] ?? 0), (int) ($input['variant_id'] ?? 0)),
            default => ['authorization_url' => $service->authorizationUrl()],
        };
        if (($input['action'] ?? '') === 'publication_variants' && !empty($result['variants'])) {
            $service->publicationError((int) $input['product_id'], 'Publicación de talles en curso o incompleta. Reintentá para completar los talles pendientes.');
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['action'] ?? '') === 'product_requirements') {
            $result = $service->productRequirements((string) ($_GET['category_id'] ?? ''));
        } elseif (($_GET['action'] ?? '') === 'published_products') {
            $result = $service->publishedProducts((int) ($_GET['offset'] ?? 0));
        } else {
            $result = $service->status();
            $result['redirect_uri'] = $app['config']['meli_redirect_uri'];
            $result['authorization_result'] = $_SESSION['meli_result'] ?? '';
            unset($_SESSION['meli_result']);
        }
    } else {
        Http::json(['connected' => false, 'message' => 'Método no permitido.'], 405);
    }
} catch (LaboratorioDigital\AuthorizationException $error) {
    Http::json(['connected' => false, 'message' => $error->getMessage()], 403);
} catch (Throwable $error) {
    $message = $error instanceof RuntimeException && !($error instanceof PDOException)
        ? $error->getMessage() : 'No se pudo comprobar la conexión. Intentá nuevamente.';
    if (!empty($authorizedAction) && isset($service, $input) && in_array($input['action'] ?? '', ['publication_variants', 'validate_publication', 'publish_product', 'finish_publication'], true) && (int) ($input['product_id'] ?? 0) > 0) {
        $service->publicationError((int) $input['product_id'], $message);
    }
    if ($callback) $_SESSION['meli_result'] = $message;
    else $result = ['connected' => false, 'configured' => isset($service) && $service->configured(), 'message' => $message];
} finally {
    if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
}
if ($callback) {
    header('Location: index.php?view=meli', true, 303);
    exit;
}
Http::json($result);
