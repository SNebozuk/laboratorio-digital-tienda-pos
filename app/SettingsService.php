<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class SettingsService
{
    private const EDITABLE_KEYS = [
        'store_name',
        'whatsapp_number',
        'payment_window_minutes',
        'rejected_retry_minutes',
        'proof_max_bytes',
        'bank_holder',
        'bank_alias',
        'bank_cbu',
        'pickup_address',
        'business_hours',
        'cart_maintenance_enabled',
        'reward_surprise_enabled', 'reward_surprise_percent', 'reward_surprise_probability', 'reward_surprise_text', 'reward_surprise_continue_text',
        'reward_quantity_enabled', 'reward_quantity_units', 'reward_quantity_percent', 'reward_quantity_pending_text', 'reward_quantity_unlocked_text',
        'reward_cart_animation_enabled', 'reward_cart_sound_enabled', 'reward_checkout_celebration_enabled', 'reward_checkout_confetti_enabled', 'reward_microinteractions_enabled',
        'reward_klaus_enabled', 'reward_klaus_animations_enabled', 'reward_klaus_messages_enabled', 'reward_klaus_happy_text', 'reward_klaus_near_text', 'reward_klaus_surprise_text', 'reward_klaus_complete_text',
        'pulga_enabled', 'pulga_frequency_seconds', 'pulga_animations_enabled',
        'whatsapp_message_order_created',
        'whatsapp_message_cash_created',
        'whatsapp_message_ready_pickup',
        'whatsapp_message_cancelled',
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
                'whatsapp_number',
                'payment_window_minutes',
                'rejected_retry_minutes',
                'proof_max_bytes',
                'bank_holder',
                'bank_alias',
                'bank_cbu',
                'pickup_address',
                'business_hours',
                'cart_maintenance_enabled',
                'reward_surprise_enabled', 'reward_surprise_percent', 'reward_surprise_probability', 'reward_surprise_text', 'reward_surprise_continue_text',
                'reward_quantity_enabled', 'reward_quantity_units', 'reward_quantity_percent', 'reward_quantity_pending_text', 'reward_quantity_unlocked_text',
                'reward_cart_animation_enabled', 'reward_cart_sound_enabled', 'reward_checkout_celebration_enabled', 'reward_checkout_confetti_enabled', 'reward_microinteractions_enabled',
                'reward_klaus_enabled', 'reward_klaus_animations_enabled', 'reward_klaus_messages_enabled', 'reward_klaus_happy_text', 'reward_klaus_near_text', 'reward_klaus_surprise_text', 'reward_klaus_complete_text',
                'pulga_enabled', 'pulga_frequency_seconds', 'pulga_animations_enabled',
                'whatsapp_message_order_created', 'whatsapp_message_cash_created',
                'whatsapp_message_ready_pickup', 'whatsapp_message_cancelled'
             )"
        );
        $values = [];
        foreach ($query->fetchAll() as $row) {
            $values[(string) $row['key']] = (string) $row['value'];
        }
        if (empty($values['bank_alias'])) {
            $values['bank_alias'] = 'labdigital';
        }
        if (empty($values['bank_cbu'])) {
            $values['bank_cbu'] = '0070146030004048890954';
        }
        if (empty($values['bank_holder']) || $values['bank_holder'] === 'Laboratorio Digital') {
            $values['bank_holder'] = 'Allessandra Lear · Banco Galicia';
        }
        $values['proof_max_mb'] = round(
            ((int) ($values['proof_max_bytes'] ?? 8388608)) / 1024 / 1024,
            1
        );
        $values += [
            'business_hours' => 'Lunes a viernes de 9:30 a 17 · Sábados de 9:30 a 12:30',
            'cart_maintenance_enabled' => '0',
            'reward_surprise_enabled' => '1', 'reward_surprise_percent' => '5', 'reward_surprise_probability' => '10', 'reward_surprise_text' => '🎁 ¡Sorpresa! Ganaste 5% de descuento en este carrito.', 'reward_surprise_continue_text' => 'Tu 5% ya está asegurado. Podés seguir agregando productos y aprovecharlo en todo este pedido.',
            'reward_quantity_enabled' => '1', 'reward_quantity_units' => '20', 'reward_quantity_percent' => '3', 'reward_quantity_pending_text' => 'Agregá {{faltan}} más y obtené {{porcentaje}}% de descuento.', 'reward_quantity_unlocked_text' => '🎉 ¡Desbloqueaste {{porcentaje}}% de descuento!',
            'reward_cart_animation_enabled' => '1', 'reward_cart_sound_enabled' => '1', 'reward_checkout_celebration_enabled' => '1', 'reward_checkout_confetti_enabled' => '1', 'reward_microinteractions_enabled' => '1',
            'reward_klaus_enabled' => '1', 'reward_klaus_animations_enabled' => '1', 'reward_klaus_messages_enabled' => '1', 'reward_klaus_happy_text' => '🐾 ¡Klaus está contento!', 'reward_klaus_near_text' => '🐾 Falta poquito para tu recompensa.', 'reward_klaus_surprise_text' => '🎁 ¡Klaus encontró una sorpresa!', 'reward_klaus_complete_text' => '🎉 ¡Compra lista!',
            'pulga_enabled' => '1', 'pulga_frequency_seconds' => '45', 'pulga_animations_enabled' => '1',
            'whatsapp_message_order_created' => 'Hola {{cliente}}! Recibimos tu pedido {{pedido}} por {{total}}. Cuando realices la transferencia, por favor respondé a este chat para que podamos prepararlo. Gracias por elegirnos.',
            'whatsapp_message_cash_created' => 'Hola {{cliente}}! Recibimos tu pedido {{pedido}} por {{total}}. Lo reservamos por 6 horas para que puedas retirarlo y abonarlo en efectivo. Te esperamos!',
            'whatsapp_message_ready_pickup' => 'Hola {{cliente}}! Tu pedido {{pedido}} ya está listo para retirar. Gracias por elegirnos!',
            'whatsapp_message_cancelled' => 'Hola {{cliente}}! Cancelamos el pedido {{pedido}}. Si necesitás ayuda para armar uno nuevo, escribinos por acá y te ayudamos con gusto.',
        ];

        return $values;
    }

    public function claimDailySurprise(bool $enabled, int $probability): bool
    {
        if (!$enabled || $probability <= 0) return false;

        $today = date('Y-m-d');
        return Database::immediate($this->pdo, function (PDO $pdo) use ($today, $probability): bool {
            $lastShown = $pdo->prepare("SELECT value FROM settings WHERE key = 'reward_surprise_last_shown_on'");
            $lastShown->execute();
            if ((string) $lastShown->fetchColumn() === $today) return false;
            if (random_int(1, 100) > $probability) return false;

            $save = $pdo->prepare(
                "INSERT INTO settings(key, value, updated_at) VALUES('reward_surprise_last_shown_on', :today, CURRENT_TIMESTAMP)
                 ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP"
            );
            $save->execute([':today' => $today]);
            return true;
        });
    }

    /** @return array<string, string> */
    public function design(): array
    {
        $defaults = [
            'enabled' => '1',
            'hero_badge' => 'STOCK DISPONIBLE EN TIEMPO REAL',
            'hero_title' => 'TODO PARA CREAR, PERSONALIZAR Y VENDER',
            'hero_text' => 'Buscá lo que necesitás, elegí variantes y armá tu pedido en pocos pasos.',
            'hero_link' => '',
            'logo_path' => '/v1/assets/brand/logo-laboratorio-digital.png',
            'logo_link' => '',
            'hero_1_path' => '/v1/assets/brand/hero-1.webp',
            'hero_2_path' => '/v1/assets/brand/hero-2.webp',
            'hero_3_path' => '/v1/assets/brand/hero-3.webp',
            'section_order' => 'featured,gallery,categories,tutorials',
        ];
        $query = $this->pdo->query("SELECT key, value FROM settings WHERE key LIKE 'design_%'");
        foreach ($query->fetchAll() as $row) {
            $key = substr((string) $row['key'], 7);
            if (array_key_exists($key, $defaults)) $defaults[$key] = (string) $row['value'];
        }
        // Conserva la configuración de logos personalizados, pero reemplaza el
        // archivo genérico anterior por la nueva identidad visual del sitio.
        if ($defaults['logo_path'] === '/v1/assets/brand/logo.png') {
            $defaults['logo_path'] = '/v1/assets/brand/logo-laboratorio-digital.png';
        }
        foreach (['hero_link', 'logo_link'] as $key) {
            if ($this->isLegacyStoreLink((string) $defaults[$key])) {
                $defaults[$key] = '';
            }
        }
        return $defaults;
    }

    /** @return array<string, string> */
    public function quote(): array
    {
        $defaults = ['enabled' => '1'];
        $statement = $this->pdo->query("SELECT key, value FROM settings WHERE key LIKE 'quote_%'");
        foreach ($statement->fetchAll() as $row) {
            $key = substr((string) $row['key'], 6);
            if (array_key_exists($key, $defaults)) $defaults[$key] = (string) $row['value'];
        }
        return $defaults;
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function updateQuote(array $data): array
    {
        $values = [];
        $values['enabled'] = in_array((string) ($data['enabled'] ?? '0'), ['1', 'true', 'on'], true) ? '1' : '0';
        Database::immediate($this->pdo, static function (PDO $pdo) use ($values): void {
            $save = $pdo->prepare('INSERT INTO settings(key, value, updated_at) VALUES(:key, :value, CURRENT_TIMESTAMP) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP');
            foreach ($values as $key => $value) $save->execute(['key' => 'quote_' . $key, 'value' => $value]);
        });
        return $this->quote();
    }

    /** @return list<int> */
    public function featuredProductIds(): array
    {
        $query = $this->pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $query->execute(['key' => 'featured_product_ids']);
        $decoded = json_decode((string) ($query->fetchColumn() ?: '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach ($decoded as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false && !in_array((int) $id, $ids, true)) {
                $ids[] = (int) $id;
            }
        }
        return array_slice($ids, 0, 8);
    }

    /** @param list<mixed> $ids @return list<int> */
    public function updateFeaturedProductIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false && !in_array((int) $id, $normalized, true)) {
                $normalized[] = (int) $id;
            }
        }
        if (count($normalized) > 8) {
            throw new ValidationException('Podés destacar hasta 8 productos.');
        }
        Database::immediate($this->pdo, static function (PDO $pdo) use ($normalized): void {
            if ($normalized !== []) {
                $marks = implode(',', array_fill(0, count($normalized), '?'));
                $check = $pdo->prepare("SELECT id FROM products WHERE id IN ($marks) AND active = 1 AND deleted_at IS NULL");
                $check->execute($normalized);
                if (count($check->fetchAll()) !== count($normalized)) {
                    throw new ValidationException('Solo se pueden destacar productos visibles en la tienda.');
                }
            }
            $save = $pdo->prepare('INSERT INTO settings(key, value, updated_at) VALUES(:key, :value, CURRENT_TIMESTAMP) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP');
            $save->execute([
                'key' => 'featured_product_ids',
                'value' => json_encode($normalized, JSON_THROW_ON_ERROR),
            ]);
        });
        return $normalized;
    }

    /** @param array<string, mixed> $data */
    public function updateDesign(array $data): array
    {
        $current = $this->design();
        $values = [];
        foreach (['hero_badge' => 120, 'hero_title' => 160, 'hero_text' => 500] as $key => $limit) {
            $value = trim((string) ($data[$key] ?? $current[$key]));
            if ($value === '' || strlen($value) > $limit) throw new ValidationException('Revisá el texto de diseño: ' . $key . '.');
            $values[$key] = $value;
        }
        foreach (['hero_link', 'logo_link'] as $key) {
            $value = trim((string) ($data[$key] ?? $current[$key]));
            if ($value !== '' && !preg_match('#^(https?://|/)#i', $value)) throw new ValidationException('Los enlaces deben comenzar con https:// o /.');
            if ($this->isLegacyStoreLink($value)) {
                throw new ValidationException('Ese enlace corresponde a la tienda anterior. Usá un enlace propio o dejalo vacío.');
            }
            $values[$key] = $value;
        }
        foreach (['logo_path', 'hero_1_path', 'hero_2_path', 'hero_3_path'] as $key) {
            $image = trim((string) ($data[$key] ?? $current[$key]));
            if ($image === '' || !str_starts_with($image, '/')) throw new ValidationException('Elegí una imagen válida.');
            $values[$key] = $image;
        }
        $sectionOrder = array_values(array_filter(array_map('trim', explode(',', (string) ($data['section_order'] ?? $current['section_order'])))));
        $sections = ['featured', 'gallery', 'categories', 'tutorials'];
        if (count($sectionOrder) !== count($sections) || array_diff($sectionOrder, $sections) !== [] || array_diff($sections, $sectionOrder) !== []) {
            throw new ValidationException('Revisá el orden de las secciones de la portada.');
        }
        $values['section_order'] = implode(',', $sectionOrder);
        Database::immediate($this->pdo, static function (PDO $pdo) use ($values): void {
            $update = $pdo->prepare('INSERT INTO settings(key, value, updated_at) VALUES(:key, :value, CURRENT_TIMESTAMP) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP');
            foreach ($values as $key => $value) $update->execute(['key' => 'design_' . $key, 'value' => $value]);
        });
        return $this->design();
    }

    private function isLegacyStoreLink(string $value): bool
    {
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        return $host === 'temu.com'
            || str_ends_with($host, '.temu.com')
            || $host === 'mitiendanube.com'
            || str_ends_with($host, '.mitiendanube.com');
    }

    /** @param array<string, mixed> $data */
    public function update(array $data): array
    {
        $current = $this->values();
        $storeName = $this->text($data, 'store_name', 2, 100);
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
        $retryMinutes = isset($data['rejected_retry_minutes'])
            ? $this->integer($data, 'rejected_retry_minutes', 15, 10080)
            : (int) ($current['rejected_retry_minutes'] ?? 120);
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
        $cartMaintenanceEnabled = in_array(
            (string) ($data['cart_maintenance_enabled'] ?? $current['cart_maintenance_enabled'] ?? '0'),
            ['1', 'true', 'on'],
            true
        ) ? '1' : '0';
        $toggle = static fn (string $key) => in_array((string) ($data[$key] ?? $current[$key] ?? '0'), ['1', 'true', 'on'], true) ? '1' : '0';
        $integer = function (string $key, int $min, int $max) use ($data, $current): string {
            $value = filter_var($data[$key] ?? $current[$key] ?? null, FILTER_VALIDATE_INT);
            if ($value === false || $value < $min || $value > $max) throw new ValidationException('Revisá la configuración de recompensas.');
            return (string) $value;
        };
        $pulgaFrequency = $integer('pulga_frequency_seconds', 30, 45);
        $rewardTexts = [];
        foreach (['reward_surprise_text', 'reward_surprise_continue_text', 'reward_quantity_pending_text', 'reward_quantity_unlocked_text', 'reward_klaus_happy_text', 'reward_klaus_near_text', 'reward_klaus_surprise_text', 'reward_klaus_complete_text'] as $key) {
            $value = trim((string) ($data[$key] ?? $current[$key] ?? ''));
            if ($value === '' || strlen($value) > 400) throw new ValidationException('Revisá los textos de recompensas.');
            $rewardTexts[$key] = $value;
        }

        /*
        $mailReplyTo = trim((string) ($data['mail_reply_to'] ?? $current['mail_reply_to'] ?? $mailFrom));
        $mailUsername = trim((string) ($data['mail_smtp_username'] ?? $current['mail_smtp_username'] ?? $mailFrom));
        foreach ([$mailFrom, $mailReplyTo, $mailUsername] as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Revisá los datos de la casilla de e-mail.');
            }
        }
        $mailHost = trim((string) ($data['mail_smtp_host'] ?? $current['mail_smtp_host'] ?? ''));
        if ($mailHost === '' || strlen($mailHost) > 255 || preg_match('/\s/', $mailHost)) {
            throw new ValidationException('Ingresá un servidor SMTP válido.');
        }
        $mailPort = $this->integer($data + ['mail_smtp_port' => $current['mail_smtp_port'] ?? 465], 'mail_smtp_port', 1, 65535);
        $mailEncryption = strtolower(trim((string) ($data['mail_smtp_encryption'] ?? $current['mail_smtp_encryption'] ?? 'ssl')));
        if (!in_array($mailEncryption, ['ssl', 'tls', 'none'], true)) {
            throw new ValidationException('Elegí un cifrado SMTP válido.');
        }
        $mailMessages = [];
        foreach (['order_created', 'payment_reported', 'payment_approved', 'payment_rejected', 'order_ready', 'order_cancelled'] as $event) {
            $message = trim((string) ($data['mail_message_' . $event] ?? $current['mail_message_' . $event] ?? ''));
            if (strlen($message) > 3000) {
                throw new ValidationException('Un mensaje automático es demasiado largo.');
            }
            $mailMessages['mail_message_' . $event] = $message;
        }

        */
        $whatsappMessages = [];
        foreach (['order_created', 'cash_created', 'ready_pickup', 'cancelled'] as $event) {
            $message = trim((string) ($data['whatsapp_message_' . $event] ?? $current['whatsapp_message_' . $event] ?? ''));
            if ($message === '' || strlen($message) > 3000) {
                throw new ValidationException('Completá un mensaje de WhatsApp de hasta 3000 caracteres.');
            }
            $whatsappMessages['whatsapp_message_' . $event] = $message;
        }

        $values = [
            'store_name' => $storeName,
            'whatsapp_number' => (string) $whatsapp,
            'payment_window_minutes' => (string) $paymentMinutes,
            'rejected_retry_minutes' => (string) $retryMinutes,
            'proof_max_bytes' => (string) ($proofMaxMb * 1024 * 1024),
            'bank_holder' => $bankHolder,
            'bank_alias' => $bankAlias,
            'bank_cbu' => (string) $bankCbu,
            'pickup_address' => $pickupAddress,
            'business_hours' => $businessHours,
            'cart_maintenance_enabled' => $cartMaintenanceEnabled,
            'reward_surprise_enabled' => $toggle('reward_surprise_enabled'), 'reward_surprise_percent' => $integer('reward_surprise_percent', 1, 100), 'reward_surprise_probability' => $integer('reward_surprise_probability', 0, 100),
            'reward_quantity_enabled' => $toggle('reward_quantity_enabled'), 'reward_quantity_units' => $integer('reward_quantity_units', 1, 10000), 'reward_quantity_percent' => $integer('reward_quantity_percent', 1, 100),
            'reward_cart_animation_enabled' => $toggle('reward_cart_animation_enabled'), 'reward_cart_sound_enabled' => $toggle('reward_cart_sound_enabled'), 'reward_checkout_celebration_enabled' => $toggle('reward_checkout_celebration_enabled'), 'reward_checkout_confetti_enabled' => $toggle('reward_checkout_confetti_enabled'), 'reward_microinteractions_enabled' => $toggle('reward_microinteractions_enabled'),
            'reward_klaus_enabled' => $toggle('reward_klaus_enabled'), 'reward_klaus_animations_enabled' => $toggle('reward_klaus_animations_enabled'), 'reward_klaus_messages_enabled' => $toggle('reward_klaus_messages_enabled'),
            'pulga_enabled' => $toggle('pulga_enabled'), 'pulga_frequency_seconds' => $pulgaFrequency, 'pulga_animations_enabled' => $toggle('pulga_animations_enabled'),
            ...$rewardTexts,
            ...$whatsappMessages,
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

    /** Guarda Contacto sin obligar a reenviar la configuración operativa completa. */
    public function updateContact(array $data): array
    {
        $storeName = $this->text($data, 'store_name', 2, 100);
        $whatsapp = preg_replace('/\D+/', '', (string) ($data['whatsapp_number'] ?? ''));
        if (strlen((string) $whatsapp) < 10 || strlen((string) $whatsapp) > 20) {
            throw new ValidationException('Ingresá el WhatsApp con código de país y área.');
        }
        $pickupAddress = trim((string) ($data['pickup_address'] ?? ''));
        if (strlen($pickupAddress) > 250) throw new ValidationException('La dirección de retiro es demasiado larga.');
        $businessHours = trim((string) ($data['business_hours'] ?? ''));
        if (strlen($businessHours) < 3 || strlen($businessHours) > 300) throw new ValidationException('Revisá los horarios de atención.');
        $values = [
            'store_name' => $storeName,
            'whatsapp_number' => (string) $whatsapp,
            'pickup_address' => $pickupAddress,
            'business_hours' => $businessHours,
        ];
        Database::immediate($this->pdo, static function (PDO $pdo) use ($values): void {
            $save = $pdo->prepare('INSERT INTO settings(key, value, updated_at) VALUES(:key, :value, CURRENT_TIMESTAMP) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP');
            foreach ($values as $key => $value) $save->execute(['key' => $key, 'value' => $value]);
        });
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
            'Body - Mangas Largas' => [['1','20','30'],['2','21','34'],['3','23','35'],['4','25','38'],['5','26','30'],['6','27','42']],
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

    /** @param array<string, mixed> $data */
    private function mailFromName(array $data, string $fallback): string
    {
        $value = trim((string) ($data['mail_from_name'] ?? $fallback));
        if (strlen($value) < 2 || strlen($value) > 120) {
            throw new ValidationException('Revisá el nombre del remitente.');
        }

        return $value;
    }
}
