<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;
use PDOException;

final class Auth
{
    private const PERSISTENT_COOKIE = 'ld_admin_session';
    private const PERSISTENT_LIFETIME = 60 * 60 * 24 * 400;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            $userId = $this->restorePersistentSession();
            if ($userId < 1) {
                return null;
            }
        }

        $query = $this->pdo->prepare(
            'SELECT id, name, email, role
             FROM users
             WHERE id = :id AND active = 1'
        );
        $query->execute(['id' => $userId]);
        $user = $query->fetch();

        if (!$user) {
            unset($_SESSION['user_id']);
            $this->forgetPersistentSession();
            return null;
        }

        if (empty($_COOKIE[self::PERSISTENT_COOKIE])) {
            $this->createPersistentSession((int) $user['id']);
        }

        return $user;
    }

    /** @return array<string, mixed> */
    public function requireUser(): array
    {
        $user = $this->user();
        if ($user === null) {
            throw new AuthorizationException('Necesitás iniciar sesión.');
        }

        return $user;
    }

    /** @return array<string, mixed> */
    public function requireAdmin(): array
    {
        $user = $this->requireUser();
        if ($user['role'] !== 'admin') {
            throw new AuthorizationException('Esta acción requiere un usuario administrador.');
        }

        return $user;
    }

    public function unlockStatistics(string $password): void
    {
        $user = $this->requireUser();
        $query = $this->pdo->prepare(
            'SELECT password_hash FROM users WHERE id = :id AND active = 1'
        );
        $query->execute(['id' => $user['id']]);
        $hash = $query->fetchColumn();
        if (!is_string($hash) || !password_verify($password, $hash)) {
            throw new AuthorizationException('La contraseña no es correcta.');
        }
        $_SESSION['statistics_access_user_id'] = (int) $user['id'];
    }

    public function consumeStatisticsAccess(): bool
    {
        $user = $this->requireUser();
        $granted = (int) ($_SESSION['statistics_access_user_id'] ?? 0) === (int) $user['id'];
        // La autorización se consume al entregar los importes: cada nuevo ingreso
        // a la sección vuelve a mostrar primero las métricas sin valores monetarios.
        unset($_SESSION['statistics_access_user_id']);
        return $granted;
    }

    /** @return array<string, mixed> */
    public function login(string $email, string $password): array
    {
        $this->checkLoginThrottle();

        $query = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = :email AND active = 1'
        );
        $query->execute(['email' => trim($email)]);
        $user = $query->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts'][] = time();
            throw new AuthorizationException('Email o contraseña incorrectos.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['login_attempts'] = [];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $this->forgetPersistentSession();
        $this->createPersistentSession((int) $user['id']);

        $update = $this->pdo->prepare(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $update->execute(['id' => $user['id']]);

        unset($user['password_hash']);

        return $user;
    }

    public function logout(): void
    {
        $this->forgetPersistentSession();
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    private function restorePersistentSession(): int
    {
        $token = (string) ($_COOKIE[self::PERSISTENT_COOKIE] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return 0;
        }

        $query = $this->pdo->prepare(
            'SELECT ps.id, ps.user_id
             FROM persistent_sessions ps
             JOIN users u ON u.id = ps.user_id
             WHERE ps.token_hash = :token_hash
               AND ps.expires_at > CURRENT_TIMESTAMP
               AND u.active = 1'
        );
        $query->execute(['token_hash' => hash('sha256', $token)]);
        $session = $query->fetch();
        if (!$session) {
            $this->forgetPersistentSession();
            return 0;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $session['user_id'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $this->pdo->prepare(
            'UPDATE persistent_sessions SET last_used_at = CURRENT_TIMESTAMP WHERE id = :id'
        )->execute(['id' => $session['id']]);

        return (int) $session['user_id'];
    }

    private function createPersistentSession(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $this->pdo->prepare(
            "INSERT INTO persistent_sessions(user_id, token_hash, expires_at)
             VALUES(:user_id, :token_hash, datetime('now', '+400 days'))"
        )->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
        ]);
        setcookie(self::PERSISTENT_COOKIE, $token, $this->persistentCookieOptions(time() + self::PERSISTENT_LIFETIME));
        $_COOKIE[self::PERSISTENT_COOKIE] = $token;
    }

    private function forgetPersistentSession(): void
    {
        $token = (string) ($_COOKIE[self::PERSISTENT_COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->pdo->prepare('DELETE FROM persistent_sessions WHERE token_hash = :token_hash')
                ->execute(['token_hash' => hash('sha256', $token)]);
        }
        setcookie(self::PERSISTENT_COOKIE, '', $this->persistentCookieOptions(time() - 3600));
        unset($_COOKIE[self::PERSISTENT_COOKIE]);
    }

    /** @return array<string, mixed> */
    private function persistentCookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            ),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    public function newOrderCount(int $userId): int
    {
        $query = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM orders o
             JOIN users u ON u.id = :user_id
             WHERE o.created_at > COALESCE(u.orders_seen_at, CURRENT_TIMESTAMP)
               AND o.archived_at IS NULL"
        );
        $query->execute(['user_id' => $userId]);
        return (int) $query->fetchColumn();
    }

    public function markOrdersSeen(int $userId): void
    {
        $update = $this->pdo->prepare(
            'UPDATE users SET orders_seen_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $update->execute(['id' => $userId]);
    }

    public function createInitialAdmin(
        string $setupToken,
        string $configuredToken,
        string $name,
        string $email,
        string $password
    ): void {
        $hasUsers = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
        if ($hasUsers) {
            throw new ConflictException('El administrador inicial ya fue creado.');
        }

        if (
            $configuredToken === ''
            || $setupToken === ''
            || !hash_equals($configuredToken, $setupToken)
        ) {
            throw new AuthorizationException('La clave de instalación no es válida.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Ingresá un email válido.');
        }

        $passwordLength = function_exists('mb_strlen')
            ? mb_strlen($password, 'UTF-8')
            : strlen($password);
        if ($passwordLength < 12) {
            throw new ValidationException('La contraseña debe tener al menos 12 caracteres.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO users(name, email, password_hash, role)
             VALUES(:name, :email, :password_hash, :role)'
        );
        $insert->execute([
            'name' => trim($name),
            'email' => trim($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function users(): array
    {
        return $this->pdo->query(
            'SELECT id, name, email, role, active, last_login_at, created_at
             FROM users
             ORDER BY active DESC, name, id'
        )->fetchAll();
    }

    public function createUser(
        string $name,
        string $email,
        string $password,
        string $role
    ): int {
        $payload = $this->validateUser($name, $email, $role);
        $this->validatePassword($password);

        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO users(name, email, password_hash, role)
                 VALUES(:name, :email, :password_hash, :role)'
            );
            $insert->execute([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $payload['role'],
            ]);
        } catch (PDOException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw new ConflictException('Ya existe un usuario con ese email.');
            }
            throw $exception;
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function updateUser(
        int $userId,
        string $name,
        string $email,
        string $role,
        bool $active,
        string $password,
        int $actorUserId
    ): void {
        $payload = $this->validateUser($name, $email, $role);
        $query = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $query->execute(['id' => $userId]);
        $existing = $query->fetch();
        if (!$existing) {
            throw new ValidationException('El usuario no existe.');
        }

        if ($userId === $actorUserId && (!$active || $payload['role'] !== 'admin')) {
            throw new ConflictException(
                'No podés desactivar ni quitarte tu propio acceso de administrador.'
            );
        }
        if (
            $existing['role'] === 'admin'
            && (int) $existing['active'] === 1
            && (!$active || $payload['role'] !== 'admin')
        ) {
            $activeAdmins = (int) $this->pdo->query(
                "SELECT COUNT(*) FROM users
                 WHERE role = 'admin' AND active = 1"
            )->fetchColumn();
            if ($activeAdmins <= 1) {
                throw new ConflictException(
                    'Debe quedar al menos un administrador activo.'
                );
            }
        }

        $parameters = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'active' => $active ? 1 : 0,
            'id' => $userId,
        ];
        $passwordSql = '';
        if ($password !== '') {
            $this->validatePassword($password);
            $passwordSql = ', password_hash = :password_hash';
            $parameters['password_hash'] = password_hash(
                $password,
                PASSWORD_DEFAULT
            );
        }

        try {
            $update = $this->pdo->prepare(
                'UPDATE users
                 SET name = :name,
                     email = :email,
                     role = :role,
                     active = :active,
                     updated_at = CURRENT_TIMESTAMP'
                . $passwordSql
                . ' WHERE id = :id'
            );
            $update->execute($parameters);
        } catch (PDOException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw new ConflictException('Ya existe un usuario con ese email.');
            }
            throw $exception;
        }
    }

    /** @return array{name: string, email: string, role: string} */
    private function validateUser(string $name, string $email, string $role): array
    {
        $name = trim($name);
        $email = trim($email);
        if ($name === '' || strlen($name) > 120) {
            throw new ValidationException('Ingresá un nombre válido.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Ingresá un email válido.');
        }
        if (!in_array($role, ['admin', 'seller'], true)) {
            throw new ValidationException('El rol del usuario no es válido.');
        }

        return ['name' => $name, 'email' => $email, 'role' => $role];
    }

    private function validatePassword(string $password): void
    {
        $passwordLength = function_exists('mb_strlen')
            ? mb_strlen($password, 'UTF-8')
            : strlen($password);
        if ($passwordLength < 12) {
            throw new ValidationException(
                'La contraseña debe tener al menos 12 caracteres.'
            );
        }
    }

    private function checkLoginThrottle(): void
    {
        $cutoff = time() - 15 * 60;
        $attempts = array_values(array_filter(
            $_SESSION['login_attempts'] ?? [],
            static fn (mixed $attempt): bool => is_int($attempt) && $attempt >= $cutoff
        ));
        $_SESSION['login_attempts'] = $attempts;

        if (count($attempts) >= 8) {
            throw new AuthorizationException(
                'Demasiados intentos. Esperá unos minutos y volvé a probar.'
            );
        }
    }
}
