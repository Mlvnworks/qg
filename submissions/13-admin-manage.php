<?php
if (!$app->isPost()) {
    return;
}

if (isset($_POST['admin_user_save_form'])) {
    $auth->requireAuth('admin');
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $app->storeOldInput($_POST, ['full_name', 'email', 'role', 'status']);

    try {
        if ($userId > 0) {
            $adminService->updateUser($userId, $_POST, (int) $currentUser['id']);
            $app->clearOldInput();
            $app->flash('success', 'User saved.', 'Updated');
            $app->redirect('admin-user-view', ['id' => $userId]);
        }

        $newUserId = $adminService->createUser($_POST, (int) $currentUser['id']);
        $app->clearOldInput();
        $app->flash('success', 'User created.', 'Saved');
        $app->redirect('admin-user-view', ['id' => $newUserId]);
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), $userId > 0 ? 'Save failed' : 'Create failed');
        $app->redirect('admin-user-edit', $userId > 0 ? ['id' => $userId] : []);
    }
}

if (isset($_POST['admin_user_status_form'])) {
    $auth->requireAuth('admin');
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    try {
        $adminService->updateUserStatus($userId, $status, (int) $currentUser['id']);
        $app->flash('success', 'Status updated.', 'Saved');
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Status update failed');
    }

    $redirectPage = trim((string) ($_POST['redirect_page'] ?? 'admin-users'));
    $params = $redirectPage === 'admin-user-view' ? ['id' => $userId] : [];
    $app->redirect($redirectPage, $params);
}

if (isset($_POST['admin_user_delete_form'])) {
    $auth->requireAuth('admin');
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $userId = (int) ($_POST['user_id'] ?? 0);

    try {
        $adminService->deleteUser($userId, (int) $currentUser['id']);
        $app->flash('success', 'User deleted.', 'Removed');
        $app->redirect('admin-users');
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Delete failed');
        $app->redirect('admin-user-view', ['id' => $userId]);
    }
}

if (isset($_POST['admin_quiz_update_form'])) {
    $auth->requireAuth('admin');
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);

    try {
        $adminService->updateQuiz($quizId, $_POST, (int) $currentUser['id']);
        $app->flash('success', 'Quiz saved.', 'Updated');
        $app->redirect('admin-quiz-view', ['id' => $quizId]);
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Save failed');
        $app->redirect('admin-quiz-edit', ['id' => $quizId]);
    }
}

if (isset($_POST['admin_quiz_status_form'])) {
    $auth->requireAuth('admin');
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    try {
        $adminService->updateQuizStatus($quizId, $status, (int) $currentUser['id']);
        $app->flash('success', 'Status updated.', 'Saved');
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Status update failed');
    }

    $redirectPage = trim((string) ($_POST['redirect_page'] ?? 'admin-quizzes'));
    $params = $redirectPage === 'admin-quiz-view' ? ['id' => $quizId] : [];
    $app->redirect($redirectPage, $params);
}

if (isset($_POST['admin_quiz_delete_form'])) {
    $auth->requireAuth('admin');
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);

    try {
        $adminService->deleteQuiz($quizId, (int) $currentUser['id']);
        $app->flash('success', 'Quiz deleted.', 'Removed');
        $app->redirect('admin-quizzes');
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Delete failed');
        $app->redirect('admin-quiz-view', ['id' => $quizId]);
    }
}
