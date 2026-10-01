<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

require_once __DIR__ . '/MercadoLibrePriceCalculator.php';
require_once __DIR__ . '/MercadoLibreProductDraft.php';
require_once __DIR__ . '/ProductService.php';
require_once __DIR__ . '/MercadoLibreStockSync.php';

final class MercadoLibreService
{
    public function __construct(private readonly PDO $pdo, private readonly array $config)
    {
    }

    public function configured(): bool
    {
        return $this->config['meli_client_id'] !== '' && $this->config['meli_client_secret'] !== ''
            && str_starts_with($this->config['meli_redirect_uri'], 'https://');
    }

    public function authorizationUrl(): string
    {
        $this->requireConfiguration();
        $state = bin2hex(random_bytes(32));
        $_SESSION['meli_oauth'] = ['state' => $state, 'created' => time()];
        $params = ['response_type' => 'code', 'client_id' => $this->config['meli_client_id'],
            'redirect_uri' => $this->config['meli_redirect_uri'], 'state' => $state];
        if ($this->config['meli_pkce'] === '1') {
            $verifier = bin2hex(random_bytes(32));
            $_SESSION['meli_oauth']['verifier'] = $verifier;
            $params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $params['code_challenge_method'] = 'S256';
        }
        return 'https://auth.mercadolibre.com.ar/authorization?' . http_build_query($params);
    }

    public function callback(array $input): void
    {
        $this->requireConfiguration();
        $attempt = $_SESSION['meli_oauth'] ?? [];
        unset($_SESSION['meli_oauth']);
        if (empty($attempt['state']) || !hash_equals($attempt['state'], (string) ($input['state'] ?? ''))
            || time() - (int) ($attempt['created'] ?? 0) > 600) {
            throw new \RuntimeException('La autorización venció o no corresponde a esta sesión. Volvé a conectar.');
        }
        if (!empty($input['error']) || empty($input['code'])) {
            throw new \RuntimeException('Mercado Libre no autorizó la conexión. Volvé a intentarlo.');
        }
        $params = ['grant_type' => 'authorization_code', 'code' => (string) $input['code'],
            'redirect_uri' => $this->config['meli_redirect_uri']];
        if (isset($attempt['verifier'])) $params['code_verifier'] = $attempt['verifier'];
        $this->saveTokens($this->tokenRequest($params));
    }

    public function status(): array
    {
        if (!$this->configured()) {
            return ['connected' => false, 'configured' => false,
                'message' => 'Falta configurar App ID, Client Secret y URL de retorno en el servidor.'];
        }
        $tokens = $this->loadTokens();
        if (!$tokens) return ['connected' => false, 'configured' => true, 'message' => 'Cuenta sin conectar.'];
        if ((int) ($tokens['expires_at'] ?? 0) <= time() + 60) $tokens = $this->refresh($tokens);
        [$code, $account] = $this->request('/users/me', (string) $tokens['access_token']);
        if ($code === 401) {
            $tokens = $this->refresh($tokens);
            [$code, $account] = $this->request('/users/me', (string) $tokens['access_token']);
        }
        if ($code !== 200 || empty($account['id'])) {
            throw new \RuntimeException('Mercado Libre no confirmó el acceso a la cuenta. Verificá o volvé a conectar.');
        }
        return ['connected' => true, 'configured' => true, 'message' => 'Conexión verificada con Mercado Libre.',
            'nickname' => (string) ($account['nickname'] ?? ''), 'user_id' => (int) $account['id']];
    }

