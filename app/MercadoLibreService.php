<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

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

    private function request(string $path, string $token, ?array $body = null): array
    {
        $this->requireConfiguration();
        $handle = curl_init('https://api.mercadolibre.com' . $path);
        $headers = ['Accept: application/json'];
        if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
        if ($body !== null) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($body));
        }
        $raw = curl_exec($handle);
        $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($raw === false) throw new \RuntimeException('No se pudo comunicar con Mercado Libre. Intentá verificar nuevamente.');
        return [$code, json_decode($raw, true) ?: []];
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
