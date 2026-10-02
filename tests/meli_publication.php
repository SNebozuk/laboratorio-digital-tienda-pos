<?php
declare(strict_types=1);

// In-memory transport: these tests never contact or publish to Mercado Libre.
namespace LaboratorioDigital;

require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/Http.php';
require dirname(__DIR__) . '/app/MercadoLibreService.php';

function check(bool $condition, string $message): void { if (!$condition) throw new \RuntimeException($message); }
function rejects(callable $fn, string $message): void {
    try { $fn(); } catch (\RuntimeException $error) { return; }
    throw new \RuntimeException($message);
}
function curl_init(string $url): object { return (object) ['url' => $url, 'options' => [], 'code' => 200]; }
function curl_setopt_array(object $handle, array $options): bool { $handle->options += $options; return true; }
function curl_setopt(object $handle, int $option, mixed $value): bool { $handle->options[$option] = $value; return true; }
function curl_getinfo(object $handle, int $option): int { return $handle->code; }
function curl_close(object $handle): void {}
function curl_exec(object $handle): string|false {
    $path = parse_url($handle->url, PHP_URL_PATH);
    $body = json_decode($handle->options[CURLOPT_POSTFIELDS] ?? 'null', true);
    $method = $handle->options[CURLOPT_CUSTOMREQUEST] ?? 'GET';
    $GLOBALS['calls'][] = [$path, $method, $body];
    if ($path === '/users/me' || $path === '/users/123') return json_encode(['id' => 123, 'tags' => ['user_product_seller']]);
    if (str_ends_with($path, '/shipping_preferences')) return json_encode(['modes' => ['me2'], 'logistics' => [['mode' => 'me2', 'types' => [['type' => 'drop_off', 'status' => 'active']]]]]);
    if (str_ends_with($path, '/available_listing_types')) return json_encode([['id' => 'gold_special']]);
    if ($path === '/special_installments/campaigns') return json_encode([['channel' => 'marketplace', 'available_campaigns' => [['listing_type_id' => 'gold_special', 'available_campaigns' => [['campaign_id' => 'pcj-co-funded']]]]]]);
    if ($path === '/sites/MLA/listing_prices') {
        parse_str(parse_url($handle->url, PHP_URL_QUERY), $params);
        $rate = isset($params['tags']) ? ($GLOBALS['financing_rate'] ?? 5) : 0;
        return json_encode(['currency_id' => 'ARS', 'listing_type_id' => 'gold_special',
            'sale_fee_amount' => (float) $params['price'] * $rate / 100, 'listing_fee_amount' => 0,
            'sale_fee_details' => ['financing_add_on_fee' => $rate]]);
    }
    if (preg_match('~^/categories/(MLA109042|MLA109085|MLA416632|MLA454114|MLA393902)/attributes$~', $path)) return json_encode(array_map(static fn ($id) => ['id' => $id], ['BRAND', 'SIZE', 'COLOR', 'GENDER', 'SELLER_SKU', 'GTIN', 'EMPTY_GTIN_REASON', 'SIZE_GRID_ID', 'SIZE_GRID_ROW_ID', 'SELLER_PACKAGE_LENGTH', 'SELLER_PACKAGE_WIDTH', 'SELLER_PACKAGE_HEIGHT', 'SELLER_PACKAGE_WEIGHT']));
    if (str_ends_with($path, '/sale_terms')) return '[]';
    if ($path === '/items/validate' || str_ends_with($path, '/attributes/conditional')) return '{}';
    if ($path === '/items') {
        if (!empty($GLOBALS['timeout'])) return false;
        $handle->code = 201;
        $id = 'MLA' . (100 + count($GLOBALS['items']));
        $GLOBALS['items'][$id] = $body + ['id' => $id, 'seller_id' => 123, 'status' => 'active', 'sub_status' => []];
        return json_encode($GLOBALS['items'][$id]);
    }
    if (preg_match('~^/items/(MLA\d+)$~', $path, $matches)) {
        $id = $matches[1];
        if ($method === 'PUT') {
            if (($body['status'] ?? '') === 'paused' && ($GLOBALS['pause_failure'] ?? '') === $id) return json_encode($GLOBALS['items'][$id]);
            if (!empty($body['deleted'])) $GLOBALS['items'][$id]['sub_status'] = ['deleted'];
            if (isset($body['status'])) $GLOBALS['items'][$id]['status'] = $body['status'];
        }
        return json_encode($GLOBALS['items'][$id]);
    }
    if (str_ends_with($path, '/description')) { $handle->code = 201; return '{}'; }
    return '{}';
}

