<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class SettingsService
{
    private const EDITABLE_KEYS = [
        'store_name',
        'sales_email',
        'whatsapp_number',
        'payment_window_minutes',
        'rejected_retry_minutes',
        'proof_max_bytes',
        'bank_holder',
        'bank_alias',
        'bank_cbu',
        'pickup_address',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        $query = $this->pdo->query(
            "SELECT key, value
             FROM settings
             WHERE key IN (
                'store_name',
                'sales_email',
                'whatsapp_number',
                'payment_window_minutes',
                'rejected_retry_minutes',
                'proof_max_bytes',
                'bank_holder',
                'bank_alias',
                'bank_cbu',
                'pickup_address'
             )"
        );
        $values = [];
        foreach ($query->fetchAll() as $row) {
            $values[(string) $row['key']] = (string) $row['value'];
        }
        $values['proof_max_mb'] = round(
            ((int) ($values['proof_max_bytes'] ?? 8388608)) / 1024 / 1024,
            1
        );

        return $values;
    }

    /** @param array<string, mixed> $data */
    public function update(array $data): array
    {
        $storeName = $this->text($data, 'store_name', 2, 100);
        $salesEmail = trim((string) ($data['sales_email'] ?? ''));
        if (!filter_var($salesEmail, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Ingresá un email de ventas válido.');
        }

        $whatsapp = preg_replace(
            '/\D+/',
            '',
            (string) ($data['whatsapp_number'] ?? '')
        );
        if (strlen((string) $whatsapp) < 10 || strlen((string) $whatsapp) > 20) {
            throw new ValidationException(
                'Ingresá el WhatsApp con código de país y área.'
            );
        }

        $paymentMinutes = $this->integer(
            $data,
            'payment_window_minutes',
            15,
            10080
        );
        $retryMinutes = $this->integer(
            $data,
            'rejected_retry_minutes',
            15,
            10080
        );
        $proofMaxMb = $this->integer($data, 'proof_max_mb', 1, 20);

        $bankHolder = $this->text($data, 'bank_holder', 2, 120);
        $bankAlias = trim((string) ($data['bank_alias'] ?? ''));
        if (strlen($bankAlias) > 100) {
            throw new ValidationException('El alias es demasiado largo.');
        }
        $bankCbu = preg_replace('/\D+/', '', (string) ($data['bank_cbu'] ?? ''));
        if ($bankCbu !== '' && strlen((string) $bankCbu) !== 22) {
            throw new ValidationException('El CBU o CVU debe tener 22 dígitos.');
        }
        if ($bankAlias === '' && $bankCbu === '') {
            throw new ValidationException('Ingresá al menos un alias o CBU.');
        }

        $pickupAddress = trim((string) ($data['pickup_address'] ?? ''));
        if (strlen($pickupAddress) > 250) {
            throw new ValidationException('La dirección de retiro es demasiado larga.');
        }

        $values = [
            'store_name' => $storeName,
            'sales_email' => $salesEmail,
            'whatsapp_number' => (string) $whatsapp,
            'payment_window_minutes' => (string) $paymentMinutes,
            'rejected_retry_minutes' => (string) $retryMinutes,
            'proof_max_bytes' => (string) ($proofMaxMb * 1024 * 1024),
            'bank_holder' => $bankHolder,
            'bank_alias' => $bankAlias,
            'bank_cbu' => (string) $bankCbu,
            'pickup_address' => $pickupAddress,
        ];

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($values): void {
                $update = $pdo->prepare(
                    'INSERT INTO settings(key, value, updated_at)
                     VALUES(:key, :value, CURRENT_TIMESTAMP)
                     ON CONFLICT(key) DO UPDATE SET
                        value = excluded.value,
                        updated_at = CURRENT_TIMESTAMP'
                );
                foreach (self::EDITABLE_KEYS as $key) {
                    $update->execute([
                        'key' => $key,
                        'value' => $values[$key],
                    ]);
                }
            }
        );

        return $this->values();
    }

    /** @param array<string, mixed> $data */
    private function text(
        array $data,
        string $key,
        int $minimumLength,
        int $maximumLength
    ): string {
        $value = trim((string) ($data[$key] ?? ''));
        $length = strlen($value);
        if ($length < $minimumLength || $length > $maximumLength) {
            throw new ValidationException('Revisá el campo ' . $key . '.');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function integer(
        array $data,
        string $key,
        int $minimum,
        int $maximum
    ): int {
        $value = filter_var(
            $data[$key] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => $minimum, 'max_range' => $maximum]]
        );
        if ($value === false) {
            throw new ValidationException('Revisá el campo ' . $key . '.');
        }

        return (int) $value;
    }
}
