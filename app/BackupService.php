<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use DateTimeImmutable;
use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class BackupService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config,
        private readonly string $projectRoot
    ) {
    }

    /** @return array<string, mixed> */
    public function create(int $actorUserId, string $kind = 'manual'): array
    {
        return $this->withBackupLock(fn (): array => $this->createUnlocked($actorUserId, $kind));
    }

    /** @return array<string, mixed> */
    private function createUnlocked(int $actorUserId, string $kind): array
    {
        $root = $this->backupRoot();
        if (!is_dir($root) && !mkdir($root, 0770, true) && !is_dir($root)) {
            throw new \RuntimeException('No se pudo crear la carpeta de respaldos.');
        }

        $name = (new DateTimeImmutable())->format('Ymd-His')
            . '-'
            . bin2hex(random_bytes(3));
        $directory = $root . DIRECTORY_SEPARATOR . $name;
        if (!mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo iniciar el respaldo.');
        }

        try {
            $databasePath = $directory . DIRECTORY_SEPARATOR . 'app.sqlite';
            $escapedPath = str_replace("'", "''", $databasePath);
            $this->pdo->exec("VACUUM INTO '" . $escapedPath . "'");
            $this->verifyDatabase($databasePath);

            $proofStats = $this->copyProofs(
                $this->storageRoot() . DIRECTORY_SEPARATOR . 'proofs',
                $directory . DIRECTORY_SEPARATOR . 'proofs'
            );
            $productImageStats = $this->copyProofs(
                $this->projectRoot . DIRECTORY_SEPARATOR . 'v1' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products',
                $directory . DIRECTORY_SEPARATOR . 'product-images'
            );
            $createdAt = (new DateTimeImmutable())->format(DATE_ATOM);
            $manifest = [
                'name' => $name,
                'created_at' => $createdAt,
                'created_by_user_id' => $actorUserId,
                'kind' => $kind,
                'database_file' => 'app.sqlite',
                'database_bytes' => (int) filesize($databasePath),
                'database_sha256' => hash_file('sha256', $databasePath),
                'database_integrity' => 'ok',
                'proof_file_count' => $proofStats['file_count'],
                'proof_bytes' => $proofStats['bytes'],
                'product_image_file_count' => $productImageStats['file_count'],
                'product_image_bytes' => $productImageStats['bytes'],
            ];
            $encoded = json_encode(
                $manifest,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );
            if (file_put_contents(
                $directory . DIRECTORY_SEPARATOR . 'manifest.json',
                $encoded
            ) === false) {
                throw new \RuntimeException('No se pudo completar el manifiesto.');
            }

            return $manifest;
        } catch (Throwable $exception) {
            $this->removeDirectory($directory);
            throw $exception;
        }
    }

    /** Crea una copia diaria como máximo una vez por fecha y conserva las últimas 30 automáticas. */
    public function createDailyIfDue(): ?array
    {
        return $this->withBackupLock(function (): ?array {
            $today = (new DateTimeImmutable())->format('Y-m-d');
            foreach ($this->recent(60) as $backup) {
                if (($backup['kind'] ?? 'manual') === 'automatic' && str_starts_with((string) ($backup['created_at'] ?? ''), $today)) {
                    return null;
                }
            }
            $backup = $this->createUnlocked(0, 'automatic');
            $this->pruneAutomaticBackups(30);
            return $backup;
        });
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 20): array
    {
        $root = $this->backupRoot();
        if (!is_dir($root)) {
            return [];
        }

        $directories = glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        rsort($directories, SORT_STRING);
        $backups = [];

        foreach (array_slice($directories, 0, max(1, min(100, $limit))) as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!is_file($manifestPath)) {
                continue;
            }
            $decoded = json_decode(
                (string) file_get_contents($manifestPath),
                true
            );
            if (is_array($decoded)) {
                $backups[] = $decoded;
            }
        }

        return $backups;
    }

    /**
     * @return array{file_count: int, bytes: int}
     */
    private function copyProofs(string $source, string $destination): array
    {
        if (!is_dir($source)) {
            return ['file_count' => 0, 'bytes' => 0];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $fileCount = 0;
        $bytes = 0;

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);
            $target = $destination . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0770, true) && !is_dir($target)) {
                    throw new \RuntimeException(
                        'No se pudo copiar la estructura de comprobantes.'
                    );
                }
                continue;
            }
            if (!$item->isFile()) {
                continue;
            }

            $targetDirectory = dirname($target);
            if (
                !is_dir($targetDirectory)
                && !mkdir($targetDirectory, 0770, true)
                && !is_dir($targetDirectory)
            ) {
                throw new \RuntimeException(
                    'No se pudo crear una carpeta del respaldo.'
                );
            }
            if (!copy($item->getPathname(), $target)) {
                throw new \RuntimeException(
                    'No se pudo copiar un comprobante al respaldo.'
                );
            }
            $fileCount++;
            $bytes += (int) $item->getSize();
        }

        return ['file_count' => $fileCount, 'bytes' => $bytes];
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($directory);
    }

    private function pruneAutomaticBackups(int $keep): void
    {
        $automatic = array_values(array_filter($this->recent(200), static fn (array $backup): bool => ($backup['kind'] ?? 'manual') === 'automatic'));
        foreach (array_slice($automatic, max(0, $keep)) as $backup) {
            $name = basename((string) ($backup['name'] ?? ''));
            if ($name !== '' && preg_match('/^[A-Za-z0-9-]+$/', $name)) {
                $this->removeDirectory($this->backupRoot() . DIRECTORY_SEPARATOR . $name);
            }
        }
    }

    private function verifyDatabase(string $databasePath): void
    {
        $backup = new PDO('sqlite:' . $databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $integrity = (string) $backup->query('PRAGMA integrity_check')->fetchColumn();
        if ($integrity !== 'ok') {
            throw new \RuntimeException('La copia de la base no superó la verificación de integridad.');
        }
    }

    /** @template T @param callable(): T $operation @return T */
    private function withBackupLock(callable $operation): mixed
    {
        $root = $this->backupRoot();
        if (!is_dir($root) && !mkdir($root, 0770, true) && !is_dir($root)) {
            throw new \RuntimeException('No se pudo crear la carpeta de respaldos.');
        }
        $lock = fopen($root . DIRECTORY_SEPARATOR . '.backup.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('No se pudo bloquear la tarea de respaldo.');
        }
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('No se pudo bloquear la tarea de respaldo.');
            }
            return $operation();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function storageRoot(): string
    {
        return rtrim((string) $this->config['storage_path'], '/\\');
    }

    private function backupRoot(): string
    {
        return $this->storageRoot() . DIRECTORY_SEPARATOR . 'backups';
    }
}
