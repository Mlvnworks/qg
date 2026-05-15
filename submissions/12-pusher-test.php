<?php
if (!$app->isPost() || !isset($_POST['pusher_test_form'])) {
    return;
}

$security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
$auth->requireAuth();
$currentUser = $auth->user();
$quizId = (int) ($_POST['quiz_id'] ?? 0);
$redirectPage = preg_match('/^[a-z0-9\-]+$/i', (string) ($_POST['redirect_page'] ?? '')) === 1
    ? (string) $_POST['redirect_page']
    : 'dashboard';
$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

$sendJson = static function (int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

try {
    if ($currentUser === null) {
        throw new RuntimeException('Authentication required.');
    }

    if ($quizId <= 0) {
        throw new RuntimeException('Select a quiz before sending a test event.');
    }

    if (!$pusherService->isReady()) {
        throw new RuntimeException('Pusher is not ready. Recheck your app ID, key, secret, cluster, and Composer dependencies.');
    }

    $quiz = $quizService->getQuizById($quizId, (int) $currentUser['id']);

    if ($quiz === null) {
        throw new RuntimeException('Quiz not found.');
    }

    $pusherService->triggerQuizEvent($quizId, 'quiz.test.ping', [
        'actor_user_id' => (int) $currentUser['id'],
        'actor_name' => $currentUser['full_name'] ?? 'Quiz owner',
        'quiz_title' => $quiz['title'],
        'status' => 'live-test',
        'message' => 'Manual Pusher connection test',
    ]);

    if ($pusherService->getLastError() !== '') {
        throw new RuntimeException($pusherService->getLastError());
    }

    if ($isAjax) {
        $sendJson(200, [
            'ok' => true,
            'message' => 'Live test sent.',
            'quiz_id' => $quizId,
        ]);
    }

    $app->flash('success', 'Live test sent.', 'Realtime');
} catch (Throwable $err) {
    if ($isAjax) {
        $sendJson(422, [
            'ok' => false,
            'message' => $err->getMessage(),
        ]);
    }

    $app->flash('danger', $err->getMessage(), 'Realtime failed');
}

$params = [];

if ($redirectPage === 'quiz-reports') {
    $params['id'] = $quizId;
}

$app->redirect($redirectPage, $params);
