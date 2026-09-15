<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CheckoutGoogleService
{
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
        });
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
        return rtrim(trim((string) ($this->config['base_url'] ?? '')), '/');
    }
}
