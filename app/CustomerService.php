<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CustomerService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{id:int,name:string,phone:string,whatsapp_url:string}> */
    public function all(): array
    {
        return array_map([$this, 'present'], $this->pdo->query('SELECT id, name, phone FROM customers ORDER BY name COLLATE NOCASE, id')->fetchAll());
    }

    /** @return list<array{id:int,name:string,phone:string,whatsapp_url:string}> */
    public function search(string $value): array
    {
        $search = trim($value);
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        $conditions = [];
        $parameters = [];
        if ($digits !== '' && preg_match('/^[\d\s()+-]+$/', $search)) {
            $conditions[] = 'phone LIKE :phone';
            $parameters['phone'] = '%' . $digits . '%';
        } else {
            $words = preg_split('/\s+/u', self::upper($search)) ?: [];
            foreach (array_values(array_filter($words)) as $index => $word) {
                $key = 'word_' . $index;
                $conditions[] = "upper(name) LIKE :{$key}";
                $parameters[$key] = '%' . $word . '%';
            }
        }
        if ($conditions === []) return [];
        $query = $this->pdo->prepare('SELECT id, name, phone FROM customers WHERE (' . implode(' AND ', $conditions) . ') ORDER BY name COLLATE NOCASE, id LIMIT 12');
        $query->execute($parameters);
        return array_map([$this, 'present'], $query->fetchAll());
    }

    public static function save(PDO $pdo, string $name, string $phone): void
    {
        $name = self::upper(preg_replace('/\s+/u', ' ', trim($name)) ?? '');
        $phone = self::normalizeWhatsapp($phone);
        if ($name === '' || strlen($phone) < 11) return;
        $query = $pdo->prepare('SELECT id FROM customers WHERE phone = :phone LIMIT 1');
        $query->execute(['phone' => $phone]);
        $id = (int) $query->fetchColumn();
        if ($id > 0) {
            $pdo->prepare('UPDATE customers SET name = :name WHERE id = :id')->execute(['name' => $name, 'id' => $id]);
            return;
        }
        $pdo->prepare('INSERT INTO customers(name, phone) VALUES(:name, :phone)')->execute(['name' => $name, 'phone' => $phone]);
    }

    public static function normalizeWhatsapp(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
        if (str_starts_with($digits, '549')) return $digits;
        if (str_starts_with($digits, '54')) return '549' . substr($digits, 2);
        $digits = ltrim($digits, '0');
        return strlen($digits) === 10 ? '549' . $digits : $digits;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function present(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['whatsapp_url'] = 'https://wa.me/' . $row['phone'];
        return $row;
    }

    private static function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
