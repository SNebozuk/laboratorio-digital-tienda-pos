<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CashService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function open(int $openingCents, int $actorUserId): array
    {
        if ($openingCents < 0) {
            throw new ValidationException('El importe inicial no puede ser negativo.');
        }

        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($openingCents, $actorUserId): array {
                $current = $pdo->query(
                    "SELECT id FROM cash_sessions WHERE status = 'open' LIMIT 1"
                )->fetchColumn();
                if ($current) {
                    throw new ConflictException('Ya existe una caja abierta.');
                }

                $insert = $pdo->prepare(
                    'INSERT INTO cash_sessions(opened_by, opening_cents)
                     VALUES(:opened_by, :opening_cents)'
                );
                $insert->execute([
                    'opened_by' => $actorUserId,
                    'opening_cents' => $openingCents,
                ]);

                return $this->session((int) $pdo->lastInsertId(), $pdo);
            }
        );
    }

    /** @return array<string, mixed> */
    public function close(int $countedCents, int $actorUserId): array
    {
        if ($countedCents < 0) {
            throw new ValidationException('El importe contado no puede ser negativo.');
        }

        return Database::immediate(
            $this->pdo,
            function (PDO $pdo) use ($countedCents, $actorUserId): array {
                $sessionId = $pdo->query(
                    "SELECT id FROM cash_sessions
                     WHERE status = 'open'
                     ORDER BY id DESC LIMIT 1"
                )->fetchColumn();
                if (!$sessionId) {
                    throw new ConflictException('No hay una caja abierta.');
                }

                $expected = $this->expectedCash((int) $sessionId, $pdo);
                $update = $pdo->prepare(
                    'UPDATE cash_sessions
                     SET closed_by = :closed_by,
                         counted_closing_cents = :counted,
                         expected_closing_cents = :expected,
                         difference_cents = :difference,
                         status = :status,
                         closed_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND status = :open_status'
                );
                $update->execute([
                    'closed_by' => $actorUserId,
                    'counted' => $countedCents,
                    'expected' => $expected,
                    'difference' => $countedCents - $expected,
                    'status' => 'closed',
                    'id' => $sessionId,
                    'open_status' => 'open',
                ]);

                return $this->session((int) $sessionId, $pdo);
            }
        );
    }

    public function addMovement(
        string $type,
        int $amountCents,
        string $detail,
        int $actorUserId
    ): void {
        if (!in_array($type, ['income', 'expense'], true)) {
            throw new ValidationException('El movimiento de caja no es válido.');
        }
        if ($amountCents < 1) {
            throw new ValidationException('Ingresá un importe mayor a cero.');
        }

        Database::immediate(
            $this->pdo,
            function (PDO $pdo) use (
                $type,
                $amountCents,
                $detail,
                $actorUserId
            ): void {
                $sessionId = $pdo->query(
                    "SELECT id FROM cash_sessions
                     WHERE status = 'open'
                     ORDER BY id DESC LIMIT 1"
                )->fetchColumn();
                if (!$sessionId) {
                    throw new ConflictException('No hay una caja abierta.');
                }

                $insert = $pdo->prepare(
                    'INSERT INTO cash_movements(
                        cash_session_id, actor_user_id, type,
                        amount_cents, payment_method, detail
                     ) VALUES(
                        :cash_session_id, :actor_user_id, :type,
                        :amount_cents, :payment_method, :detail
                     )'
                );
                $insert->execute([
                    'cash_session_id' => $sessionId,
                    'actor_user_id' => $actorUserId,
                    'type' => $type,
                    'amount_cents' => $amountCents,
                    'payment_method' => 'cash',
                    'detail' => trim($detail),
                ]);
            }
        );
    }

    /** @return array<string, mixed>|null */
    public function current(): ?array
    {
        $sessionId = $this->pdo->query(
            "SELECT id FROM cash_sessions
             WHERE status = 'open'
             ORDER BY id DESC LIMIT 1"
        )->fetchColumn();

        return $sessionId ? $this->session((int) $sessionId, $this->pdo) : null;
    }

    /** @return array<string, mixed> */
    private function session(int $sessionId, PDO $pdo): array
    {
        $query = $pdo->prepare(
            'SELECT
                cs.*,
                opener.name AS opened_by_name,
                closer.name AS closed_by_name
             FROM cash_sessions cs
             JOIN users opener ON opener.id = cs.opened_by
             LEFT JOIN users closer ON closer.id = cs.closed_by
             WHERE cs.id = :id'
        );
        $query->execute(['id' => $sessionId]);
        $session = $query->fetch();
        if (!$session) {
            throw new ValidationException('La caja no existe.');
        }

        $session['expected_now_cents'] = $this->expectedCash($sessionId, $pdo);

        return $session;
    }

    private function expectedCash(int $sessionId, PDO $pdo): int
    {
        $opening = $pdo->prepare(
            'SELECT opening_cents FROM cash_sessions WHERE id = :id'
        );
        $opening->execute(['id' => $sessionId]);
        $openingCents = $opening->fetchColumn();
        if ($openingCents === false) {
            throw new ValidationException('La caja no existe.');
        }

        $movements = $pdo->prepare(
            "SELECT COALESCE(SUM(
                CASE
                    WHEN type IN ('sale', 'income') THEN amount_cents
                    WHEN type = 'expense' THEN -amount_cents
                    ELSE 0
                END
             ), 0)
             FROM cash_movements
             WHERE cash_session_id = :cash_session_id
               AND payment_method = 'cash'"
        );
        $movements->execute(['cash_session_id' => $sessionId]);

        return (int) $openingCents + (int) $movements->fetchColumn();
    }
}
