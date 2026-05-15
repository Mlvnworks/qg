<?php
$auth->requireAuth();
$currentUser = $auth->user();
$quizId = (int) ($_GET['quiz_id'] ?? 0);
$attemptId = (int) ($_GET['attempt_id'] ?? 0);
$review = $quizService->getOwnerAttemptReview($quizId, $attemptId, (int) $currentUser['id']);

if ($review === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$quiz = $review['quiz'];
$attempt = $review['attempt'];
$questions = $review['questions'];
$topbarTitle = 'Attempt Review';
$topbarCopy = 'Answers and misses.';
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
                            <span class="section-kicker"><i data-lucide="scan-search"></i> Attempt Review</span>
                            <h2 class="section-title !text-[2.4rem]"><?= $app->e($quiz['title']) ?></h2>
                            <p class="compact-copy mt-2"><?= $app->e($attempt['taker_name']) ?> · <?= $app->e((string) $attempt['score']) ?>/<?= $app->e((string) $attempt['total_questions']) ?> · <?= $app->e((string) $attempt['percentage']) ?>%</p>
                        </div>
                        <div class="filter-row">
                            <span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span>
                            <a href="<?= $app->e($app->url('quiz-reports', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                        </div>
                    </div>
                </section>

                <?php foreach ($questions as $questionIndex => $question) : ?>
                    <section class="question-card">
                        <div class="question-card__header">
                            <div>
                                <span class="section-kicker"><i data-lucide="circle-help"></i> Question <?= $app->e((string) ($questionIndex + 1)) ?></span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]"><?= $app->e($question['question_text']) ?></h3>
                            </div>
                            <span class="status-badge status-<?= $question['was_correct'] ? 'passed' : 'failed' ?>">
                                <?= $question['was_correct'] ? 'Correct' : 'Wrong' ?>
                            </span>
                        </div>
                        <div class="choices-grid">
                            <?php foreach ($question['choices'] as $choiceIndex => $choice) : ?>
                                <?php
                                $isSelected = (int) ($question['selected_choice_id'] ?? 0) === (int) $choice['id'];
                                $isCorrect = (int) $choice['is_correct'] === 1;
                                $extraClass = $isCorrect
                                    ? '!border-[rgba(22,163,74,0.24)] !bg-[rgba(22,163,74,0.05)]'
                                    : ($isSelected ? '!border-[rgba(220,38,38,0.24)] !bg-[rgba(220,38,38,0.05)]' : '');
                                ?>
                                <div class="quiz-choice quiz-choice--large <?= $extraClass ?>">
                                    <span class="choice-bullet"><?= $app->e(chr(65 + $choiceIndex)) ?></span>
                                    <div>
                                        <div class="text-sm font-medium text-[var(--text)]"><?= $app->e($choice['choice_text']) ?></div>
                                        <?php if ($isCorrect) : ?>
                                            <div class="mt-2 text-sm text-[var(--success)]">Correct answer</div>
                                        <?php elseif ($isSelected) : ?>
                                            <div class="mt-2 text-sm text-[var(--danger)]">Selected by taker</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($question['explanation'] !== '') : ?>
                            <p class="compact-copy mt-4 mb-0"><?= $app->e($question['explanation']) ?></p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
