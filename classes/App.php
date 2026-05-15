<?php

class App
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function db(): ?PDO
    {
        return $this->pdo;
    }

    public function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function input(array $source, string $key, string $default = ''): string
    {
        return trim((string) ($source[$key] ?? $default));
    }

    public function url(string $page = 'home', array $params = []): string
    {
        $base = APP_URL . '/';
        $query = ['c' => $page];

        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }

        if ($page === 'home' && count($query) === 1) {
            return $base;
        }

        return $base . '?' . http_build_query($query);
    }

    public function redirect(string $page = 'home', array $params = []): void
    {
        header('Location: ' . $this->url($page, $params));
        exit;
    }

    public function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public function flash(string $type, string $message, string $title = 'Notice'): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ];
    }

    public function old(string $key, string $default = ''): string
    {
        return (string) ($_SESSION['old'][$key] ?? $default);
    }

    public function storeOldInput(array $data, array $allowedKeys): void
    {
        $_SESSION['old'] = [];

        foreach ($allowedKeys as $key) {
            $_SESSION['old'][$key] = trim((string) ($data[$key] ?? ''));
        }
    }

    public function clearOldInput(): void
    {
        unset($_SESSION['old']);
    }

    public function consumeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return is_array($flash) ? $flash : null;
    }

    public function ensureDatabase(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Database configuration is required for this feature.');
        }

        return $this->pdo;
    }

    public function generateShareCode(int $length = 18): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $value = '';

        for ($index = 0; $index < $length; $index++) {
            $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $value;
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
