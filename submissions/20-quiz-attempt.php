<?php
if (!$app->isPost()) {
    return;
}

if (isset($_POST['start_quiz_form'])) {
    $auth->requireAuth();
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $shareCode = trim((string) ($_POST['share_code'] ?? ''));

    try {
        $attempt = $quizService->startQuizAttempt($shareCode, (int) $currentUser['id']);
        $app->flash('success', 'Quiz started.', 'Started');
        $app->redirect('take-quiz', [
            'quiz' => $attempt['share_code'],
            'attempt' => $attempt['attempt_id'],
        ]);
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Could not start quiz');
        $app->redirect('take-quiz', ['quiz' => $shareCode]);
    }
}

if (isset($_POST['submit_quiz_form'])) {
    $auth->requireAuth();
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $attemptId = (int) ($_POST['attempt_id'] ?? 0);
    $shareCode = trim((string) ($_POST['share_code'] ?? ''));
    $answers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];

    try {
        $result = $quizService->submitQuizAttempt($attemptId, (int) $currentUser['id'], $answers);
        $app->flash('success', 'Quiz submitted.', 'Done');
        $app->redirect('results', [
            'attempt' => $result['attempt_id'],
            'quiz' => $result['share_code'],
        ]);
    } catch (Throwable $err) {
        $app->flash('danger', $err->getMessage(), 'Could not submit quiz');
        $app->redirect('take-quiz', [
            'quiz' => $shareCode,
            'attempt' => $attemptId,
        ]);
    }
}

if (isset($_POST['abandon_quiz_form'])) {
    $auth->requireAuth();
    $security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
    $currentUser = $auth->user();
    $attemptId = (int) ($_POST['attempt_id'] ?? 0);
    $answers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
    $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

    try {
        $quizService->markAttemptUnfinished($attemptId, (int) $currentUser['id'], $answers);

        if ($isAjax) {
            http_response_code(204);
            exit;
        }
    } catch (Throwable $err) {
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => $err->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
