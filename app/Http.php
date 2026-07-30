<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class Http
{
    /** @param array<string, mixed> $payload */
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        exit;
    }

    /** @return array<string, mixed> */
    public static function input(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '{}', true);

            if (!is_array($decoded)) {
                throw new ValidationException('La solicitud no contiene JSON válido.');
            }

            return $decoded;
        }

        return $_POST;
    }

    /** @param array<string, mixed> $input */
    public static function requireCsrf(array $input): void
    {
        $provided = (string) ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expected = (string) ($_SESSION['csrf_token'] ?? '');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            throw new AuthorizationException('La sesión venció. Actualizá la página.');
        }
    }

    public static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}

class ValidationException extends \RuntimeException
{
}

class AuthorizationException extends \RuntimeException
{
}

class ConflictException extends \RuntimeException
{
}
