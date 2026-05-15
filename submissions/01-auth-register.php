<?php
if (!$app->isPost() || !isset($_POST['register_form'])) {
    return;
}

$security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);

$name = $app->input($_POST, 'full_name');
$email = $app->input($_POST, 'email');
$password = (string) ($_POST['password'] ?? '');
$passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

$app->storeOldInput($_POST, ['full_name', 'email']);

if ($name === '' || !$security->validEmail($email) || !$security->validPassword($password)) {
    $app->flash('danger', 'Enter a name, a valid email, and a password with at least 8 characters.', 'Sign up failed');
    $app->redirect('register');
}

if ($password !== $passwordConfirmation) {
    $app->flash('danger', 'Passwords do not match.', 'Sign up failed');
    $app->redirect('register');
}

try {
    $userId = $auth->register($name, $email, $password);
    $auth->logActivity($userId, 'register', 'New user account created.');
    $auth->login($email, $password);
    $app->clearOldInput();
    $app->flash('success', 'Account created.', 'Welcome');
    $app->redirect('dashboard');
} catch (Throwable $err) {
    $app->flash('danger', $err->getMessage(), 'Sign up failed');
    $app->redirect('register');
}
