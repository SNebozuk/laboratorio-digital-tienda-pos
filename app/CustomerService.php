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
        if ($name === '' || $phone === '') return;
        $query = $pdo->prepare('SELECT id FROM customers WHERE phone IN (:e164, :digits, :national, :old_country) ORDER BY CASE WHEN phone = :preferred THEN 0 ELSE 1 END, id LIMIT 1');
        $query->execute(self::phoneVariants($phone) + ['preferred' => $phone]);
        $id = (int) $query->fetchColumn();
        if ($id > 0) {
            $pdo->prepare('UPDATE customers SET name = :name, phone = :phone WHERE id = :id')->execute(['name' => $name, 'phone' => $phone, 'id' => $id]);
            return;
        }
        $pdo->prepare('INSERT INTO customers(name, phone) VALUES(:name, :phone)')->execute(['name' => $name, 'phone' => $phone]);
    }

    public static function normalizeWhatsapp(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\+?[\d\s().-]+$/', $value)) return '';
        $international = str_starts_with($value, '+') || str_starts_with($value, '00');
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
        if ($international && !str_starts_with($digits, '54')) return '';
        $hasCountry = str_starts_with($digits, '54') && strlen($digits) > 11;
        if ($hasCountry) $digits = substr($digits, 2);
        if ($hasCountry && str_starts_with($digits, '9') && in_array(strlen($digits), [11, 12], true)) $digits = substr($digits, 1);
        $digits = ltrim($digits, '0');
        if (str_starts_with($digits, '15')) return '';
        if (preg_match('/^(\d{2,4})15(\d+)$/', $digits, $matches) && in_array(strlen($matches[1] . $matches[2]), [10, 11], true)) {
            $digits = $matches[1] . $matches[2];
        }
        if (!in_array(strlen($digits), [10, 11], true)
            || preg_match('/^(\d)\1+$/', $digits)
            || in_array($digits, ['1234567890', '9876543210', '12345678901', '10987654321'], true)) return '';
        return '+549' . $digits;
    }

    /** @return array{e164:string,digits:string,national:string,old_country:string} */
    public static function phoneVariants(string $phone): array
    {
        $national = substr($phone, 4);
        return ['e164' => $phone, 'digits' => substr($phone, 1), 'national' => $national, 'old_country' => '54' . $national];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function present(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['whatsapp_url'] = 'https://wa.me/' . ltrim((string) $row['phone'], '+');
        return $row;
    }

    private static function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper(strtr($value, ['á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ü' => 'Ü', 'ñ' => 'Ñ']));
    }
}