    public function productRequirements(string $categoryId): array
    {
        if (!preg_match('/^MLA\d+$/D', $categoryId)) throw new \RuntimeException('Ingresá una categoría MLA válida.');
        $status = $this->status();
        if (!$status['connected']) throw new \RuntimeException('Conectá la cuenta antes de consultar la ficha técnica.');
        $tokens = $this->loadTokens();
        $get = function (string $path) use ($tokens): array {
            [$code, $data] = $this->request($path, $tokens['access_token']);
            if ($code !== 200) throw new \RuntimeException('No se pudo consultar la ficha de Mercado Libre. Revisá la categoría y reintentá.');
            return $data;
        };
        $category = $get('/categories/' . $categoryId);
        $attributes = $get('/categories/' . $categoryId . '/attributes');
        $saleTerms = $get('/categories/' . $categoryId . '/sale_terms');
        $account = $get('/users/' . $status['user_id']);
        $shipping = $get('/users/' . $status['user_id'] . '/shipping_preferences');
        $listingTypes = $get('/users/' . $status['user_id'] . '/available_listing_types?category_id=' . $categoryId);
        return ['ok' => true, 'category' => $category,
            'attributes' => array_values(array_filter($attributes, static fn ($a) => empty($a['tags']['read_only']))),
            'sale_terms' => array_values(array_filter($saleTerms, static fn ($a) => empty($a['tags']['read_only']))),
            'listing_types' => $listingTypes['available'] ?? $listingTypes, 'shipping_modes' => $shipping['modes'] ?? [],
            'shipping_logistics' => $shipping['logistics'] ?? [],
            'user_product_seller' => in_array('user_product_seller', $account['tags'] ?? [], true)];
    }

    public function calculateProductPrice(array $input): array
    {
        $pricing = MercadoLibrePriceCalculator::inputs($input['pricing'] ?? null);
        $category = (string) ($input['category_id'] ?? '');
        $catalog = (string) ($input['catalog_product_id'] ?? '');
        $type = (string) ($input['listing_type_id'] ?? '');
        $mode = (string) ($input['shipping_mode'] ?? '');
        $logistic = (string) ($input['logistic_type'] ?? '');
        if (!preg_match('/^MLA\d+$/D', $category) || ($catalog !== '' && !preg_match('/^MLA\d+$/D', $catalog))
            || !preg_match('/^[a-z_]{1,40}$/D', $type) || !preg_match('/^[a-z_]{1,40}$/D', $logistic)) {
            throw new \RuntimeException('Completá categoría, tipo de publicación y logística antes de calcular.');
        }
        $status = $this->status();
        if (!$status['connected']) throw new \RuntimeException('Conectá la cuenta para consultar las comisiones.');
        $tokens = $this->loadTokens();
        [$shippingCode, $shipping] = $this->request('/users/' . $status['user_id'] . '/shipping_preferences', $tokens['access_token']);
        $allowed = false;
        foreach ($shipping['logistics'] ?? [] as $option) {
            if ($option['mode'] === $mode) foreach ($option['types'] ?? [] as $logistics) {
                if (($logistics['type'] ?? '') === $logistic && ($logistics['status'] ?? '') === 'active') $allowed = true;
            }
        }
        if ($shippingCode !== 200 || !$allowed) throw new \RuntimeException('La modalidad logística no está habilitada para la cuenta.');
        $result = MercadoLibrePriceCalculator::calculate($pricing, function (int $price) use ($category, $catalog, $type, $mode, $logistic, $pricing, $tokens): array {
            $params = ['price' => number_format($price / 100, 2, '.', ''), 'currency_id' => 'ARS',
                'listing_type_id' => $type, 'shipping_mode' => $mode, 'logistic_type' => $logistic,
                'billable_weight' => $pricing['billable_weight']];
            $params[$catalog !== '' ? 'catalog_product_id' : 'category_id'] = $catalog !== '' ? $catalog : $category;
            [$code, $fees] = $this->request('/sites/MLA/listing_prices?' . http_build_query($params), $tokens['access_token']);
            if ($code !== 200) throw new \RuntimeException('No se pudieron consultar las comisiones. Reintentá el cálculo.');
            if (array_is_list($fees)) {
                $fees = array_values(array_filter($fees, static fn ($fee) => ($fee['listing_type_id'] ?? '') === $type))[0] ?? [];
            }
            if (($fees['currency_id'] ?? '') !== 'ARS' || ($fees['listing_type_id'] ?? '') !== $type) {
                throw new \RuntimeException('Mercado Libre no confirmó los cargos de esta publicación.');
            }
            return $fees;
        });
        return ['ok' => true, 'pricing' => $result];
    }

