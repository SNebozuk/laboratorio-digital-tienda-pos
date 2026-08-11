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
        'business_hours',
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
                'pickup_address',
                'business_hours'
             )"
        );
        $values = [];
        foreach ($query->fetchAll() as $row) {
            $values[(string) $row['key']] = (string) $row['value'];
        }
        if (empty($values['bank_alias'])) {
            $values['bank_alias'] = 'labdigital';
        }
        if (empty($values['bank_holder']) || $values['bank_holder'] === 'Laboratorio Digital') {
            $values['bank_holder'] = 'Allessandra Lear · Banco Galicia';
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
        $businessHours = trim((string) ($data['business_hours'] ?? ''));
        if (strlen($businessHours) < 3 || strlen($businessHours) > 300) {
            throw new ValidationException('Revisá los horarios de atención.');
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
            'business_hours' => $businessHours,
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

    /** @return array{intro: string, rows: list<array<string, string>>} */
    public function sizeGuide(): array
    {
        $query = $this->pdo->query(
            "SELECT key, value
             FROM settings
             WHERE key IN ('size_guide_intro', 'size_guide_json')"
        );
        $values = [];
        foreach ($query->fetchAll() as $row) {
            $values[(string) $row['key']] = (string) $row['value'];
        }

        $decoded = json_decode(
            (string) ($values['size_guide_json'] ?? '[]'),
            true
        );
        $rows = [];
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $group = trim((string) ($row['group'] ?? ''));
                $size = trim((string) ($row['size'] ?? ''));
                if ($group === '' || $size === '') {
                    continue;
                }
                $rows[] = [
                    'group' => $group,
                    'size' => $size,
                    'width' => trim((string) ($row['width'] ?? '')),
                    'length' => trim((string) ($row['length'] ?? '')),
                    'note' => trim((string) ($row['note'] ?? '')),
                ];
            }
        }

        if ($rows === []) {
            $rows = $this->defaultSizeGuideRows();
        }

        return [
            'intro' => trim((string) ($values['size_guide_intro'] ?? '')) ?: 'Las medidas son de la prenda extendida y sin lavar. Puede existir una variación de hasta ±2 cm según el lote. Lavar con agua fría y evitar el secado al sol directo ayuda a conservar el talle.',
            'rows' => $rows,
        ];
    }

    /** @return list<array<string, string>> */
    private function defaultSizeGuideRows(): array
    {
        $tables = [
            'Niño · Algodón peinado' => [['4','30','40'],['6','33','44'],['8','36','47'],['10','37','49'],['12','39','52'],['14','42','55'],['16','45','59']],
            'Niño · Modal / Spum' => [['6','33','43'],['8','33','46'],['10','35','48'],['12','37','50'],['14','37','53'],['16','43','56']],
            'Unisex · Algodón peinado 24.1' => [['1','46','66'],['2','50','67'],['3','53','69'],['4','55','72'],['5','57','76'],['6','59','77'],['8','61','82'],['10','63','84']],
            'Unisex · Algodón peinado 20.1' => [['1','53','66'],['2','55','68'],['3','58','72'],['4','59','74'],['5','60','75'],['6','63','78'],['8','65','81'],['10','68','83'],['12','69','86'],['14','72','86']],
            'Oversize · Algodón peinado 20.1' => [['1','56','73'],['2','60','74'],['3','61','75'],['4','62','78'],['5','63','80']],
            'Unisex · Modal / Spum' => [['1','48','64'],['2','49','65'],['3','52','66'],['4','56','68'],['5','58','72'],['6','62','79'],['8','65','80']],
            'Mujer · Algodón peinado' => [['1','39','55'],['2','42','58'],['3','43','61'],['4','46','64'],['5','49','68']],
            'Mujer · Modal / Spum' => [['1','38','53'],['2','41','57'],['3','43','58'],['4','44','62'],['5','47','62'],['6','50','66'],['8','52','67']],
            'Buzo cuello redondo · Friza clásica' => [['1','52','66'],['2','55','67'],['3','56','67'],['4','59','72'],['5','63','73'],['6','64','76'],['8','65','79'],['10','67','82']],
            'Buzo canguro · Friza clásica' => [['1','53','61'],['2','54','63'],['3','57','67'],['4','58','70'],['5','61','72'],['6','63','78'],['8','71','82']],
            'Campera · Friza clásica' => [['1','51','60'],['2','53','64'],['3','56','68'],['4','59','71'],['5','62','73']],
        ];
        $rows = [];
        foreach ($tables as $group => $sizes) {
            foreach ($sizes as [$size, $width, $length]) {
                $rows[] = ['group' => $group, 'size' => $size, 'width' => $width . ' cm', 'length' => $length . ' cm', 'note' => ''];
            }
        }
        return $rows;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{intro: string, rows: list<array<string, string>>}
     */
    public function updateSizeGuide(array $data): array
    {
        $intro = trim((string) ($data['intro'] ?? ''));
        if (strlen($intro) > 1000) {
            throw new ValidationException('La introduccion es demasiado larga.');
        }

        $inputRows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        if (count($inputRows) > 200) {
            throw new ValidationException('La tabla admite hasta 200 filas.');
        }

        $rows = [];
        foreach ($inputRows as $row) {
            if (!is_array($row)) {
                throw new ValidationException('Hay una fila de talles invalida.');
            }
            $normalized = [
                'group' => trim((string) ($row['group'] ?? '')),
                'size' => trim((string) ($row['size'] ?? '')),
                'width' => trim((string) ($row['width'] ?? '')),
                'length' => trim((string) ($row['length'] ?? '')),
                'note' => trim((string) ($row['note'] ?? '')),
            ];
            if ($normalized['group'] === '' || $normalized['size'] === '') {
                throw new ValidationException(
                    'Cada fila necesita una prenda o tabla y un talle.'
                );
            }
            foreach ($normalized as $value) {
                if (strlen($value) > 160) {
                    throw new ValidationException('Una medida es demasiado larga.');
                }
            }
            $rows[] = $normalized;
        }

        $values = [
            'size_guide_intro' => $intro,
            'size_guide_json' => json_encode(
                $rows,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            ),
        ];

        Database::immediate(
            $this->pdo,
            static function (PDO $pdo) use ($values): void {
                $update = $pdo->prepare(
                    'INSERT INTO settings(key, value, updated_at)
                     VALUES(:key, :value, CURRENT_TIMESTAMP)
                     ON CONFLICT(key) DO UPDATE SET
                        value = excluded.value,
                        updated_at = CURRENT_TIMESTAMP'
                );
                foreach ($values as $key => $value) {
                    $update->execute(['key' => $key, 'value' => $value]);
                }
            }
        );

        return $this->sizeGuide();
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
