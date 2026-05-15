<?php
$auth->requireAuth();
$attemptId = (int) ($_GET['attempt'] ?? 0);
$currentUser = $auth->user();
$result = $attemptId > 0 ? $quizService->getAttemptResult($attemptId, (int) $currentUser['id']) : null;

if ($result === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$topbarTitle = 'Results';
$topbarCopy = 'Score and answers.';
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
                            <span class="section-kicker"><i data-lucide="badge-check"></i> Results</span>
                            <h2 class="section-title !text-[2.4rem]"><?= $app->e($result['title']) ?></h2>
                        </div>
                        <span class="status-badge status-<?= $app->e(strtolower((string) ($result['result'] ?? 'ongoing'))) ?>">
                            <?= $app->e($result['result'] ?? 'Pending') ?>
                        </span>
                    </div>
                    <div class="cards-grid cards-grid--3 mt-4">
                        <article class="timeline-card">
                            <p class="metric-label">Score</p>
                            <h3 class="metric-value !text-[2rem]"><?= $app->e((string) $result['score']) ?> / <?= $app->e((string) $result['total_questions']) ?></h3>
                        </article>
                        <article class="timeline-card">
                            <p class="metric-label">Percentage</p>
                            <h3 class="metric-value !text-[2rem]"><?= $app->e((string) $result['percentage']) ?>%</h3>
                        </article>
                        <article class="timeline-card">
                            <p class="metric-label">Passing Rate</p>
                            <h3 class="metric-value !text-[2rem]"><?= $app->e((string) $result['passing_rate']) ?>%</h3>
                        </article>
                    </div>
                </section>

                <?php foreach ($result['questions'] as $questionIndex => $question) : ?>
                    <section class="question-card">
                        <div class="question-card__header">
                            <div>
                                <p class="metric-label">Question <?= $app->e((string) ($questionIndex + 1)) ?></p>
                                <h3 class="mt-2 mb-0 text-xl font-bold text-[var(--primary)]"><?= $app->e($question['question_text']) ?></h3>
                            </div>
                            <span class="status-badge status-<?= $question['was_correct'] ? 'passed' : 'failed' ?>">
                                <?= $question['was_correct'] ? 'Correct' : 'Incorrect' ?>
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
                                        <div class="text-sm text-[var(--text)]"><?= $app->e($choice['choice_text']) ?></div>
                                        <?php if ($isCorrect) : ?>
                                            <div class="mt-2 text-sm text-[var(--success)]">Correct answer</div>
                                        <?php elseif ($isSelected) : ?>
                                            <div class="mt-2 text-sm text-[var(--danger)]">Your answer</div>
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