    public function publishedProducts(int $offset = 0): array
    {
        $status = $this->status();
        if (!$status['connected']) throw new \RuntimeException('Conectá la cuenta para consultar las publicaciones.');
        $tokens = $this->loadTokens();
        $offset = max(0, min(980, $offset));
        [$code, $search] = $this->request('/users/' . $status['user_id'] . '/items/search?limit=20&offset=' . $offset, $tokens['access_token']);
        if ($code !== 200) throw new \RuntimeException('No se pudieron consultar los productos publicados.');
        $items = [];
        if (!empty($search['results'])) {
            $ids = array_filter($search['results'], static fn ($id) => is_string($id) && preg_match('/^MLA\d+$/D', $id));
            [$code, $details] = $this->request('/items?ids=' . implode(',', $ids)
                . '&attributes=id,title,family_name,price,currency_id,available_quantity,sold_quantity,status,permalink,listing_type_id,last_updated', $tokens['access_token']);
            if ($code !== 200) throw new \RuntimeException('No se pudo actualizar la información de las publicaciones.');
            foreach ($details as $detail) {
                if (($detail['code'] ?? 0) !== 200) throw new \RuntimeException('Una publicación no pudo consultarse. Volvé a actualizar.');
                $items[] = $detail['body'];
            }
        }
        $links = $this->listingLinks();
        foreach ($items as &$item) $item['linked'] = isset($links[$item['id']]);
        unset($item);
        return ['ok' => true, 'products' => $items, 'offset' => $offset, 'total' => (int) ($search['paging']['total'] ?? 0)];
    }

    private function listingLinks(): array
    {
        $links = [];
        foreach ($this->pdo->query("SELECT value FROM settings WHERE key LIKE 'meli_listing_%'") as $row) {
            $record = json_decode($row['value'], true);
            if (($record['state'] ?? '') === 'published' && !empty($record['item_id'])) $links[$record['item_id']] = $record;
        }
        return $links;
    }

    private function ownedItem(string $itemId): array
    {
        if (!preg_match('/^MLA\d+$/D', $itemId)) throw new \RuntimeException('Publicación inválida.');
        $status = $this->status();
        if (!$status['connected']) throw new \RuntimeException('Conectá la cuenta para operar.');
        $tokens = $this->loadTokens();
        [$code, $item] = $this->request('/items/' . $itemId, $tokens['access_token']);
        if ($code !== 200 || (int) ($item['seller_id'] ?? 0) !== $status['user_id']) {
            throw new \RuntimeException('La publicación no pertenece a la cuenta conectada o no está disponible.');
        }
        return [$item, $tokens['access_token']];
    }

    public function changeListingStatus(string $itemId, string $target): array
    {
        if (!in_array($target, ['active', 'paused'], true)) throw new \RuntimeException('Estado inválido.');
        [$item, $token] = $this->ownedItem($itemId);
        if (!in_array($item['status'], ['active', 'paused'], true)) throw new \RuntimeException('El estado actual no permite pausar o reactivar.');
        if ($target === 'active' && (int) $item['available_quantity'] < 1) throw new \RuntimeException('La publicación necesita stock para reactivarse.');
        if ($item['status'] !== $target) {
            [$code, $updated] = $this->request('/items/' . $itemId, $token, ['status' => $target], true, 'PUT');
            if ($code !== 200 || ($updated['status'] ?? '') !== $target) throw new \RuntimeException('Meli no confirmó el cambio de estado. Actualizá el panel antes de reintentar.');
        }
        return ['ok' => true, 'message' => $target === 'active' ? 'Publicación reactivada.' : 'Publicación pausada.'];
    }

