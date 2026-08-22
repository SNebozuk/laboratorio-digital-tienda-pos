<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class StoreVisitService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(string $visitorId): void
    {
        $day = (new DateTimeImmutable('now', new DateTimeZone('America/Argentina/Buenos_Aires')))
            ->format('Y-m-d');
        $insert = $this->pdo->prepare(
            'INSERT OR IGNORE INTO store_visits(visitor_hash, visit_day) VALUES(:visitor_hash, :visit_day)'
        );
        $insert->execute([
            'visitor_hash' => hash('sha256', $visitorId),
            'visit_day' => $day,
        ]);
    }
}
