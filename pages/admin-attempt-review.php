<?php
$auth->requireAuth('admin');
$attemptId = (int) ($_GET['id'] ?? 0);
$review = $adminService->getAttemptReview($attemptId);

if ($review === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$attempt = $review['attempt'];
$questions = $review['questions'];
$topbarTitle = 'Attempt Review';
$topbarCopy = 'Answers and results.';
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
                            <span class="section-kicker"><i data-lucide="scan-eye"></i> Attempt Review</span>
                            <h2 class="section-title !text-[2.4rem]"><?= $app->e($attempt['quiz_title']) ?></h2>
                            <p class="compact-copy mt-2"><?= $app->e($attempt['taker_name']) ?> · <?= $app->e($attempt['taker_email']) ?></p>
                        </div>
                        <div class="filter-row">
                            <span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span>
                            <a href="<?= $app->e($app->url('admin-reports', ['quiz_id' => $attempt['quiz_id']])) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                        </div>
                    </div>
                    <div class="cards-grid cards-grid--3 mt-4">
                        <article class="timeline-card">
                            <p class="metric-label">Score</p>
                            <h3 class="metric-value !text-[2.1rem]"><?= $app->e((string) $attempt['score']) ?>/<?= $app->e((string) $attempt['total_questions']) ?></h3>
                        </article>
                        <article class="timeline-card">
                            <p class="metric-label">Percentage</p>
                            <h3 class="metric-value !text-[2.1rem]"><?= $app->e((string) $attempt['percentage']) ?>%</h3>
                        </article>
                        <article class="timeline-card">
                            <p class="metric-label">Started</p>
                            <h3 class="metric-value !text-[1.4rem]"><?= $app->e($tools->formatTimestamp($attempt['started_at']) ?: '') ?></h3>
                        </article>
                    </div>
                </section>

                <?php foreach ($questions as $index => $question) : ?>
                    <section class="question-card">
                        <div class="question-card__header">
                            <div>
                                <span class="section-kicker"><i data-lucide="circle-help"></i> Question <?= $app->e((string) ($index + 1)) ?></span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]"><?= $app->e($question['question_text']) ?></h3>
                            </div>
                            <span class="status-badge status-<?= $app->e($question['was_correct'] ? 'passed' : 'failed') ?>"><?= $question['was_correct'] ? 'Correct' : 'Wrong' ?></span>
                        </div>
                        <div class="choices-grid">
                            <?php foreach ($question['choices'] as $choiceIndex => $choice) : ?>
                                <?php
                                $isSelected = (int) ($question['selected_choice_id'] ?? 0) === (int) $choice['id'];
                                $isCorrect = (int) $choice['is_correct'] === 1;
                                $extraClass = $isCorrect ? '!border-[rgba(22,163,74,0.24)] !bg-[rgba(22,163,74,0.05)]' : ($isSelected ? '!border-[rgba(220,38,38,0.24)] !bg-[rgba(220,38,38,0.05)]' : '');
                                ?>
                                <div class="quiz-choice <?= $extraClass ?>">
                                    <span class="choice-bullet"><?= $app->e(chr(65 + $choiceIndex)) ?></span>
                                    <div>
                                        <div class="text-sm font-medium text-[var(--text)]"><?= $app->e($choice['choice_text']) ?></div>
                                        <?php if ($isCorrect) : ?>
                                            <div class="mt-2 text-sm text-[var(--success)]">Correct answer</div>
                                        <?php elseif ($isSelected) : ?>
                                            <div class="mt-2 text-sm text-[var(--danger)]">Selected answer</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($question['explanation'] !== '') : ?>
                            <p class="panel-copy mt-4 mb-0"><?= $app->e($question['explanation']) ?></p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