    private function linkedPrice(string $itemId, array $item, string $token): array
    {
        $link = $this->listingLinks()[$itemId] ?? null;
        if (!$link) throw new \RuntimeException('Esta publicación no está vinculada con un producto de la tienda.');
        [$code, $automation] = $this->request('/pricing-automation/items/' . $itemId . '/automation', $token);
        if ($code !== 404 && ($code !== 200 || strtoupper((string) ($automation['status'] ?? '')) === 'ACTIVE')) {
            throw new \RuntimeException('No se puede editar el precio mientras Meli tenga una automatización activa o no confirme su estado.');
        }
        $q = $this->pdo->prepare('SELECT value FROM settings WHERE key=?');
        $q->execute(['meli_product_' . $link['product_id']]);
        $draft = MercadoLibreProductDraft::normalize(json_decode((string) $q->fetchColumn(), true));
        $q = $this->pdo->prepare('SELECT v.price_cents FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? AND v.product_id=? AND v.active=1 AND p.active=1 AND p.deleted_at IS NULL');
        $q->execute([$link['variant_id'], $link['product_id']]);
        $base = $q->fetchColumn();
        if ($base === false || !$draft || !$draft['pricing']) throw new \RuntimeException('Completá los gastos y el precio del producto vinculado.');
        if (!empty($item['shipping']['free_shipping']) && $draft['pricing']['shipping_cents'] <= 0) {
            throw new \RuntimeException('Completá el envío a tu cargo en la ficha antes de recalcular este precio.');
        }
        $fingerprint = hash('sha256', json_encode([$base, $draft, $item['listing_type_id'], $item['shipping'], $item['catalog_product_id'] ?? '']));
        $draft['pricing']['base_price_cents'] = (int) $base;
        $draft['category_id'] = $item['category_id'];
        $draft['catalog_product_id'] = $item['catalog_product_id'] ?? '';
        $draft['listing_type_id'] = $item['listing_type_id'];
        $draft['shipping_mode'] = $item['shipping']['mode'];
        $draft['logistic_type'] = $item['shipping']['logistic_type'];
        return ['link' => $link, 'draft' => $draft, 'fingerprint' => $fingerprint,
            'pricing' => $this->calculateProductPrice($draft)['pricing']];
    }

    public function previewListingPrice(string $itemId): array
    {
        [$item, $token] = $this->ownedItem($itemId);
        $quote = $this->linkedPrice($itemId, $item, $token);
        $quoteToken = bin2hex(random_bytes(16));
        $q = $this->pdo->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=CURRENT_TIMESTAMP');
        $q->execute(['meli_price_quote_' . $itemId, json_encode(['token' => $quoteToken, 'expires' => time() + 300,
            'fingerprint' => $quote['fingerprint'], 'price_cents' => $quote['pricing']['price_cents']], JSON_THROW_ON_ERROR)]);
        return ['ok' => true, 'quote_token' => $quoteToken, 'pricing' => $quote['pricing'], 'current_price' => $item['price']];
    }

    public function applyListingPrice(string $itemId, string $quoteToken): array
    {
        [$item, $token] = $this->ownedItem($itemId);
        $q = $this->pdo->prepare('SELECT value FROM settings WHERE key=?');
        $q->execute(['meli_price_quote_' . $itemId]);
        $saved = json_decode((string) $q->fetchColumn(), true);
        if (!$saved || $saved['expires'] < time() || !hash_equals($saved['token'], $quoteToken)) throw new \RuntimeException('El cálculo venció. Volvé a recalcular.');
        $quote = $this->linkedPrice($itemId, $item, $token);
        if ($quote['fingerprint'] !== $saved['fingerprint'] || $quote['pricing']['price_cents'] !== $saved['price_cents']) {
            throw new \RuntimeException('Cambió el precio de la tienda, los gastos o las comisiones. Volvé a recalcular y revisar.');
        }
        [$code, $updated] = $this->request('/items/' . $itemId, $token, ['price' => $saved['price_cents'] / 100], true, 'PUT');
        if ($code !== 200 || (int) round(($updated['price'] ?? 0) * 100) !== $saved['price_cents']) throw new \RuntimeException('Meli no confirmó el precio. Actualizá el panel antes de reintentar.');
        $quote['draft']['pricing'] = $quote['pricing'];
        MercadoLibreProductDraft::save($this->pdo, (int) $quote['link']['product_id'], $quote['draft']);
        $this->pdo->prepare('DELETE FROM settings WHERE key=?')->execute(['meli_price_quote_' . $itemId]);
        return ['ok' => true, 'message' => 'Precio actualizado con las comisiones vigentes.'];
    }

