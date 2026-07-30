<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;
use PDOException;
use Throwable;

final class Database
{
    public static function connect(string $databasePath, string $schemaPath): PDO
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException(
                'El servidor necesita tener habilitada la extensión pdo_sqlite.'
            );
        }

        $directory = dirname($databasePath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo crear el almacenamiento privado.');
        }

        try {
            $pdo = new PDO('sqlite:' . $databasePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new \RuntimeException(
                'No se pudo abrir la base de datos.',
                0,
                $exception
            );
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');

        self::migrate($pdo, $schemaPath);

        return $pdo;
    }

    public static function migrate(PDO $pdo, string $schemaPath): void
    {
        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new \RuntimeException('No se pudo leer el esquema de base de datos.');
        }

        $pdo->exec($schema);
    }

    /**
     * Ejecuta una operación con bloqueo de escritura inmediato.
     *
     * SQLite no permite iniciar BEGIN IMMEDIATE mediante PDO::beginTransaction().
     * Esta variante evita que dos cajas o pedidos reserven la misma unidad.
     *
     * @template T
     * @param callable(PDO): T $operation
     * @return T
     */
    public static function immediate(PDO $pdo, callable $operation): mixed
    {
        $pdo->exec('BEGIN IMMEDIATE');

        try {
            $result = $operation($pdo);
            $pdo->exec('COMMIT');

            return $result;
        } catch (Throwable $exception) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable) {
                // La operación pudo fallar antes de abrir la transacción.
            }

            throw $exception;
        }
    }
}
