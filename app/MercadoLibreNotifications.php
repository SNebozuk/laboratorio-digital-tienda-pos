<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

require_once __DIR__ . '/Database.php';

/** Durable, coalesced notifications; API resources are verified before applying stock. */
final class MercadoLibreNotifications
{
    public function __construct(private readonly PDO $pdo, private readonly string $storagePath) {}

    public function enqueue(array $event, bool $onlyIfMissing = false): void
    {
        $key = 'meli_notification_' . hash('sha256', $event['topic'] . ':' . $event['resource'] . ':' . $event['user_id']);
        Database::immediate($this->pdo, static function (PDO $pdo) use ($key, $event, $onlyIfMissing): void {
            $query = $pdo->prepare('SELECT value FROM settings WHERE key=?');
            $query->execute([$key]);
            $existing = $query->fetchColumn();
            if ($existing && $onlyIfMissing) return;
            if (!$existing && (int) $pdo->query("SELECT COUNT(*) FROM settings WHERE key GLOB 'meli_notification_*'")->fetchColumn() >= 500) {
                throw new \RuntimeException('La cola de notificaciones está ocupada.');
            }
            $record = ['event' => $event, 'revision' => bin2hex(random_bytes(12)), 'retry_at' => 0];
            $pdo->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=CURRENT_TIMESTAMP')
                ->execute([$key, json_encode($record, JSON_THROW_ON_ERROR)]);
        });
    }

    public function process(callable $handle, int $limit = 10): array
    {
        $lock = fopen($this->storagePath . '/meli-oauth.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            return ['processed' => 0, 'failed' => 0, 'busy' => true];
        }
        $result = ['processed' => 0, 'failed' => 0, 'busy' => false];
        try {
            $rows = $this->pdo->query("SELECT key,value FROM settings WHERE key GLOB 'meli_notification_*' ORDER BY updated_at,key")->fetchAll();
            foreach ($rows as $row) {
                if ($result['processed'] + $result['failed'] >= $limit) break;
                $record = json_decode($row['value'], true);
                if (!$record || ($record['retry_at'] ?? 0) > time()) continue;
                try {
                    $handle($record['event']);
                    // A notification arriving during processing must remain queued.
                    $this->pdo->prepare('DELETE FROM settings WHERE key=? AND value=?')->execute([$row['key'], $row['value']]);
                    $result['processed']++;
                } catch (\Throwable $error) {
                    $record['retry_at'] = time() + 60;
                    $record['last_error'] = mb_substr($error->getMessage(), 0, 300);
                    $this->pdo->prepare('UPDATE settings SET value=?,updated_at=CURRENT_TIMESTAMP WHERE key=? AND value=?')
                        ->execute([json_encode($record, JSON_THROW_ON_ERROR), $row['key'], $row['value']]);
                    $result['failed']++;
                }
            }
        } finally { flock($lock, LOCK_UN); fclose($lock); }
        return $result;
    }
}
