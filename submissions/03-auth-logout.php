<?php
if (!$app->isPost() || !isset($_POST['logout_form'])) {
    return;
}

$security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
$auth->logout();
$app->flash('success', 'Signed out.', 'Session closed');
$app->redirect('home');
