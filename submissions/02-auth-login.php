<?php
if (!$app->isPost() || !isset($_POST['login_form'])) {
    return;
}

$security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);

$email = $app->input($_POST, 'email');
$password = (string) ($_POST['password'] ?? '');
$app->storeOldInput($_POST, ['email']);

if (!$security->validEmail($email) || $password === '') {
    $app->flash('danger', 'Invalid credentials.', 'Sign in failed');
    $app->redirect('login');
}

if (!$auth->login($email, $password)) {
    $app->flash('danger', 'Invalid credentials.', 'Sign in failed');
    $app->redirect('login');
}

$currentUser = $auth->user();
$redirectPage = $currentUser !== null && $currentUser['role'] === 'admin' ? 'admin-dashboard' : 'dashboard';

$app->clearOldInput();
$app->flash('success', 'Signed in.', 'Welcome back');
$app->redirect($redirectPage);
