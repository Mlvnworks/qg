<?php
$auth->requireAuth('admin');
$quizId = (int) ($_GET['id'] ?? 0);
$quiz = $adminService->getQuizById($quizId);

if ($quiz === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$topbarTitle = 'Edit Quiz';
$topbarCopy = 'Settings and questions.';
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
                        <a href="<?= $app->e($app->url('admin-quiz-view', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                    </div>

                    <form method="post" action="<?= $app->e($app->url('home')) ?>" class="page-stack mt-4">
                        <?= $security->csrfField() ?>
                        <input type="hidden" name="admin_quiz_update_form" value="1">
                        <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">

                        <div class="form-grid">
                            <div class="form-field">
                                <label class="field-label" for="title">Quiz title</label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="pen-square"></i></span>
                                    <input class="field-input field-input--icon" id="title" name="title" type="text" value="<?= $app->e($quiz['title']) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="topic">Topic</label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="brain"></i></span>
                                    <input class="field-input field-input--icon" id="topic" name="topic" type="text" value="<?= $app->e($quiz['topic']) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="difficulty">Difficulty</label>
                                <select class="field-select" id="difficulty" name="difficulty">
                                    <?php foreach (['Easy', 'Medium', 'Hard'] as $difficulty) : ?>
                                        <option value="<?= $app->e($difficulty) ?>" <?= $quiz['difficulty'] === $difficulty ? 'selected' : '' ?>><?= $app->e($difficulty) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="timer_per_question">Quiz timer</label>
                                <input class="field-input" id="timer_per_question" name="timer_per_question" type="number" min="10" max="7200" value="<?= $app->e((string) $quiz['timer_per_question']) ?>" required>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="passing_rate">Passing rate</label>
                                <input class="field-input" id="passing_rate" name="passing_rate" type="number" min="1" max="100" value="<?= $app->e((string) $quiz['passing_rate']) ?>" required>
                            </div>
                        </div>

                        <div class="page-stack">
                            <?php foreach ($quiz['questions'] as $index => $question) : ?>
                                <?php
                                $choices = $question['choices'];
                                while (count($choices) < 4) {
                                    $choices[] = ['choice_text' => '', 'is_correct' => 0];
                                }
                                $correctIndex = 0;
                                foreach ($choices as $choiceIndex => $choice) {
                                    if ((int) ($choice['is_correct'] ?? 0) === 1) {
                                        $correctIndex = $choiceIndex;
                                        break;
                                    }
                                }
                                ?>
                                <section class="question-card">
                                    <div class="question-card__header">
                                        <div>
                                            <span class="section-kicker"><i data-lucide="circle-help"></i> Question <?= $app->e((string) ($index + 1)) ?></span>
                                        </div>
                                        <span class="status-badge status-active"><?= $app->e($question['difficulty']) ?></span>
                                    </div>
                                    <input type="hidden" name="questions[<?= $app->e((string) $index) ?>][id]" value="<?= $app->e((string) $question['id']) ?>">
                                    <div class="form-grid mt-4">
                                        <div class="form-field form-field--full">
                                            <label class="field-label">Question</label>
                                            <textarea class="field-input field-textarea" name="questions[<?= $app->e((string) $index) ?>][question_text]" required><?= $app->e($question['question_text']) ?></textarea>
                                        </div>
                                        <div class="form-field form-field--full">
                                            <label class="field-label">Explanation</label>
                                            <textarea class="field-input field-textarea" name="questions[<?= $app->e((string) $index) ?>][explanation]"><?= $app->e($question['explanation']) ?></textarea>
                                        </div>
                                        <div class="form-field">
                                            <label class="field-label">Difficulty</label>
                                            <select class="field-select" name="questions[<?= $app->e((string) $index) ?>][difficulty]">
                                                <?php foreach (['Easy', 'Medium', 'Hard'] as $difficulty) : ?>
                                                    <option value="<?= $app->e($difficulty) ?>" <?= $question['difficulty'] === $difficulty ? 'selected' : '' ?>><?= $app->e($difficulty) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="cards-grid cards-grid--2 mt-4">
                                        <?php foreach ($choices as $choiceIndex => $choice) : ?>
                                            <div class="quiz-choice">
                                                <div class="flex items-center justify-between gap-3 mb-3">
                                                    <span class="choice-bullet"><?= $app->e(chr(65 + $choiceIndex)) ?></span>
                                                    <label class="inline-flex items-center gap-2 text-sm text-[var(--muted)]">
                                                        <input type="radio" name="questions[<?= $app->e((string) $index) ?>][correct_index]" value="<?= $app->e((string) $choiceIndex) ?>" <?= $correctIndex === $choiceIndex ? 'checked' : '' ?>>
                                                        <span>Correct</span>
                                                    </label>
                                                </div>
                                                <input class="field-input" type="text" name="questions[<?= $app->e((string) $index) ?>][choices][]" value="<?= $app->e($choice['choice_text']) ?>" required>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
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
