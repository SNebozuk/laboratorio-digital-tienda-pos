<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CheckoutGoogleService
{
    private const SESSION_COOKIE = 'laboratorio_google_customer';
    private const SESSION_LIFETIME = 31536000;
    /** @param array<string, mixed> $config */
    public function __construct(private readonly PDO $pdo, private readonly array $config)
    {
    }

    public function enabled(): bool
    {
        return trim((string) ($this->config['google_client_id'] ?? '')) !== ''
            && trim((string) ($this->config['google_client_secret'] ?? '')) !== ''
            && $this->baseUrl() !== '';
    }

    public function callbackUrl(): string
    {
        return $this->publicUrl('/google-callback.php');
    }

    public function loginUrl(): string
    {
        return $this->publicUrl('/google-login.php');
    }

    public function storeUrl(): string
    {
        return $this->publicUrl('/');
    }

    /** @return array{id:int,name:string,first_name:string,last_name:string,email:string}|null */
    public function customer(): ?array
    {
        $id = (int) ($_SESSION['checkout_google_customer_id'] ?? 0);
        if ($id < 1) {
            $token = trim((string) ($_COOKIE[self::SESSION_COOKIE] ?? ''));
            if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
                $query = $this->pdo->prepare('SELECT customer_id FROM checkout_customer_sessions WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP');
                $query->execute(['token_hash' => hash('sha256', $token)]);
                $id = (int) $query->fetchColumn();
                if ($id > 0) {
                    $_SESSION['checkout_google_customer_id'] = $id;
                    $this->pdo->prepare('UPDATE checkout_customer_sessions SET last_used_at = CURRENT_TIMESTAMP WHERE token_hash = :token_hash')->execute(['token_hash' => hash('sha256', $token)]);
                }
            }
        }
        if ($id < 1) return null;
        $query = $this->pdo->prepare('SELECT id, name, first_name, last_name, email FROM checkout_customers WHERE id = :id');
        $query->execute(['id' => $id]);
        $customer = $query->fetch();
        if (!is_array($customer)) {
            unset($_SESSION['checkout_google_customer_id']);
            return null;
        }
        return [
            'id' => (int) $customer['id'],
            'name' => (string) $customer['name'],
            'first_name' => (string) $customer['first_name'],
            'last_name' => (string) $customer['last_name'],
            'email' => (string) $customer['email'],
        ];
    }

    /** @param array<string, mixed> $profile */
    public function linkProfile(array $profile): void
    {
        $googleSub = trim((string) ($profile['sub'] ?? ''));
        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        if ($googleSub === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Google no confirmó una cuenta válida.');
        }

        $firstName = trim((string) ($profile['given_name'] ?? ''));
        $lastName = trim((string) ($profile['family_name'] ?? ''));
        $name = trim((string) ($profile['name'] ?? ''));
        if ($name === '') $name = trim($firstName . ' ' . $lastName);
        if ($name === '') throw new \RuntimeException('Google no devolvió tu nombre completo.');

        Database::immediate($this->pdo, function (PDO $pdo) use ($googleSub, $email, $firstName, $lastName, $name): void {
            $byGoogle = $pdo->prepare('SELECT id FROM checkout_customers WHERE google_sub = :google_sub');
            $byGoogle->execute(['google_sub' => $googleSub]);
            $googleCustomer = $byGoogle->fetch();
            $byEmail = $pdo->prepare('SELECT id FROM checkout_customers WHERE email = :email COLLATE NOCASE');
            $byEmail->execute(['email' => $email]);
            $emailCustomer = $byEmail->fetch();
            if ($googleCustomer && $emailCustomer && (int) $googleCustomer['id'] !== (int) $emailCustomer['id']) {
                throw new \RuntimeException('Esta cuenta de Google ya está vinculada a otro cliente.');
            }

            $id = (int) (($googleCustomer ?: $emailCustomer)['id'] ?? 0);
            if ($id > 0) {
                $update = $pdo->prepare('UPDATE checkout_customers SET google_sub = :google_sub, first_name = :first_name, last_name = :last_name, name = :name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $update->execute([
                    'google_sub' => $googleSub,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $name,
                    'email' => $email,
                    'id' => $id,
                ]);
            } else {
                $insert = $pdo->prepare('INSERT INTO checkout_customers(google_sub, first_name, last_name, name, email) VALUES(:google_sub, :first_name, :last_name, :name, :email)');
                $insert->execute([
                    'google_sub' => $googleSub,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $name,
                    'email' => $email,
                ]);
                $id = (int) $pdo->lastInsertId();
            }
            session_regenerate_id(true);
            $_SESSION['checkout_google_customer_id'] = $id;
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('DELETE FROM checkout_customer_sessions WHERE customer_id = :customer_id OR expires_at <= CURRENT_TIMESTAMP')->execute(['customer_id' => $id]);
            $pdo->prepare("INSERT INTO checkout_customer_sessions(customer_id, token_hash, expires_at) VALUES(:customer_id, :token_hash, datetime('now', '+365 days'))")->execute(['customer_id' => $id, 'token_hash' => hash('sha256', $token)]);
            setcookie(self::SESSION_COOKIE, $token, [
                'expires' => time() + self::SESSION_LIFETIME,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        });
    }

    /** @return list<array<string, mixed>> */
    public function customers(): array
    {
        return $this->pdo->query('SELECT id, name, first_name, last_name, email, google_sub IS NOT NULL AS google_connected, created_at, updated_at FROM checkout_customers ORDER BY updated_at DESC, id DESC')->fetchAll();
    }

    private function publicUrl(string $path): string
    {
        $baseUrl = $this->baseUrl();
        if ($baseUrl === '') return '';
        $storePath = trim((string) ($this->config['public_store_path'] ?? ''), '/');
        return $baseUrl . ($storePath === '' ? '' : '/' . $storePath) . $path;
    }

    private function baseUrl(): string
    {
        $configured = rtrim(trim((string) ($this->config['base_url'] ?? '')), '/');
        $configuredHost = strtolower((string) (parse_url($configured, PHP_URL_HOST) ?? ''));
        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $allowedHosts = array_filter([$configuredHost, $configuredHost === '' ? '' : 'www.' . $configuredHost]);
        if ($currentHost !== '' && in_array($currentHost, $allowedHosts, true)) {
            return 'https://' . $currentHost;
        }
        return $configured;
    }
}
