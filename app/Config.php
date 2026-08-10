<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class Config
{
    /** @return array<string, mixed> */
    public static function load(string $projectRoot): array
    {
        $localFile = $projectRoot . '/config.local.php';
        $local = is_file($localFile) ? require $localFile : [];

        if (!is_array($local)) {
            throw new \RuntimeException('config.local.php debe devolver un array.');
        }

        $storagePath = self::string(
            $local,
            'storage_path',
            getenv('APP_STORAGE_PATH') ?: $projectRoot . '/storage'
        );

        return [
            'environment' => self::string(
                $local,
                'environment',
                getenv('APP_ENV') ?: 'production'
            ),
            'timezone' => self::string(
                $local,
                'timezone',
                getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires'
            ),
            'base_url' => rtrim(self::string(
                $local,
                'base_url',
                getenv('APP_BASE_URL') ?: ''
            ), '/'),
            'public_store_path' => self::string(
                $local,
                'public_store_path',
                getenv('APP_PUBLIC_STORE_PATH') ?: '/v1'
            ),
            'storage_path' => rtrim($storagePath, '/\\'),
            'database_path' => self::string(
                $local,
                'database_path',
                getenv('APP_DATABASE_PATH') ?: $storagePath . '/app.sqlite'
            ),
            'session_name' => self::string(
                $local,
                'session_name',
                getenv('APP_SESSION_NAME') ?: 'laboratorio_digital_session'
            ),
            'setup_token' => self::string(
                $local,
                'setup_token',
                getenv('APP_SETUP_TOKEN') ?: ''
            ),
            'maintenance_token_hash' => self::string(
                $local,
                'maintenance_token_hash',
                getenv('APP_MAINTENANCE_TOKEN_HASH')
                    ?: '709696c8371bc2917f1f700c8c3d3cce1033213e1eead6c999aa875137c6d064'
            ),
            'debug' => self::bool(
                $local,
                'debug',
                filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL)
            ),
            'orders_enabled' => self::bool(
                $local,
                'orders_enabled',
                filter_var(
                    getenv('APP_ORDERS_ENABLED') ?: false,
                    FILTER_VALIDATE_BOOL
                )
            ),
            'mail_enabled' => self::bool(
                $local,
                'mail_enabled',
                filter_var(getenv('APP_MAIL_ENABLED') ?: false, FILTER_VALIDATE_BOOL)
            ),
            'mail_transport' => self::string(
                $local,
                'mail_transport',
                getenv('APP_MAIL_TRANSPORT') ?: 'smtp'
            ),
            'mail_from' => self::string(
                $local,
                'mail_from',
                getenv('APP_MAIL_FROM') ?: 'ventas@laboratorio-digital.com.ar'
            ),
            'mail_from_name' => self::string(
                $local,
                'mail_from_name',
                getenv('APP_MAIL_FROM_NAME') ?: 'Laboratorio Digital'
            ),
            'mail_reply_to' => self::string(
                $local,
                'mail_reply_to',
                getenv('APP_MAIL_REPLY_TO') ?: 'ventas@laboratorio-digital.com.ar'
            ),
            'mail_smtp_host' => self::string($local, 'mail_smtp_host', getenv('APP_MAIL_SMTP_HOST') ?: ''),
            'mail_smtp_port' => self::integer($local, 'mail_smtp_port', (int) (getenv('APP_MAIL_SMTP_PORT') ?: 587)),
            'mail_smtp_encryption' => self::string($local, 'mail_smtp_encryption', getenv('APP_MAIL_SMTP_ENCRYPTION') ?: 'tls'),
            'mail_smtp_username' => self::string($local, 'mail_smtp_username', getenv('APP_MAIL_SMTP_USERNAME') ?: ''),
            'mail_smtp_password' => self::string($local, 'mail_smtp_password', getenv('APP_MAIL_SMTP_PASSWORD') ?: ''),
            'receipt_ai_enabled' => self::bool(
                $local,
                'receipt_ai_enabled',
                filter_var(
                    getenv('APP_RECEIPT_AI_ENABLED') ?: false,
                    FILTER_VALIDATE_BOOL
                )
            ),
            'openai_api_key' => self::string(
                $local,
                'openai_api_key',
                getenv('OPENAI_API_KEY') ?: ''
            ),
            'receipt_ai_model' => self::string(
                $local,
                'receipt_ai_model',
                getenv('APP_RECEIPT_AI_MODEL') ?: 'gpt-5.6-sol'
            ),
            'openai_base_url' => rtrim(self::string(
                $local,
                'openai_base_url',
                getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1'
            ), '/'),
        ];
    }

    /** @param array<string, mixed> $values */
    private static function string(array $values, string $key, string $default): string
    {
        $value = $values[$key] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** @param array<string, mixed> $values */
    private static function bool(array $values, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $values)) {
            return $default;
        }

        return filter_var($values[$key], FILTER_VALIDATE_BOOL);
    }

    /** @param array<string, mixed> $values */
    private static function integer(array $values, string $key, int $default): int
    {
        $value = $values[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }
}
