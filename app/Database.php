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
        self::migrateReceiptPrevalidation($pdo);
        self::migrateCategoryTree($pdo);
        self::seedCatalog(
            $pdo,
            dirname($schemaPath) . '/catalog_seed.sql'
        );
    }

    private static function migrateCategoryTree(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(categories)')->fetchAll() as $row) $columns[(string) $row['name']] = true;
        if (!isset($columns['parent_id'])) $pdo->exec('ALTER TABLE categories ADD COLUMN parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_categories_tree ON categories(parent_id, sort_order, name)');
        $pdo->exec('INSERT OR IGNORE INTO schema_migrations(version) VALUES(4)');
    }

    /** Agrega campos a bases ya creadas antes de la prevalidación de pagos. */
    private static function migrateReceiptPrevalidation(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(payment_proofs)')->fetchAll() as $row) {
            $columns[(string) $row['name']] = true;
        }
        $definitions = [
            'ai_status' => "TEXT NOT NULL DEFAULT 'not_run'",
            'ai_risk_level' => 'TEXT',
            'ai_summary' => 'TEXT',
            'ai_result_json' => 'TEXT',
            'ai_model' => 'TEXT',
            'ai_checked_at' => 'TEXT',
        ];
        foreach ($definitions as $name => $definition) {
            if (!isset($columns[$name])) {
                $pdo->exec(
                    'ALTER TABLE payment_proofs ADD COLUMN '
                    . $name . ' ' . $definition
                );
            }
        }

        $insertSetting = $pdo->prepare(
            'INSERT OR IGNORE INTO settings(key, value) VALUES(:key, :value)'
        );
        $insertSetting->execute([
            'key' => 'business_hours',
            'value' => 'Lunes a viernes de 9 a 17 h',
        ]);
        $pdo->exec(
            'INSERT OR IGNORE INTO schema_migrations(version) VALUES(3)'
        );
    }

    private static function seedCatalog(PDO $pdo, string $seedPath): void
    {
        $version = 2;
        $query = $pdo->prepare(
            'SELECT 1 FROM schema_migrations WHERE version = :version'
        );
        $query->execute(['version' => $version]);
        if ($query->fetchColumn() !== false) {
            return;
        }

        $seed = file_get_contents($seedPath);
        if ($seed === false) {
            throw new \RuntimeException('No se pudo leer el catálogo inicial.');
        }

        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $pdo->exec($seed);
            $insert = $pdo->prepare(
                'INSERT INTO schema_migrations(version) VALUES(:version)'
            );
            $insert->execute(['version' => $version]);
            $pdo->exec('COMMIT');
        } catch (Throwable $exception) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable) {
                // La transacción pudo fallar antes de iniciarse.
            }
            throw $exception;
        }
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
