<?php

class Auth
{
    private ?PDO $pdo;
    private App $app;
    private Security $security;

    public function __construct(?PDO $pdo, App $app, Security $security)
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->security = $security;
    }

    public function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('SELECT id, full_name, email, role, status FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            $this->clearSessionState();
            return null;
        }

        $_SESSION['user_snapshot'] = $user;

        return $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user !== null && $user['role'] === 'admin';
    }

    public function requireAuth(?string $role = null): void
    {
        $user = $this->user();

        if ($user === null) {
            $this->app->flash('warning', 'Sign in to continue.', 'Authentication required');
            $this->app->redirect('login');
        }

        if ($role !== null && $user['role'] !== $role) {
            $this->app->flash('danger', 'You do not have permission to access that page.', 'Access denied');
            $this->app->redirect('dashboard');
        }
    }

    public function login(string $email, string $password): bool
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $this->security->normalizeEmail($email)]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_snapshot'] = [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
        ];

        $this->logActivity((int) $user['id'], 'login', 'User signed in.');

        return true;
    }

    public function register(string $name, string $email, string $password): int
    {
        $pdo = $this->app->ensureDatabase();
        $email = $this->security->normalizeEmail($email);

        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            throw new RuntimeException('An account with that email already exists.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
            VALUES (:full_name, :email, :password_hash, :role, :status, NOW(), NOW())
        ');
        $stmt->execute([
            'full_name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'user',
            'status' => 'active',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function logout(): void
    {
        $user = isset($_SESSION['user_snapshot']) && is_array($_SESSION['user_snapshot'])
            ? $_SESSION['user_snapshot']
            : null;

        if ($user !== null) {
            $this->logActivity((int) $user['id'], 'logout', 'User signed out.');
        }

        $this->clearSessionState();
    }

    public function logActivity(?int $userId, string $action, string $description): void
    {
        if (!$this->pdo instanceof PDO) {
            return;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO activity_logs (user_id, action, description, created_at)
            VALUES (:user_id, :action, :description, NOW())
        ');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }

    private function clearSessionState(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
}
