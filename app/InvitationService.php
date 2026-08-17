<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

/** Gestiona las solicitudes para que Sergio envíe invitaciones oficiales manualmente. */
final class InvitationService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{id:int,email:string,status:string,created_at:string} */
    public function request(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Escribí un email válido para solicitar la invitación.');
        }

        $find = $this->pdo->prepare('SELECT id, email, status, created_at FROM invitation_requests WHERE email = :email LIMIT 1');
        $find->execute(['email' => $email]);
        $existing = $find->fetch();
        if (is_array($existing)) {
            return $existing;
        }

        $insert = $this->pdo->prepare('INSERT INTO invitation_requests(email) VALUES(:email)');
        $insert->execute(['email' => $email]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'email' => $email,
            'status' => 'pending',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ];
    }

    /** @return array{requests:list<array<string,mixed>>,pending_count:int} */
    public function all(): array
    {
        $requests = $this->pdo->query('SELECT id, email, status, created_at, sent_at FROM invitation_requests ORDER BY CASE status WHEN \'pending\' THEN 0 ELSE 1 END, created_at DESC')->fetchAll();
        $pendingCount = (int) $this->pdo->query("SELECT COUNT(*) FROM invitation_requests WHERE status = 'pending'")->fetchColumn();
        return ['requests' => $requests, 'pending_count' => $pendingCount];
    }

    public function markSent(int $id, bool $sent): void
    {
        if ($id < 1) {
            throw new ValidationException('La solicitud de invitación no es válida.');
        }
        $statement = $this->pdo->prepare(
            'UPDATE invitation_requests
             SET status = :status,
                 sent_at = CASE WHEN :sent = 1 THEN CURRENT_TIMESTAMP ELSE NULL END,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'status' => $sent ? 'sent' : 'pending',
            'sent' => $sent ? 1 : 0,
        ]);
        if ($statement->rowCount() === 0) {
            throw new ValidationException('No encontramos esa solicitud de invitación.');
        }
    }
}