    public function synchronizeListingStock(string $itemId, int $actorId): array
    {
        [$item, $token] = $this->ownedItem($itemId);
        $link = $this->listingLinks()[$itemId] ?? null;
        if (!$link || empty($item['user_product_id']) || !empty($item['variations'])) throw new \RuntimeException('La publicación no tiene una variante vinculada compatible con esta sincronización.');
        $path = '/user-products/' . rawurlencode($item['user_product_id']) . '/stock';
        $sync = new MercadoLibreStockSync($this->pdo, new ProductService($this->pdo));
        return $sync->synchronize((int) $link['variant_id'], $itemId, $actorId,
            function () use ($path, $token): array {
                [$code, $stock, $headers] = $this->request($path, $token);
                $locations = $stock['locations'] ?? [];
                if ($code !== 200 || count($locations) !== 1 || $locations[0]['type'] !== 'selling_address'
                    || !ctype_digit((string) ($headers['x-version'] ?? '')) || !is_int($locations[0]['quantity'] ?? null)) {
                    throw new \RuntimeException('El stock requiere un único depósito del vendedor y una versión confirmada por Meli.');
                }
                return ['quantity' => $locations[0]['quantity'], 'version' => $headers['x-version']];
            },
            function (int $quantity, string $version) use ($path, $token): int {
                [$code] = $this->request($path . '/type/selling_address', $token, ['quantity' => $quantity], true, 'PUT', ['x-version: ' . $version]);
                return $code;
            });
    }

    public function preparePublication(int $productId): array
    {
        $query = $this->pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $query->execute(['key' => 'meli_product_' . $productId]);
        $draft = MercadoLibreProductDraft::normalize(json_decode((string) $query->fetchColumn(), true));
        if (!$draft || !$draft['pricing']) throw new \RuntimeException('Guardá la ficha y calculá el precio antes de publicar.');
        $query = $this->pdo->prepare('SELECT v.id,v.sku,v.price_cents,v.stock_on_hand FROM product_variants v JOIN products p ON p.id=v.product_id WHERE p.id=:product AND v.id=:variant AND p.active=1 AND p.deleted_at IS NULL AND v.active=1');
        $query->execute(['product' => $productId, 'variant' => $draft['variant_id']]);
        $variant = $query->fetch();
        if (!$variant || (int) $variant['stock_on_hand'] < 1) throw new \RuntimeException('La variante no está activa o no tiene stock.');
        $draft['pricing']['base_price_cents'] = (int) $variant['price_cents'];
        $pricing = $this->calculateProductPrice($draft)['pricing'];
        $requirements = $this->productRequirements($draft['category_id']);
        $convert = static function (array $values, array $definitions): array {
            $attributes = [];
            foreach ($definitions as $definition) {
                $id = $definition['id'];
                $value = $values[$id] ?? '';
                if ($value === '') continue;
                $entry = ['id' => $id, 'value_name' => $value];
                foreach ($definition['values'] ?? [] as $option) {
                    if ($option['name'] === $value) { $entry = ['id' => $id, 'value_id' => $option['id']]; break; }
                }
                $attributes[] = $entry;
            }
            return $attributes;
        };
        $payload = ['category_id' => $draft['category_id'], 'price' => $pricing['price_cents'] / 100,
            'currency_id' => 'ARS', 'available_quantity' => (int) $variant['stock_on_hand'], 'buying_mode' => 'buy_it_now',
            'condition' => $draft['condition'], 'listing_type_id' => $draft['listing_type_id'],
            'pictures' => array_map(static fn ($url) => ['source' => $url], $draft['pictures']),
            'attributes' => $convert($draft['attributes'], $requirements['attributes']),
            'sale_terms' => $convert($draft['sale_terms'], $requirements['sale_terms']),
            'shipping' => ['mode' => $draft['shipping_mode'], 'local_pick_up' => $draft['local_pick_up'], 'free_shipping' => $draft['free_shipping']]];
        $payload[$requirements['user_product_seller'] ? 'family_name' : 'title'] = $draft['family_name'];
        if ($draft['catalog_product_id'] !== '') {
            $payload['catalog_product_id'] = $draft['catalog_product_id'];
            $payload['catalog_listing'] = true;
        }
        return ['payload' => $payload, 'draft' => $draft, 'pricing' => $pricing, 'variant_id' => (int) $variant['id']];
    }

