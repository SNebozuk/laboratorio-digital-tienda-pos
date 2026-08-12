<?php
declare(strict_types=1);

use LaboratorioDigital\AuthorizationException;
use LaboratorioDigital\ConflictException;
use LaboratorioDigital\Http;
use LaboratorioDigital\ValidationException;

$app = require __DIR__ . '/app/container.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'catalog':
                Http::json([
                    'ok' => true,
                    'products' => $app['products']->publicCatalog(),
                    'categories' => $app['categories']->tree(),
                ]);

            case 'catalog_search':
                Http::json([
                    'ok' => true,
                    'product_ids' => $app['products']->publicCodeMatches(
                        (string) ($_GET['q'] ?? '')
                    ),
                ]);

            case 'size_guide':
                Http::json([
                    'ok' => true,
                    'size_guide' => $app['settings']->sizeGuide(),
                ]);

            case 'session':
                Http::json([
                    'ok' => true,
                    'user' => $app['auth']->user(),
                    'csrf_token' => $app['csrf_token'],
                ]);

            case 'admin_products':
                $app['auth']->requireUser();
                Http::json([
                    'ok' => true,
                    'products' => $app['products']->adminCatalog(),
                ]);

            case 'admin_categories':
                $app['auth']->requireUser();
                Http::json(['ok' => true, 'categories' => $app['categories']->tree()]);

            case 'orders':
                $app['auth']->requireUser();
                Http::json([
                    'ok' => true,
                    'orders' => $app['orders']->recentOrders(
                        (int) ($_GET['limit'] ?? 100),
                        (bool) ($_GET['include_archived'] ?? false)
                    ),
                ]);

            case 'order':
                $app['auth']->requireUser();
                Http::json([
                    'ok' => true,
                    'order' => $app['orders']->orderDetail(
                        (int) ($_GET['id'] ?? 0)
                    ),
                ]);

            case 'backups':
                $app['auth']->requireAdmin();
                Http::json([
                    'ok' => true,
                    'backups' => $app['backups']->recent(),
                ]);

            case 'settings':
                $app['auth']->requireAdmin();
                Http::json([
                    'ok' => true,
                    'settings' => $app['settings']->values(),
                ]);

            case 'users':
                $app['auth']->requireAdmin();
                Http::json([
                    'ok' => true,
                    'users' => $app['auth']->users(),
                ]);

            case 'payment_proof':
                $app['auth']->requireUser();
                $file = $app['proofs']->protectedFile((int) ($_GET['id'] ?? 0));
                Http::noCache();
                header('Content-Type: ' . $file['mime_type']);
                header(
                    'Content-Disposition: inline; filename="'
                    . rawurlencode($file['original_name'])
                    . '"'
                );
                header('Content-Length: ' . filesize($file['path']));
                readfile($file['path']);
                exit;

            case 'payment_proof_analysis':
                $app['auth']->requireUser();
                Http::json([
                    'ok' => true,
                    'analysis' => $app['proofs']->analysis(
                        (int) ($_GET['id'] ?? 0)
                    ),
                ]);

            default:
                throw new ValidationException('La consulta no es válida.');
        }
    }

    if ($method !== 'POST') {
        Http::json(['ok' => false, 'error' => 'Método no permitido.'], 405);
    }

    $input = Http::input();
    $action = trim((string) ($input['action'] ?? $action));
    Http::requireCsrf($input);

    switch ($action) {
        case 'setup_admin':
            $app['auth']->createInitialAdmin(
                (string) ($input['setup_token'] ?? ''),
                (string) $app['config']['setup_token'],
                (string) ($input['name'] ?? ''),
                (string) ($input['email'] ?? ''),
                (string) ($input['password'] ?? '')
            );
            Http::json(['ok' => true], 201);

        case 'login':
            Http::json([
                'ok' => true,
                'user' => $app['auth']->login(
                    (string) ($input['email'] ?? ''),
                    (string) ($input['password'] ?? '')
                ),
                'csrf_token' => $_SESSION['csrf_token'],
            ]);

        case 'logout':
            $app['auth']->logout();
            Http::json([
                'ok' => true,
                'csrf_token' => $_SESSION['csrf_token'],
            ]);

        case 'create_order':
            $order = $app['orders']->createWebOrder(
                is_array($input['customer'] ?? null) ? $input['customer'] : [],
                is_array($input['items'] ?? null) ? $input['items'] : [],
                (string) ($input['channel'] ?? 'web'),
                (string) ($input['payment_method'] ?? 'bank_transfer')
            );
            // Intentamos entregar la confirmación al instante. La tarea programada
            // conserva los reintentos y procesa cualquier correo pendiente.
            Http::json(['ok' => true, 'order' => $order], 201);

        case 'upload_proof':
            $proof = $app['proofs']->receive(
                (int) ($input['order_id'] ?? 0),
                (string) ($input['upload_token'] ?? ''),
                is_array($_FILES['proof'] ?? null) ? $_FILES['proof'] : []
            );
            Http::json(['ok' => true, 'result' => $proof], 201);

        case 'product_image_upload':
            $app['auth']->requireAdmin();
            $image = $app['product_images']->receive(
                is_array($_FILES['image'] ?? null) ? $_FILES['image'] : []
            );
            Http::json([
                'ok' => true,
                'image_path' => $image['image_path'],
            ], 201);

        case 'product_create':
            $user = $app['auth']->requireAdmin();
            $productId = $app['products']->create(
                is_array($input['product'] ?? null) ? $input['product'] : [],
                (int) $user['id']
            );
            Http::json(['ok' => true, 'product_id' => $productId], 201);

        case 'category_create':
            $app['auth']->requireAdmin();
            Http::json(['ok' => true, 'category_id' => $app['categories']->create(is_array($input['category'] ?? null) ? $input['category'] : [])], 201);

        case 'category_update':
            $app['auth']->requireAdmin();
            $app['categories']->update((int) ($input['category_id'] ?? 0), is_array($input['category'] ?? null) ? $input['category'] : []);
            Http::json(['ok' => true]);

        case 'category_delete':
            $app['auth']->requireAdmin();
            $app['categories']->delete((int) ($input['category_id'] ?? 0));
            Http::json(['ok' => true]);

        case 'product_update':
            $user = $app['auth']->requireAdmin();
            $app['products']->update(
                (int) ($input['product_id'] ?? 0),
                is_array($input['product'] ?? null) ? $input['product'] : [],
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'variant_quick_update':
            $user = $app['auth']->requireAdmin();
            $app['products']->quickUpdateVariant(
                (int) ($input['variant_id'] ?? 0),
                is_array($input['changes'] ?? null) ? $input['changes'] : [],
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'variant_barcode_assign':
            $user = $app['auth']->requireUser();
            $app['products']->assignBarcode(
                (int) ($input['variant_id'] ?? 0),
                (string) ($input['barcode'] ?? ''),
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'product_duplicate':
            $user = $app['auth']->requireAdmin();
            $newProductId = $app['products']->duplicate(
                (int) ($input['product_id'] ?? 0),
                (int) $user['id'],
                !isset($input['copy_images']) || (bool) $input['copy_images']
            );
            Http::json(['ok' => true, 'product_id' => $newProductId], 201);

        case 'pos_sale':
            $user = $app['auth']->requireUser();
            $sale = $app['orders']->createPosSale(
                is_array($input['items'] ?? null) ? $input['items'] : [],
                (string) ($input['customer_name'] ?? ''),
                (string) ($input['payment_method'] ?? ''),
                (int) $user['id']
            );
            Http::json(['ok' => true, 'order' => $sale], 201);

        case 'payment_review':
            $user = $app['auth']->requireAdmin();
            $app['orders']->reviewPayment(
                (int) ($input['order_id'] ?? 0),
                (string) ($input['decision'] ?? ''),
                (int) $user['id'],
                (string) ($input['note'] ?? '')
            );
            Http::json(['ok' => true]);

        case 'order_update_items':
            $user = $app['auth']->requireAdmin();
            $order = $app['orders']->updateWebOrderItems(
                (int) ($input['order_id'] ?? 0),
                is_array($input['items'] ?? null) ? $input['items'] : [],
                (int) $user['id']
            );
            Http::json(['ok' => true, 'order' => $order]);

        case 'order_ready':
            $user = $app['auth']->requireUser();
            $app['orders']->markReady(
                (int) ($input['order_id'] ?? 0),
                (int) $user['id'],
                'manual_cancellation',
                ($input['notify_customer'] ?? true) !== false
            );
            Http::json(['ok' => true]);

        case 'order_archive':
            $user = $app['auth']->requireAdmin();
            $app['orders']->archive(
                (int) ($input['order_id'] ?? 0),
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'order_reopen':
            $user = $app['auth']->requireAdmin();
            $app['orders']->reopen(
                (int) ($input['order_id'] ?? 0),
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'order_deliver':
            $user = $app['auth']->requireUser();
            $app['orders']->deliver(
                (int) ($input['order_id'] ?? 0),
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'order_cancel':
            $user = $app['auth']->requireAdmin();
            $app['orders']->cancel(
                (int) ($input['order_id'] ?? 0),
                (int) $user['id'],
                'manual_cancellation',
                false
            );
            Http::json(['ok' => true]);

        case 'expire_orders':
            $user = $app['auth']->requireAdmin();
            unset($user);
            Http::json([
                'ok' => true,
                'result' => $app['stock']->expireOrders(),
            ]);

        case 'backup_create':
            $user = $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'backup' => $app['backups']->create((int) $user['id']),
            ], 201);

        case 'settings_update':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'settings' => $app['settings']->update(
                    is_array($input['settings'] ?? null)
                        ? $input['settings']
                        : []
                ),
            ]);

        case 'size_guide_update':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'size_guide' => $app['settings']->updateSizeGuide(
                    is_array($input['size_guide'] ?? null)
                        ? $input['size_guide']
                        : []
                ),
            ]);

        case 'user_create':
            $app['auth']->requireAdmin();
            $userId = $app['auth']->createUser(
                (string) ($input['name'] ?? ''),
                (string) ($input['email'] ?? ''),
                (string) ($input['password'] ?? ''),
                (string) ($input['role'] ?? 'seller')
            );
            Http::json(['ok' => true, 'user_id' => $userId], 201);

        case 'user_update':
            $actor = $app['auth']->requireAdmin();
            $app['auth']->updateUser(
                (int) ($input['user_id'] ?? 0),
                (string) ($input['name'] ?? ''),
                (string) ($input['email'] ?? ''),
                (string) ($input['role'] ?? 'seller'),
                filter_var(
                    $input['active'] ?? false,
                    FILTER_VALIDATE_BOOL
                ),
                (string) ($input['password'] ?? ''),
                (int) $actor['id']
            );
            Http::json(['ok' => true]);

        default:
            throw new ValidationException('La acción no es válida.');
    }
} catch (ValidationException $exception) {
    Http::json(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (AuthorizationException $exception) {
    Http::json(['ok' => false, 'error' => $exception->getMessage()], 403);
} catch (ConflictException $exception) {
    Http::json(['ok' => false, 'error' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    error_log((string) $exception);
    $message = $app['config']['debug']
        ? $exception->getMessage()
        : 'Ocurrió un error inesperado. Volvé a intentar.';
    Http::json(['ok' => false, 'error' => $message], 500);
}
