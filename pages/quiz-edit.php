<?php
$auth->requireAuth();
$currentUser = $auth->user();
$quizId = (int) ($_GET['id'] ?? 0);
$quiz = $quizService->getQuizById($quizId, (int) $currentUser['id']);

if ($quiz === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$topbarTitle = 'Edit Quiz';
$topbarCopy = 'Update settings.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <section class="surface-card">
                    <div class="toolbar">
                        <div>
                            <span class="section-kicker"><i data-lucide="square-pen"></i> Edit Quiz</span>
                            <h2 class="section-title !text-[2.4rem]"><?= $app->e($quiz['title']) ?></h2>
                        </div>
                        <a href="<?= $app->e($app->url('quiz-view', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                    </div>

                    <form method="post" action="<?= $app->e($app->url('home')) ?>" class="page-stack mt-4">
                        <?= $security->csrfField() ?>
                        <input type="hidden" name="quiz_update_form" value="1">
                        <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">

                        <div class="form-grid">
                            <div class="form-field">
                                <label class="field-label" for="title">Quiz title</label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="pen-square"></i></span>
                                    <input class="field-input field-input--icon" id="title" name="title" type="text" value="<?= $app->e($app->old('title', $quiz['title'])) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="topic">Topic</label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="brain"></i></span>
                                    <input class="field-input field-input--icon" id="topic" name="topic" type="text" value="<?= $app->e($app->old('topic', $quiz['topic'])) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="difficulty">Difficulty</label>
                                <select class="field-select" id="difficulty" name="difficulty">
                                    <?php foreach (['Easy', 'Medium', 'Hard'] as $difficulty) : ?>
                                        <?php $selectedDifficulty = $app->old('difficulty', $quiz['difficulty']); ?>
                                        <option value="<?= $app->e($difficulty) ?>" <?= $selectedDifficulty === $difficulty ? 'selected' : '' ?>><?= $app->e($difficulty) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="question_count">Questions</label>
                                <input class="field-input" id="question_count" name="question_count" type="number" min="1" max="20" value="<?= $app->e($app->old('question_count', (string) $quiz['question_count'])) ?>" required>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="timer_per_question">Quiz timer</label>
                                <input class="field-input" id="timer_per_question" name="timer_per_question" type="number" min="10" max="7200" value="<?= $app->e($app->old('timer_per_question', (string) $quiz['timer_per_question'])) ?>" required>
                                <span class="field-help">Total time in seconds.</span>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="passing_rate">Passing rate</label>
                                <input class="field-input" id="passing_rate" name="passing_rate" type="number" min="1" max="100" value="<?= $app->e($app->old('passing_rate', (string) $quiz['passing_rate'])) ?>" required>
                            </div>
                        </div>

                        <div class="toolbar">
                            <button class="btn-primary" type="submit" data-loading-text="Saving..."><i data-lucide="save"></i><span>Save Quiz</span></button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