$db = new \PDO('sqlite::memory:', null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
$db->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
$db->exec("INSERT INTO products(id,name) VALUES(1,'Remera negra algodón unisex')");
$db->exec("INSERT INTO product_variants(id,product_id,name,sku,price_cents,stock_on_hand,active) VALUES
    (1,1,'Talle 1','__AUTO__1',750000,13,1), (2,1,'Talle 2','__AUTO__2',750000,0,1),
    (3,1,'Talle 3','SKU3',950000,16,1), (4,1,'Talle 4','SKU4',750000,10,0)");
$draft = ['category_id' => 'MLA109042', 'family_name' => 'Remera negra algodón unisex', 'variant_id' => 1,
    'publish_all_variants' => true, 'condition' => 'new', 'listing_type_id' => 'gold_special', 'shipping_mode' => 'me2', 'logistic_type' => 'drop_off',
    'pictures' => ['https://example.com/remera.jpg'], 'package_confirmed' => true,
    'attributes' => ['BRAND' => 'Inventada', 'COLOR' => 'Negro', 'SIZE_GRID_ID' => '123', 'SELLER_PACKAGE_LENGTH' => '30 cm',
        'SELLER_PACKAGE_WIDTH' => '25 cm', 'SELLER_PACKAGE_HEIGHT' => '3 cm', 'SELLER_PACKAGE_WEIGHT' => '250 g'],
    'size_grid_rows' => [1 => '123:1', 3 => '123:3'],
    'pricing' => ['base_price_cents' => 750000, 'billable_weight' => 250]];
MercadoLibreProductDraft::save($db, 1, MercadoLibreProductDraft::normalize($draft));
$service = new MercadoLibreService($db, ['meli_client_id' => 'test', 'meli_client_secret' => 'fake-test-secret', 'meli_redirect_uri' => 'https://example.com/meli.php']);
(new \ReflectionMethod($service, 'saveTokens'))->invoke($service, ['access_token' => 'fake-test-token', 'refresh_token' => 'fake', 'expires_at' => time() + 3600]);
$GLOBALS['calls'] = [];
$GLOBALS['items'] = [];
check(array_column($service->publicationVariants(1)['variants'], 'id') === [1, 3], 'Only active sizes with stock');
$prepared = $service->preparePublication(1, 3);
$attrs = array_column($prepared['payload']['attributes'], 'value_name', 'id');
check($prepared['payload']['price'] === 9500 && $prepared['payload']['available_quantity'] === 16, 'Each size keeps its own price and stock');
check($attrs['SIZE'] === '3' && $attrs['SIZE_GRID_ROW_ID'] === '123:3', 'Correct size and chart row');
check($attrs['BRAND'] === 'Generic' && $attrs['SELLER_SKU'] === 'SKU3', 'No invented shirt brand; real variant SKU');
rejects(fn () => $service->preparePublication(1, 2), 'Cannot publish zero stock');
rejects(fn () => $service->preparePublication(1, 4), 'Cannot publish inactive sizes');
$first = $service->publishProduct(1, 1);
$baseline = json_decode((string) $db->query("SELECT value FROM settings WHERE key='meli_stock_1'")->fetchColumn(), true);
check($baseline['remote_quantity'] === 13 && $baseline['item_id'] === $first['item_id'], 'Publication establishes stock baseline before its first sale');
check(array_column($service->publicationVariants(1)['variants'], 'id') === [3], 'Retries skip already published sizes');
rejects(fn () => $service->publishProduct(1, 1), 'Cannot create duplicate listing');
$service->publishProduct(1, 3);
$service->finishPublication(1);
check(MercadoLibreProductDraft::publicationStates($db)[1]['state'] === 'published', 'Successful product is yellow');
$service->publicationError(1, 'Example failure');
check(MercadoLibreProductDraft::publicationStates($db)[1]['state'] === 'failed', 'Errors survive reload and turn red');
$service->finishPublication(1);
$GLOBALS['pause_failure'] = 'MLA101';
rejects(fn () => $service->setProductsVisibility([1], false), 'A failed pause must prevent hiding');
check((int) $db->query('SELECT active FROM products WHERE id=1')->fetchColumn() === 1, 'Product stays visible until every listing is paused');
$GLOBALS['pause_failure'] = '';
$hidden = $service->setProductsVisibility([1], false);
check($hidden['meli_paused'] === 1 && count(array_filter($GLOBALS['items'], static fn ($item) => $item['status'] === 'active')) === 0, 'Hide pauses remaining active sizes; retry skips already paused sizes');
rejects(fn () => $service->publicationVariants(1), 'Hidden products cannot start publication');
rejects(fn () => $service->preparePublication(1, 1), 'Hidden products cannot prepare publication');
rejects(fn () => $service->changeListingStatus($first['item_id'], 'active'), 'Hidden listings cannot be reactivated');
$service->setProductsVisibility([1], true);
check(count(array_filter($GLOBALS['items'], static fn ($item) => $item['status'] === 'active')) === 0, 'Showing product does not automatically reactivate listings');
$service->deleteListing($first['item_id']);
check(!isset(MercadoLibreProductDraft::publicationStates($db)[1]), 'Delete all linked sizes and reset gray');
check(count(array_filter($GLOBALS['items'], static fn ($item) => in_array('deleted', $item['sub_status'], true))) === 2, 'MeLi deletion confirmed for every size');
$defaults = MercadoLibreDefaults::seed();
$defaults['logistic_type'] = 'drop_off';
$defaults['packaging_cents'] = 20000;
$service->saveDefaults($defaults);
rejects(fn () => $service->saveDefaults(array_replace($defaults, ['financing_max_percent' => 6])), 'Cannot absorb more than authorized');
rejects(fn () => $service->saveDefaults(array_replace($defaults, ['listing_type_id' => 'gold_pro'])), 'Campaign requires correct listing type');
$prepared = $service->preparePublication(1, 3);
check($prepared['payload']['price'] === 9700, 'Global costs apply to each size without adding installment cost to price');
check($prepared['pricing']['financing_fee_cents'] === 48500 && $prepared['pricing']['net_cents'] === 901500, 'Financing is absorbed in net');
check($prepared['payload']['tags'] === ['pcj-co-funded'], 'New listings include installment campaign');
check($prepared['draft']['pricing']['billable_weight'] === 250.0 && $prepared['draft']['size_grid_rows'][3] === '123:3', 'Global defaults preserve product weight and sizes');
$GLOBALS['financing_rate'] = 6;
rejects(fn () => $service->preparePublication(1, 1), 'Stop before publishing when campaign cost exceeds cap');
check(count($GLOBALS['items']) === 2, 'Settings and price validation create no real or mock listings');
$GLOBALS['financing_rate'] = 5;
$GLOBALS['timeout'] = true;
rejects(fn () => $service->publishProduct(1, 1), 'Uncertain creation must fail');
rejects(fn () => $service->publicationVariants(1), 'Uncertain creation prevents retry duplicates');
rejects(fn () => MercadoLibreProductDraft::normalize(array_replace($draft, ['size_grid_rows' => 'invalid'])), 'Invalid rows');
$db->exec("INSERT INTO products(id,name) VALUES(2,'Buzo cuello redondo negro'); INSERT INTO product_variants(id,product_id,name,sku,price_cents,stock_on_hand,active) VALUES(5,2,'Talle 3','__AUTO__5',1490000,2,1)");
$buzo = array_replace($draft, ['category_id' => 'MLA109085', 'family_name' => 'Buzo cuello redondo negro', 'variant_id' => 5,
    'package_confirmed' => false, 'package_estimated' => true, 'size_grid_rows' => [5 => '456:3']]);
$buzo['attributes']['BRAND'] = 'Generic';
$buzo['attributes']['SIZE_GRID_ID'] = '456';
MercadoLibreProductDraft::save($db, 2, MercadoLibreProductDraft::normalize($buzo));
$preparedBuzo = $service->preparePublication(2, 5);
$attrsBuzo = array_column($preparedBuzo['payload']['attributes'], 'value_name', 'id');
check($attrsBuzo['SIZE'] === '3' && $attrsBuzo['SIZE_GRID_ROW_ID'] === '456:3', 'Buzo uses its own numeric size and chart row');
check(!$preparedBuzo['draft']['package_confirmed'] && $preparedBuzo['draft']['package_estimated'], 'Buzo keeps package estimates explicit');
$buzo['size_grid_rows'] = [];
MercadoLibreProductDraft::save($db, 2, MercadoLibreProductDraft::normalize($buzo));
rejects(fn () => $service->preparePublication(2, 5), 'Buzo cannot publish without its own chart row');
$db->exec("INSERT INTO products(id,name) VALUES(3,'Papel tricapa A4'); INSERT INTO product_variants(id,product_id,name,sku,barcode,price_cents,stock_on_hand,active) VALUES(6,3,'Única','S101','sublisti721450716029',950000,4,1)");
$paper = array_replace($draft, ['family_name' => 'Papel tricapa A4', 'variant_id' => 6, 'package_confirmed' => false, 'package_estimated' => true]);
$paper['attributes']['EMPTY_GTIN_REASON'] = 'Otra razón';
foreach (['MLA416632', 'MLA454114', 'MLA393902'] as $category) {
    $paper['category_id'] = $category;
    MercadoLibreProductDraft::save($db, 3, MercadoLibreProductDraft::normalize($paper));
    $preparedPaper = $service->preparePublication(3, 6);
    $attrsPaper = array_column($preparedPaper['payload']['attributes'], 'value_name', 'id');
    check(!isset($attrsPaper['GTIN']) && $attrsPaper['EMPTY_GTIN_REASON'] === 'Otra razón', 'Malformed barcode is not sent as GTIN; explicit reason preserved');
    check(!$preparedPaper['draft']['package_confirmed'] && $preparedPaper['draft']['package_estimated'], 'Paper package estimate is not marked measured');
}
unset($paper['attributes']['EMPTY_GTIN_REASON']);
MercadoLibreProductDraft::save($db, 3, MercadoLibreProductDraft::normalize($paper));
rejects(fn () => $service->preparePublication(3, 6), 'Invalid barcode needs an explicit reason before publication');
check($db->query('SELECT barcode FROM product_variants WHERE id=6')->fetchColumn() === 'sublisti721450716029', 'Local barcode is preserved');
$paper['attributes']['GTIN'] = '721450696000';
MercadoLibreProductDraft::save($db, 3, MercadoLibreProductDraft::normalize($paper));
$attrsPaper = array_column($service->preparePublication(3, 6)['payload']['attributes'], 'value_name', 'id');
check($attrsPaper['GTIN'] === '721450696000', 'Verified draft GTIN is used for a single variant with malformed local barcode');
$db->exec("UPDATE product_variants SET barcode=NULL WHERE id=6");
$attrsPaper = array_column($service->preparePublication(3, 6)['payload']['attributes'], 'value_name', 'id');
check($attrsPaper['GTIN'] === '721450696000', 'Verified draft GTIN is retained when local barcode is empty');
$db->exec("UPDATE product_variants SET barcode='721450716388' WHERE id=6");
$attrsPaper = array_column($service->preparePublication(3, 6)['payload']['attributes'], 'value_name', 'id');
check($attrsPaper['GTIN'] === '721450696000', 'Explicit paper draft GTIN can correct the publication without changing local barcode');
$service->publicationError(3, 'Previous validation failed');
check($service->validatePublication(3, 6)['valid'], 'Corrected paper draft validates without publication');
check($db->query("SELECT COUNT(*) FROM settings WHERE key='meli_publication_error_3'")->fetchColumn() == 0, 'Successful validation clears stale product error');
check(count($GLOBALS['items']) === 2, 'Paper draft validation creates no listings');
$db->exec("UPDATE product_variants SET barcode=NULL WHERE id=6; INSERT INTO product_variants(id,product_id,name,sku,price_cents,stock_on_hand,active) VALUES(7,3,'Otro paquete','S102',950000,4,1)");
$attrsPaper = array_column($service->preparePublication(3, 7)['payload']['attributes'], 'value_name', 'id');
check(!isset($attrsPaper['GTIN']), 'Product draft GTIN cannot leak into a different variant');
$paper['attributes']['SHEETS_NUMBER'] = '1';
MercadoLibreProductDraft::save($db, 3, MercadoLibreProductDraft::normalize($paper));
$db->exec("UPDATE product_variants SET barcode='721450696000' WHERE id=6");
$callCount = count($GLOBALS['calls']);
rejects(fn () => $service->publishProduct(3, 6), 'A single sheet cannot be published even when its barcode is present');
check(count($GLOBALS['calls']) === $callCount, 'Single sheet is blocked before any API call');
$paper['attributes']['SHEETS_NUMBER'] = '2';
MercadoLibreProductDraft::save($db, 3, MercadoLibreProductDraft::normalize($paper));
$db->exec("UPDATE products SET name='Tatufan Art-Jet - 1 Hoja' WHERE id=3");
rejects(fn () => $service->preparePublication(3, 6), 'Tatufan one-kit presentation stays excluded even though it contains two sheets');
$db->exec("UPDATE products SET name='Filmilo paquete A4' WHERE id=3;UPDATE product_variants SET name='1 Hoja' WHERE id=6");
rejects(fn () => $service->preparePublication(3, 6), 'Single-sheet variant name also prevents publication');
echo "MeLi publication tests passed\n";
