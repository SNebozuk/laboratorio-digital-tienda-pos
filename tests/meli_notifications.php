<?php
declare(strict_types=1);
namespace LaboratorioDigital;

require dirname(__DIR__) . '/app/Http.php';
require dirname(__DIR__) . '/app/MercadoLibreService.php';
function check(bool $value, string $message): void { if (!$value) throw new \RuntimeException($message); }
function curl_init(string $url): object { return (object) ['url' => $url, 'options' => [], 'code' => 200]; }
function curl_setopt_array(object $handle, array $options): bool { $handle->options += $options; return true; }
function curl_setopt(object $handle, int $key, mixed $value): bool { $handle->options[$key] = $value; return true; }
function curl_getinfo(object $handle, int $key): int { return $handle->code; }
function curl_close(object $handle): void {}
function curl_exec(object $handle): string {
    $path = parse_url($handle->url, PHP_URL_PATH);
    if ($path === '/users/me') return '{"id":123}';
    if ($path === '/orders/456') return json_encode(['seller' => ['id' => $GLOBALS['seller']], 'order_items' => [['item' => ['id' => 'MLA123']]]]);
    if ($path === '/items/MLA123') return '{"seller_id":123,"user_product_id":"MLAU123"}';
    if ($path === '/user-products/MLAU123/stock') {
        $callback = $handle->options[CURLOPT_HEADERFUNCTION]; $callback($handle, "x-version: 7\r\n");
        return json_encode(['locations' => [['type' => 'selling_address', 'quantity' => $GLOBALS['remote']]]]);
    }
    throw new \RuntimeException('Unexpected API request: ' . $path);
}
$db = new \PDO('sqlite::memory:', null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
$db->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
$db->exec("INSERT INTO users(id,name,email,password_hash,role) VALUES(1,'Test','test@example.test','fake','admin');
    INSERT INTO products(id,name) VALUES(1,'Papel');
    INSERT INTO product_variants(id,product_id,name,sku,price_cents,stock_on_hand,stock_specified) VALUES(1,1,'Unit','TEST',100000,60,1)");
$storage = sys_get_temp_dir() . '/meli-notifications-' . bin2hex(random_bytes(6)); mkdir($storage);
$service = new MercadoLibreService($db, ['storage_path' => $storage, 'meli_client_id' => 'test', 'meli_client_secret' => 'fake-test-secret', 'meli_redirect_uri' => 'https://example.test/callback']);
(new \ReflectionMethod($service, 'saveTokens'))->invoke($service, ['access_token' => 'fake', 'user_id' => 123, 'expires_at' => time() + 3600]);
$insert = $db->prepare('INSERT INTO settings(key,value) VALUES(?,?)');
$insert->execute(['meli_listing_1', json_encode(['state' => 'published', 'product_id' => 1, 'variant_id' => 1, 'item_id' => 'MLA123'])]);
$insert->execute(['meli_stock_1', '{"item_id":"MLA123","remote_quantity":60,"pending":false}']);
$event = ['application_id' => 'test', 'user_id' => 123, 'topic' => 'orders_v2', 'resource' => '/orders/456'];
$GLOBALS['remote'] = 58; $GLOBALS['seller'] = 123;
try {
    foreach ([$event + [], array_replace($event, ['topic' => 'items', 'resource' => '/items/MLA123']),
        array_replace($event, ['topic' => 'stock-location', 'resource' => '/user-products/MLAU123/stock'])] as $notification) {
        $service->receiveNotification($notification); $service->receiveNotification($notification);
    }
    check($service->processNotifications()['processed'] === 3, 'Duplicate notifications coalesce');
    check((int) $db->query('SELECT stock_on_hand FROM product_variants WHERE id=1')->fetchColumn() === 58, 'Sale deducts exactly two units');
    check((int) $db->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn() === 1, 'Order and stock notifications do not deduct twice');
    $service->receiveNotification($event); $service->processNotifications();
    check((int) $db->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn() === 1, 'Late duplicate cannot repeat a deduction');
    foreach ([['user_id' => 999], ['application_id' => 'foreign'], ['resource' => 'https://evil.test/orders/456']] as $bad) {
        $rejected = false;
        try { $service->receiveNotification(array_replace($event, $bad)); } catch (\InvalidArgumentException) { $rejected = true; }
        check($rejected, 'Foreign or external resource is rejected');
    }
    $GLOBALS['seller'] = 999; $GLOBALS['remote'] = 50;
    $service->receiveNotification($event);
    check($service->processNotifications()['failed'] === 1, 'Order ownership is verified through the API');
    check((int) $db->query('SELECT stock_on_hand FROM product_variants WHERE id=1')->fetchColumn() === 58, 'Foreign order cannot change stock');
    $GLOBALS['seller'] = 123; $GLOBALS['remote'] = 57;
    $service->receiveNotification($event);
    $lock = fopen($storage . '/meli-oauth.lock', 'c'); flock($lock, LOCK_EX);
    check($service->processNotifications()['busy'], 'Busy integration leaves notifications queued');
    flock($lock, LOCK_UN); fclose($lock);
    check($service->processNotifications()['processed'] === 1, 'Queued notification can retry');
    $queue = new MercadoLibreNotifications($db, $storage); $queuedEvent = ['topic' => 'items', 'resource' => '/items/MLA123', 'user_id' => 123];
    $queue->enqueue($queuedEvent);
    $queue->process(static function () use ($queue, $queuedEvent): void { $queue->enqueue($queuedEvent); });
    check((int) $db->query("SELECT COUNT(*) FROM settings WHERE key GLOB 'meli_notification_*'")->fetchColumn() === 1, 'Arrival during processing survives acknowledgment');
    $GLOBALS['remote'] = 55;
    check($service->processNotifications(true)['processed'] === 1, 'Scheduled polling processes a delayed stock event');
    check((int) $db->query('SELECT stock_on_hand FROM product_variants WHERE id=1')->fetchColumn() === 55, 'Polling recovers missing notifications without extra deductions');
    echo "MeLi notification tests passed\n";
} finally { unlink($storage . '/meli-oauth.lock'); rmdir($storage); }