    public function validatePublication(int $productId): array
    {
        $prepared = $this->preparePublication($productId);
        $tokens = $this->loadTokens();
        [$code, $conditional] = $this->request('/categories/' . $prepared['payload']['category_id'] . '/attributes/conditional',
            $tokens['access_token'], $prepared['payload'], true);
        if ($code !== 200) throw new \RuntimeException('No se pudieron validar los atributos condicionales.');
        [$code, $validation] = $this->request('/items/validate', $tokens['access_token'], $prepared['payload'], true);
        return ['ok' => true, 'valid' => $this->publicationValidationPassed($code, $validation), 'validation' => $validation,
            'conditional_required' => $conditional['required_attributes'] ?? [],
            'package_confirmed' => $prepared['draft']['package_confirmed'], 'price_cents' => $prepared['pricing']['price_cents']];
    }

    public function publishProduct(int $productId): array
    {
        $prepared = $this->preparePublication($productId);
        if (!$prepared['draft']['package_confirmed']) throw new \RuntimeException('Confirmá las medidas y el peso reales del paquete antes de publicar.');
        $tokens = $this->loadTokens();
        [$code, $validation] = $this->request('/items/validate', $tokens['access_token'], $prepared['payload'], true);
        if (!$this->publicationValidationPassed($code, $validation)) {
            $errors = array_map(static fn ($cause) => (string) ($cause['message'] ?? $cause['code'] ?? 'Dato pendiente'),
                array_filter($validation['cause'] ?? [], static fn ($cause) => ($cause['type'] ?? 'error') === 'error'));
            if (!$errors) $errors = array_map(static fn ($cause) => (string) ($cause['message'] ?? $cause['code'] ?? ''), $validation['cause'] ?? []);
            throw new \RuntimeException('Mercado Libre rechazó la ficha: ' . (implode(' · ', $errors) ?: 'Revisá los requisitos de publicación de la cuenta.'));
        }
        $key = 'meli_listing_' . $prepared['variant_id'];
        $record = ['state' => 'pending', 'product_id' => $productId, 'variant_id' => $prepared['variant_id'], 'created_at' => gmdate('c')];
        Database::immediate($this->pdo, static function (PDO $pdo) use ($key, $record): void {
            $query = $pdo->prepare('SELECT value FROM settings WHERE key=:key');
            $query->execute(['key' => $key]);
            $existing = json_decode((string) $query->fetchColumn(), true);
            if ($existing && ($existing['state'] ?? '') !== 'failed') throw new \RuntimeException('Ya existe una publicación o un envío pendiente para este producto. Consultá Meli antes de reintentar.');
            $query = $pdo->prepare('INSERT INTO settings(key,value) VALUES(:key,:value) ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=CURRENT_TIMESTAMP');
            $query->execute(['key' => $key, 'value' => json_encode($record, JSON_THROW_ON_ERROR)]);
        });
        // A timeout keeps the pending marker: never automatically repeat a creation.
        [$code, $item] = $this->request('/items', $tokens['access_token'], $prepared['payload'], true);
        if ($code !== 201 || empty($item['id'])) {
            if ($code >= 400 && $code < 500) $record['state'] = 'failed';
            $query = $this->pdo->prepare('UPDATE settings SET value=:value,updated_at=CURRENT_TIMESTAMP WHERE key=:key');
            $query->execute(['key' => $key, 'value' => json_encode($record, JSON_THROW_ON_ERROR)]);
            throw new \RuntimeException('Mercado Libre no confirmó la publicación. Revisá las publicaciones antes de reintentar.');
        }
        $record['state'] = 'published';
        $record['item_id'] = $item['id'];
        $record['permalink'] = $item['permalink'] ?? '';
        $query = $this->pdo->prepare('UPDATE settings SET value=:value,updated_at=CURRENT_TIMESTAMP WHERE key=:key');
        $query->execute(['key' => $key, 'value' => json_encode($record, JSON_THROW_ON_ERROR)]);
        $descriptionSaved = true;
        if ($prepared['draft']['description'] !== '') {
            try {
                [$descriptionCode] = $this->request('/items/' . $item['id'] . '/description', $tokens['access_token'],
                    ['plain_text' => $prepared['draft']['description']], true);
                $descriptionSaved = in_array($descriptionCode, [200, 201], true);
            } catch (\RuntimeException) {
                $descriptionSaved = false;
            }
        }
        return ['ok' => true, 'item_id' => $item['id'], 'permalink' => $record['permalink'], 'description_saved' => $descriptionSaved];
    }

