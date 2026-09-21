<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CheckoutGoogleService
{
    private const SESSION_COOKIE = 'laboratorio_google_customer';
    private const SESSION_LIFETIME = 31536000;
    private const TRUSTED_STORE_ROOTS = ['laboratorio-digital.com.ar', 'artjet.com.ar'];
    /** @param array<string, mixed> $config */
    public function __construct(private readonly PDO $pdo, private readonly array $config)
    {
    }

    public function enabled(): bool
    {
        return $this->baseUrl() !== '';
    }

    public function callbackUrl(): string
    {
        return $this->publicUrl('/google-callback.php');
    }

    public function loginUrl(): string
    {
        return $this->publicUrl('/customer-login.php');
    }

    public function storeUrl(): string
    {
        return $this->publicUrl('/');
    }

    /** @return array{id:int,name:string,first_name:string,last_name:string,email:string,phone:string}|null */
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
                    $this->setPersistentCookie($token);
                }
            }
        }
        if ($id < 1) return null;
        $query = $this->pdo->prepare('SELECT id, name, first_name, last_name, email, phone FROM checkout_customers WHERE id = :id');
        $query->execute(['id' => $id]);
        $customer = $query->fetch();
        if (!is_array($customer)) {
            unset($_SESSION['checkout_google_customer_id']);
            return null;
        }
        $normalizedPhone = CustomerService::normalizeWhatsapp((string) $customer['phone']);
        $phoneChanged = $normalizedPhone !== '' && $normalizedPhone !== (string) $customer['phone'];
        if ($phoneChanged) {
            $this->pdo->prepare('UPDATE checkout_customers SET phone = :phone, updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['phone' => $normalizedPhone, 'id' => $id]);
            $customer['phone'] = $normalizedPhone;
        }
        if ($phoneChanged || (int) ($_SESSION['checkout_customer_synced'] ?? 0) !== $id) {
            CustomerService::save($this->pdo, (string) $customer['name'], (string) $customer['phone']);
            $_SESSION['checkout_customer_synced'] = $id;
        }
        return [
            'id' => (int) $customer['id'],
            'name' => (string) $customer['name'],
            'first_name' => (string) $customer['first_name'],
            'last_name' => (string) $customer['last_name'],
            'email' => str_ends_with((string) $customer['email'], '@local.invalid') ? '' : (string) $customer['email'],
            'phone' => (string) $customer['phone'],
        ];
    }

    public function createLocalCustomer(string $firstName, string $lastName, string $phone): void
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $phone = CustomerService::normalizeWhatsapp($phone);
        if (!self::validNamePart($firstName)
            || !self::validNamePart($lastName)
            || $phone === '') {
            throw new \RuntimeException('Ingresá nombre, apellido y un WhatsApp válidos.');
        }
        $firstName = self::upper($firstName);
        $lastName = self::upper($lastName);

        Database::immediate($this->pdo, function (PDO $pdo) use ($firstName, $lastName, $phone): void {
            $name = trim($firstName . ' ' . $lastName);
            $lookup = $pdo->prepare('SELECT id, name FROM checkout_customers WHERE phone IN (:e164, :digits, :national, :old_country) ORDER BY CASE WHEN phone = :preferred THEN 0 ELSE 1 END, id LIMIT 1');
            $lookup->execute(CustomerService::phoneVariants($phone) + ['preferred' => $phone]);
            $existing = $lookup->fetch();
            if ($existing && self::upper((string) $existing['name']) !== $name) {
                throw new \RuntimeException('Ese WhatsApp ya está registrado con otro nombre. Revisá los datos o consultanos.');
            }
            if ($existing) {
                $id = (int) $existing['id'];
                $pdo->prepare('UPDATE checkout_customers SET phone = :phone, updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['phone' => $phone, 'id' => $id]);
            } else {
                $email = bin2hex(random_bytes(16)) . '@local.invalid';
                $insert = $pdo->prepare('INSERT INTO checkout_customers(first_name, last_name, name, email, phone) VALUES(:first_name, :last_name, :name, :email, :phone)');
                $insert->execute(['first_name' => $firstName, 'last_name' => $lastName, 'name' => $name, 'email' => $email, 'phone' => $phone]);
                $id = (int) $pdo->lastInsertId();
            }
            CustomerService::save($pdo, $name, $phone);
            session_regenerate_id(true);
            $_SESSION['checkout_google_customer_id'] = $id;
            $_SESSION['checkout_customer_synced'] = $id;
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("INSERT INTO checkout_customer_sessions(customer_id, token_hash, expires_at) VALUES(:customer_id, :token_hash, datetime('now', '+365 days'))")
                ->execute(['customer_id' => $id, 'token_hash' => hash('sha256', $token)]);
            $this->setPersistentCookie($token);
        });
    }

    public static function validNamePart(string $value): bool
    {
        $value = trim($value);
        $length = preg_match_all('/./us', $value);
        if ($length === false || $length < 2 || $length > 60
            || !preg_match("/^\\p{L}[\\p{L}'’.-]*(?: +\\p{L}[\\p{L}'’.-]*)*$/u", $value)
            || preg_match('/(\\p{L})\\1{3,}/iu', $value)) return false;
        $plain = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return !in_array($plain, ['asdf', 'qwerty', 'abc', 'abcd', 'test', 'testing', 'prueba', 'nombre', 'apellido', 'usuario', 'anonimo', 'anónimo', 'cliente', 'xxx', 'xxxx', 'nn', 'n n'], true);
    }

    /** @return array{name:string}|null */
    public function knownWhatsapp(string $phone): ?array
    {
        $phone = CustomerService::normalizeWhatsapp($phone);
        if ($phone === '') return null;
        $query = $this->pdo->prepare('SELECT name FROM checkout_customers WHERE phone IN (:e164, :digits, :national, :old_country) ORDER BY CASE WHEN phone = :preferred THEN 0 ELSE 1 END, id LIMIT 1');
        $query->execute(CustomerService::phoneVariants($phone) + ['preferred' => $phone]);
        $name = $query->fetchColumn();
        if ($name === false) {
            $query = $this->pdo->prepare('SELECT name FROM customers WHERE phone IN (:e164, :digits, :national, :old_country) ORDER BY CASE WHEN phone = :preferred THEN 0 ELSE 1 END, id LIMIT 1');
            $query->execute(CustomerService::phoneVariants($phone) + ['preferred' => $phone]);
            $name = $query->fetchColumn();
        }
        return $name === false ? null : ['name' => (string) $name];
    }

    public function updateLocalCustomer(string $firstName, string $lastName, string $phone): void
    {
        $customer = $this->customer();
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $phone = CustomerService::normalizeWhatsapp($phone);
        if ($customer === null
            || !preg_match("/^\\p{L}[\\p{L}'’.-]{1,}$/u", $firstName)
            || !preg_match("/^\\p{L}[\\p{L}'’.-]{1,}$/u", $lastName)
            || $phone === '') {
            throw new \RuntimeException('Ingresá nombre, apellido y un WhatsApp válidos.');
        }
        $firstName = self::upper($firstName);
        $lastName = self::upper($lastName);
        $this->pdo->prepare('UPDATE checkout_customers SET first_name = :first_name, last_name = :last_name, name = :name, phone = :phone, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim($firstName . ' ' . $lastName),
                'phone' => $phone,
                'id' => $customer['id'],
            ]);
        CustomerService::save($this->pdo, trim($firstName . ' ' . $lastName), $phone);
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
        $phone = CustomerService::normalizeWhatsapp((string) ($profile['phone'] ?? ''));
        if ($phone === '') throw new \RuntimeException('Ingresá un WhatsApp válido.');
        if ($name === '') $name = trim($firstName . ' ' . $lastName);
        if ($name === '') throw new \RuntimeException('Google no devolvió tu nombre completo.');

        Database::immediate($this->pdo, function (PDO $pdo) use ($googleSub, $email, $firstName, $lastName, $name, $phone): void {
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
                $update = $pdo->prepare('UPDATE checkout_customers SET google_sub = :google_sub, first_name = :first_name, last_name = :last_name, name = :name, email = :email, phone = :phone, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $update->execute([
                    'google_sub' => $googleSub,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'id' => $id,
                ]);
            } else {
                $insert = $pdo->prepare('INSERT INTO checkout_customers(google_sub, first_name, last_name, name, email, phone) VALUES(:google_sub, :first_name, :last_name, :name, :email, :phone)');
                $insert->execute([
                    'google_sub' => $googleSub,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                ]);
                $id = (int) $pdo->lastInsertId();
            }
            session_regenerate_id(true);
            $_SESSION['checkout_google_customer_id'] = $id;
            CustomerService::save($pdo, $name, $phone);
            $_SESSION['checkout_customer_synced'] = $id;
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('DELETE FROM checkout_customer_sessions WHERE customer_id = :customer_id OR expires_at <= CURRENT_TIMESTAMP')->execute(['customer_id' => $id]);
            $pdo->prepare("INSERT INTO checkout_customer_sessions(customer_id, token_hash, expires_at) VALUES(:customer_id, :token_hash, datetime('now', '+365 days'))")->execute(['customer_id' => $id, 'token_hash' => hash('sha256', $token)]);
            $this->setPersistentCookie($token);
        });
    }

    /** @return list<array<string, mixed>> */
    public function customers(): array
    {
        $customers = $this->pdo->query('SELECT id, name, first_name, last_name, email, phone, created_at, updated_at FROM checkout_customers ORDER BY updated_at DESC, id DESC')->fetchAll();
        foreach ($customers as &$customer) {
            if (str_ends_with((string) $customer['email'], '@local.invalid')) $customer['email'] = '';
        }
        unset($customer);
        return $customers;
    }

    private function publicUrl(string $path): string
    {
        $baseUrl = $this->baseUrl();
        if ($baseUrl === '') return '';
        $storePath = trim((string) ($this->config['public_store_path'] ?? ''), '/');
        return $baseUrl . ($storePath === '' ? '' : '/' . $storePath) . $path;
    }

    private static function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper(strtr($value, ['á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ü' => 'Ü', 'ñ' => 'Ñ']));
    }

    private function setPersistentCookie(string $token): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) parse_url((string) ($this->config['base_url'] ?? ''), PHP_URL_SCHEME)) === 'https';
        $options = [
            'expires' => time() + self::SESSION_LIFETIME,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        $currentHost = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''))) ?: '';
        $currentRoot = preg_replace('/^www\./', '', $currentHost) ?: '';
        if ($currentRoot !== '' && in_array($currentRoot, $this->trustedStoreRoots(), true)) {
            setcookie(self::SESSION_COOKIE, '', [...$options, 'expires' => time() - 3600]);
            $options['domain'] = '.' . $currentRoot;
        }
        setcookie(self::SESSION_COOKIE, $token, $options);
    }

    private function baseUrl(): string
    {
        $configured = rtrim(trim((string) ($this->config['base_url'] ?? '')), '/');
        $currentHost = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''))) ?: '';
        $currentRoot = preg_replace('/^www\./', '', $currentHost) ?: '';
        if ($currentRoot !== '' && in_array($currentRoot, $this->trustedStoreRoots(), true)) {
            return 'https://' . $currentHost;
        }
        return $configured;
    }

    /** @return list<string> */
    private function trustedStoreRoots(): array
    {
        $configuredHost = strtolower((string) parse_url((string) ($this->config['base_url'] ?? ''), PHP_URL_HOST));
        $configuredRoot = preg_replace('/^www\./', '', $configuredHost) ?: '';
        return array_values(array_unique(array_filter([$configuredRoot, ...self::TRUSTED_STORE_ROOTS])));
    }
}
