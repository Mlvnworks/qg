<?php
if (!$app->isPost() || !isset($_POST['quiz_create_form'])) {
    return;
}

$auth->requireAuth();
$security->verifyCsrfOrFail($_POST['csrf_token'] ?? null);
$app->storeOldInput($_POST, ['title', 'topic', 'difficulty', 'question_count', 'timer_per_question', 'passing_rate']);

try {
    $quizId = $quizService->createQuiz($_POST, $_FILES);
    $app->clearOldInput();
    $app->flash('success', 'Quiz created.', 'Saved');
    $app->redirect('quiz-view', ['id' => $quizId]);
} catch (Throwable $err) {
    $app->flash('danger', $err->getMessage(), 'Quiz creation failed');
    $app->redirect('quiz-create');
}