    private function publicationValidationPassed(int $code, array $validation): bool
    {
        if (in_array($code, [200, 204], true)) return true;
        if ($code !== 400 || empty($validation['cause'])) return false;
        foreach ($validation['cause'] as $cause) {
            if (($cause['type'] ?? '') !== 'warning') return false;
        }
        return true;
    }

    private function requireConfiguration(): void
    {
        if (!$this->configured()) throw new \RuntimeException('Configurá la aplicación en el servidor antes de conectar.');
        if (!function_exists('curl_init') || !function_exists('openssl_encrypt')) {
            throw new \RuntimeException('La conexión requiere cURL y OpenSSL habilitados en el servidor.');
        }
    }

    private function refresh(array $tokens): array
    {
        if (empty($tokens['refresh_token'])) throw new \RuntimeException('La autorización venció. Volvé a conectar la cuenta.');
        $tokens = $this->tokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]);
        $this->saveTokens($tokens);
        return $tokens;
    }

    private function tokenRequest(array $params): array
    {
        $this->requireConfiguration();
        $params['client_id'] = $this->config['meli_client_id'];
        $params['client_secret'] = $this->config['meli_client_secret'];
        [$code, $tokens] = $this->request('/oauth/token', '', $params);
        if ($code !== 200 || empty($tokens['access_token']) || empty($tokens['refresh_token']) || empty($tokens['expires_in'])) {
            throw new \RuntimeException('No se pudo autorizar o renovar el acceso. Revisá la aplicación y volvé a conectar.');
        }
        $tokens['expires_at'] = time() + (int) $tokens['expires_in'];
        return $tokens;
    }

    private function request(string $path, string $token, ?array $body = null, bool $json = false, string $method = 'POST', array $extraHeaders = []): array
    {
        $this->requireConfiguration();
        $handle = curl_init('https://api.mercadolibre.com' . $path);
        $headers = ['Accept: application/json'];
        if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
        if ($body !== null) $headers[] = $json ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded';
        $responseHeaders = [];
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => array_merge($headers, $extraHeaders), CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) { [$name, $value] = explode(':', $line, 2); $responseHeaders[strtolower(trim($name))] = trim($value); }
                return strlen($line);
            }]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $json ? json_encode($body, JSON_THROW_ON_ERROR) : http_build_query($body));
        }
        $raw = curl_exec($handle);
        $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($raw === false) throw new \RuntimeException('No se pudo comunicar con Mercado Libre. Intentá verificar nuevamente.');
        return [$code, json_decode($raw, true) ?: [], $responseHeaders];
    }

    private function encryptionKey(): string
    {
        return hash('sha256', 'laboratorio-meli:' . $this->config['meli_client_secret'], true);
    }

    private function saveTokens(array $tokens): void
    {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt(json_encode($tokens, JSON_THROW_ON_ERROR), 'aes-256-gcm',
            $this->encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) throw new \RuntimeException('No se pudo guardar la autorización de forma segura.');
        $query = $this->pdo->prepare("INSERT INTO settings (key, value) VALUES ('meli_oauth_tokens', :value)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP");
        $query->execute(['value' => base64_encode($iv . $tag . $encrypted)]);
    }

    private function loadTokens(): ?array
    {
        $this->requireConfiguration();
        $query = $this->pdo->query("SELECT value FROM settings WHERE key = 'meli_oauth_tokens'");
        $value = $query->fetchColumn();
        if ($value === false) return null;
        $raw = base64_decode((string) $value, true);
        if ($raw === false || strlen($raw) < 29) throw new \RuntimeException('La autorización guardada no es válida. Volvé a conectar.');
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $this->encryptionKey(),
            OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        $tokens = $plain === false ? null : json_decode($plain, true);
        if (!is_array($tokens) || empty($tokens['access_token'])) throw new \RuntimeException('Volvé a autorizar la cuenta de Mercado Libre.');
        return $tokens;
    }
}
