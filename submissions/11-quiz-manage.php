<?php
if (!$app->isPost()) {
    return;
}

if (isset($_POST['quiz_update_form'])) {
    $auth->requireAuth();
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);
    $app->storeOldInput($_POST, ['title', 'topic', 'difficulty', 'question_count', 'timer_per_question', 'passing_rate']);

    try {
        $quizService->updateQuiz($quizId, (int) $currentUser['id'], $_POST);
        $app->clearOldInput();
        $app->flash('success', 'Quiz updated.', 'Saved');
        $app->redirect('quiz-view', ['id' => $quizId]);
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Update failed');
        $app->redirect('quiz-edit', ['id' => $quizId]);
    }
}

if (isset($_POST['quiz_status_form'])) {
    $auth->requireAuth();
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    try {
        $quizService->updateQuizStatus($quizId, (int) $currentUser['id'], $status);
        $app->flash('success', 'Status updated.', 'Saved');
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Status update failed');
    }

    $redirectPage = trim((string) ($_POST['redirect_page'] ?? 'quiz-list'));
    $params = $redirectPage === 'quiz-view' ? ['id' => $quizId] : [];
    $app->redirect($redirectPage, $params);
}

if (isset($_POST['quiz_delete_form'])) {
    $auth->requireAuth();
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);

    try {
        $quizService->softDeleteQuiz($quizId, (int) $currentUser['id']);
        $app->flash('success', 'Quiz deleted.', 'Removed');
        $app->redirect('quiz-list');
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Delete failed');
        $app->redirect('quiz-view', ['id' => $quizId]);
    }
}
