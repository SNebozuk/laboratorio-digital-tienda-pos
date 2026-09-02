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
                    'featured_product_ids' => $app['settings']->featuredProductIds(),
                    'tutorials' => $app['tutorials']->publicList(),
                ]);

            case 'catalog_search':
                Http::json([
                    'ok' => true,
                    'product_ids' => $app['products']->publicCodeMatches(
                        (string) ($_GET['q'] ?? '')
                    ),
                ]);

            case 'quote_catalog':
                Http::json([
                    'ok' => true,
                    'products' => $app['products']->publicCatalog(),
                    'categories' => $app['categories']->tree(),
                    'quote_settings' => $app['settings']->quote(),
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
                    'featured_product_ids' => $app['settings']->featuredProductIds(),
                ]);

            case 'admin_categories':
                $app['auth']->requireUser();
                Http::json(['ok' => true, 'categories' => $app['categories']->tree()]);

            case 'supplier_order_draft':
                $user = $app['auth']->requireAdmin();
                Http::json([
                    'ok' => true,
                    'draft' => $app['supplier_orders']->draft((int) $user['id']),
                    'categories' => $app['categories']->tree(),
                ]);

            case 'admin_tutorials':
                $app['auth']->requireAdmin();
                Http::json(['ok' => true, 'tutorials' => $app['tutorials']->adminList()]);

            case 'orders':
                $app['auth']->requireUser();
                Http::json([
                    'ok' => true,
                    'orders' => $app['orders']->recentOrders(
                        (int) ($_GET['limit'] ?? 100),
                        (bool) ($_GET['include_archived'] ?? false)
                    ),
                    'open_count' => $app['orders']->openOrderCount(),
                ]);

            case 'order_notifications':
                $user = $app['auth']->requireUser();
                Http::json([
                    'ok' => true,
                    'open_count' => $app['orders']->openOrderCount(),
                ]);

            case 'delivery_slots':
                $app['auth']->requireUser();
                Http::json(['ok' => true, 'slots' => $app['deliveries']->slots()]);

            case 'statistics':
                $app['auth']->requireStatisticsAccess();
                Http::json([
                    'ok' => true,
                    'statistics' => $app['orders']->statistics(),
                    'deliveries' => $app['deliveries']->summary(),
                ]);

            case 'invitations':
                $app['auth']->requireAdmin();
                Http::json(['ok' => true] + $app['invitations']->all());

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

            case 'design':
                $app['auth']->requireAdmin();
                Http::json(['ok' => true, 'design' => $app['settings']->design()]);

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

    // Página pública: no requiere una sesión de administración ni expone datos.
    if ($action === 'invitation_request') {
        if (trim((string) ($input['website'] ?? '')) !== '') {
            Http::json(['ok' => true]);
        }
        $lastRequest = (int) ($_SESSION['invitation_request_at'] ?? 0);
        if ($lastRequest > 0 && (time() - $lastRequest) < 12) {
            throw new ValidationException('Esperá unos segundos antes de volver a enviarlo.');
        }
        $_SESSION['invitation_request_at'] = time();
        Http::json(['ok' => true, 'request' => $app['invitations']->request((string) ($input['email'] ?? ''))], 201);
    }
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

        case 'statistics_unlock':
            $app['auth']->unlockStatistics((string) ($input['password'] ?? ''));
            Http::json(['ok' => true]);

        case 'klaus_interaction':
            $user = $app['auth']->user();
            $visitorId = $user
                ? 'user:' . (int) $user['id']
                : (string) ($_COOKIE['laboratorio_store_visitor'] ?? '');
            if ($visitorId !== '') $app['store_visits']->recordKlausInteraction($visitorId);
            Http::json(['ok' => true]);

        case 'create_order':
            $requestKey = trim((string) ($input['request_key'] ?? ''));
            $cached = $requestKey !== '' ? ($_SESSION['web_order_requests'][$requestKey] ?? null) : null;
            $order = is_array($cached) ? $cached : $app['orders']->createWebOrder(
                is_array($input['customer'] ?? null) ? $input['customer'] : [],
                is_array($input['items'] ?? null) ? $input['items'] : [],
                (string) ($input['channel'] ?? 'web'),
                (string) ($input['payment_method'] ?? 'bank_transfer'),
                !empty($_SESSION['cart_surprise_unlocked']),
                !empty($_SESSION['cart_klaus_discount_unlocked'])
            );
            if (!is_array($cached)) unset($_SESSION['cart_surprise_unlocked'], $_SESSION['cart_surprise_checked'], $_SESSION['cart_klaus_discount_unlocked'], $_SESSION['cart_klaus_reward_checked']);
            if ($requestKey !== '') {
                $_SESSION['web_order_requests'] = array_slice((array) ($_SESSION['web_order_requests'] ?? []) + [$requestKey => $order], -10, null, true);
            }
            // Entrega inmediata: la venta ya quedó confirmada antes de este punto.
            // Si SES no responde, la cola y la tarea programada conservan el reintento
            // sin afectar la experiencia de compra ni duplicar pedidos reenviados.
            if (!is_array($cached) && !empty($app['config']['mail_enabled'])) {
                try {
                    $app['mail']->process(2, (int) $order['id']);
                } catch (Throwable $mailException) {
                    error_log('No se pudo entregar el aviso inmediato de venta: ' . $mailException->getMessage());
                }
            }
            Http::json(['ok' => true, 'order' => $order], 201);

        case 'reward_surprise':
            if (!isset($_SESSION['cart_surprise_checked'])) {
                $_SESSION['cart_surprise_checked'] = true;
                $settings = $app['settings']->values();
                $enabled = in_array((string) ($settings['reward_surprise_enabled'] ?? '0'), ['1', 'true', 'on'], true);
                $probability = max(0, min(100, (int) ($settings['reward_surprise_probability'] ?? 0)));
                $_SESSION['cart_surprise_unlocked'] = $app['settings']->claimDailySurprise($enabled, $probability);
            }
            Http::json(['ok' => true, 'unlocked' => !empty($_SESSION['cart_surprise_unlocked'])]);

        case 'reward_klaus':
            if (empty($_SESSION['cart_klaus_reward_checked'])) {
                $_SESSION['cart_klaus_reward_checked'] = true;
                $_SESSION['cart_klaus_discount_unlocked'] = random_int(1, 100) <= 10;
            }
            Http::json(['ok' => true, 'unlocked' => !empty($_SESSION['cart_klaus_discount_unlocked'])]);

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

        case 'supplier_order_search':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'search' => $app['supplier_orders']->search(
                    is_array($input['filters'] ?? null) ? $input['filters'] : []
                ),
            ]);

        case 'supplier_order_save':
            $user = $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'draft' => $app['supplier_orders']->save(
                    (int) $user['id'],
                    is_array($input['draft'] ?? null) ? $input['draft'] : []
                ),
            ]);

        case 'supplier_order_clear':
            $user = $app['auth']->requireAdmin();
            $app['supplier_orders']->clear((int) $user['id']);
            Http::json(['ok' => true]);

        case 'product_create':
            $user = $app['auth']->requireAdmin();
            $productId = $app['products']->create(
                is_array($input['product'] ?? null) ? $input['product'] : [],
                (int) $user['id']
            );
            Http::json(['ok' => true, 'product_id' => $productId], 201);

        case 'tutorial_create':
            $app['auth']->requireAdmin();
            Http::json(['ok' => true, 'tutorial_id' => $app['tutorials']->create(
                is_array($input['tutorial'] ?? null) ? $input['tutorial'] : []
            )], 201);

        case 'tutorial_update':
            $app['auth']->requireAdmin();
            $app['tutorials']->update(
                (int) ($input['tutorial_id'] ?? 0),
                is_array($input['tutorial'] ?? null) ? $input['tutorial'] : []
            );
            Http::json(['ok' => true]);

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

        case 'category_move':
            $app['auth']->requireAdmin();
            $app['categories']->move(
                (int) ($input['category_id'] ?? 0),
                (int) ($input['target_id'] ?? 0),
                ($input['position'] ?? '') === 'after' ? 'after' : 'before'
            );
            Http::json(['ok' => true]);

        case 'product_update':
            $user = $app['auth']->requireAdmin();
            $app['products']->update(
                (int) ($input['product_id'] ?? 0),
                is_array($input['product'] ?? null) ? $input['product'] : [],
                (int) $user['id']
            );
            Http::json(['ok' => true]);

        case 'product_visibility':
            $app['auth']->requireAdmin();
            // No usar un casteo directo: la cadena "false" en PHP equivale a true.
            // Así Ocultar siempre llega al servicio como falso, incluso desde
            // navegadores o formularios que serializan los booleanos como texto.
            $visibilityValue = $input['active'] ?? false;
            $isVisible = $visibilityValue === true
                || $visibilityValue === 1
                || $visibilityValue === '1'
                || strtolower((string) $visibilityValue) === 'true'
                || strtolower((string) $visibilityValue) === 'on';
            $app['products']->setVisibility(
                is_array($input['product_ids'] ?? null) ? $input['product_ids'] : [],
                $isVisible
            );
            Http::json(['ok' => true, 'active' => $isVisible]);

        case 'products_price_adjust':
            $user = $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'updated_variants' => $app['products']->adjustPrices(
                    is_array($input['product_ids'] ?? null) ? $input['product_ids'] : [],
                    (string) ($input['adjustment_type'] ?? ''),
                    (float) ($input['adjustment'] ?? 0),
                    (int) ($input['rounding_pesos'] ?? 0),
                    (int) $user['id']
                ),
            ]);

        case 'featured_products_update':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'featured_product_ids' => $app['settings']->updateFeaturedProductIds(
                    is_array($input['product_ids'] ?? null) ? $input['product_ids'] : []
                ),
            ]);

        case 'product_delete':
            $app['auth']->requireAdmin();
            $app['products']->delete(
                is_array($input['product_ids'] ?? null) ? $input['product_ids'] : []
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
                is_array($input['product'] ?? null) ? $input['product'] : []
            );
            Http::json(['ok' => true, 'product_id' => $newProductId], 201);

        case 'pos_sale':
            $user = $app['auth']->requireUser();
            $sale = $app['orders']->createPosSale(
                is_array($input['items'] ?? null) ? $input['items'] : [],
                (string) ($input['customer_name'] ?? ''),
                (string) ($input['customer_phone'] ?? ''),
                (string) ($input['payment_method'] ?? ''),
                (int) $user['id']
            );
            Http::json(['ok' => true, 'order' => $sale], 201);

        case 'contact_update':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'settings' => $app['settings']->updateContact(
                    is_array($input['contact'] ?? null) ? $input['contact'] : []
                ),
            ]);

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

        case 'order_notifications_seen':
            $user = $app['auth']->requireUser();
            $app['auth']->markOrdersSeen((int) $user['id']);
            Http::json(['ok' => true]);

        case 'order_reopen':
            $user = $app['auth']->requireAdmin();
            $app['orders']->reopen(
                (int) ($input['order_id'] ?? 0),
                (int) $user['id'],
                is_array($input['items'] ?? null) ? $input['items'] : null
            );
            // Si estaba en Entregas, reabrir también la devuelve a LDV para
            // que nunca quede duplicada en ambas mesas de trabajo.
            $app['deliveries']->returnOrderToSales((int) ($input['order_id'] ?? 0));
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
                false,
                ($input['restore_stock'] ?? true) !== false
            );
            Http::json(['ok' => true]);

        case 'delivery_slot_update':
            $user = $app['auth']->requireUser();
            Http::json(['ok' => true, 'slot' => $app['deliveries']->saveSlot((int) ($input['slot_number'] ?? 0), $input, (int) $user['id'])]);

        case 'delivery_copy_order':
            $user = $app['auth']->requireUser();
            Http::json(['ok' => true, 'result' => $app['deliveries']->copyOrder((int) ($input['order_id'] ?? 0), (int) ($input['slot_number'] ?? 0), (int) $user['id'])]);

        case 'delivery_copy_orders':
            $user = $app['auth']->requireUser();
            $orderIds = is_array($input['order_ids'] ?? null) ? $input['order_ids'] : [];
            Http::json(['ok' => true, 'result' => $app['deliveries']->copyOrders($orderIds, (int) ($input['slot_number'] ?? 0), (int) $user['id'])]);

        case 'delivery_slot_delete':
            $app['auth']->requireUser();
            $app['deliveries']->deleteSlot((int) ($input['slot_number'] ?? 0));
            Http::json(['ok' => true]);

        case 'delivery_return_order':
            $app['auth']->requireUser();
            $app['deliveries']->returnOrderToSales((int) ($input['order_id'] ?? 0));
            Http::json(['ok' => true]);

        case 'invitation_mark_sent':
            $app['auth']->requireAdmin();
            $app['invitations']->markSent(
                (int) ($input['invitation_id'] ?? 0),
                ($input['sent'] ?? true) !== false
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

        case 'mail_test':
            $app['auth']->requireAdmin();
            $recipient = trim((string) ($input['recipient'] ?? ''));
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Indicá un destinatario de prueba válido.');
            }
            $app['mail']->sendTest($recipient);
            Http::json(['ok' => true]);

        case 'reset_demo_data':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'result' => $app['products']->resetDemoData(),
            ]);

        case 'prepare_catalog_import':
            $app['auth']->requireAdmin();
            Http::json(['ok' => true, 'result' => $app['products']->prepareCatalogImport()]);

        case 'catalog_import':
            $user = $app['auth']->requireAdmin();
            $records = is_array($input['products'] ?? null) ? $input['products'] : [];
            foreach ($records as &$record) {
                if (!is_array($record)) continue;
                $sourceImage = trim((string) ($record['tiendanube_image'] ?? ''));
                if ($sourceImage !== '') $record['image_path'] = $app['product_images']->receiveTiendaNubeImage($sourceImage)['image_path'];
                unset($record['tiendanube_image']);
            }
            unset($record);
            Http::json(['ok' => true, 'created' => $app['products']->importCatalog($records, (int) $user['id'])], 201);

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

        case 'quote_settings_update':
            $app['auth']->requireAdmin();
            Http::json([
                'ok' => true,
                'quote_settings' => $app['settings']->updateQuote(
                    is_array($input['quote_settings'] ?? null) ? $input['quote_settings'] : []
                ),
            ]);

        case 'design_update':
            $app['auth']->requireAdmin();
            Http::json(['ok' => true, 'design' => $app['settings']->updateDesign(is_array($input['design'] ?? null) ? $input['design'] : [])]);

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
