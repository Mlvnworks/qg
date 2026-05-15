<?php

class Security
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . $this->app->e($this->csrfToken()) . '">';
    }

    public function validateCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    public function verifyCsrfOrFail(?string $token): void
    {
        if ($this->validateCsrf($token)) {
            return;
        }

        http_response_code(419);
        $this->app->flash('danger', 'Session expired. Try again.', 'Security check failed');
        $this->app->redirect('home');
    }

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function validEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validPassword(string $password): bool
    {
        return strlen($password) >= 8;
    }

    public function cleanTextarea(string $value): string
    {
        return trim(preg_replace("/\r\n|\r/", "\n", $value));
    }
}
