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

$topbarTitle = 'Quiz Details';
$topbarCopy = 'Questions, link, and stats.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="clipboard-pen-line"></i> Quiz Overview</span>
                        <h2 class="section-title !text-[2.4rem]"><?= $app->e($quiz['title']) ?></h2>
                        <p class="section-copy mt-3"><?= $app->e($quiz['topic']) ?> · <?= $app->e($quiz['difficulty']) ?> · <?= $app->e((string) $quiz['question_count']) ?> questions</p>
                        <div class="cards-grid cards-grid--2 mt-4">
                            <article class="timeline-card">
                                <p class="metric-label">Attempts</p>
                                <h3 class="metric-value !text-[2.2rem]"><?= $app->e((string) ($quiz['total_attempts'] ?? 0)) ?></h3>
                            </article>
                            <article class="timeline-card">
                                <p class="metric-label">Average Score</p>
                                <h3 class="metric-value !text-[2.2rem]"><?= $app->e(number_format((float) ($quiz['average_score'] ?? 0), 2)) ?>%</h3>
                            </article>
                        </div>
                    </section>

                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="link"></i> Sharing</span>
                        <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Share link</h3>
                        <p class="list-meta mt-3 break-all"><?= $app->e($quiz['share_url']) ?></p>
                        <div class="filter-row mt-4">
                            <a href="<?= $app->e($app->url('quiz-edit', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="square-pen"></i><span>Edit Quiz</span></a>
                            <a href="<?= $app->e($app->url('take-quiz', ['quiz' => $quiz['share_code']])) ?>" class="btn-primary"><i data-lucide="external-link"></i><span>Open</span></a>
                            <a href="<?= $app->e($app->url('quiz-reports', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="file-bar-chart-2"></i><span>Reports</span></a>
                        </div>
                        <div class="filter-row mt-3">
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="quiz_status_form" value="1">
                                <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                <input type="hidden" name="status" value="<?= $app->e($quiz['status'] === 'active' ? 'inactive' : 'active') ?>">
                                <input type="hidden" name="redirect_page" value="quiz-view">
                                <button class="btn-ghost" type="submit" data-loading-text="<?= $quiz['status'] === 'active' ? 'Deactivating...' : 'Activating...' ?>">
                                    <i data-lucide="<?= $quiz['status'] === 'active' ? 'toggle-left' : 'toggle-right' ?>"></i>
                                    <span><?= $quiz['status'] === 'active' ? 'Deactivate' : 'Activate' ?></span>
                                </button>
                            </form>
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="quiz_delete_form" value="1">
                                <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                <button class="btn-danger" type="submit" data-loading-text="Deleting..."><i data-lucide="trash-2"></i><span>Delete</span></button>
                            </form>
                        </div>
                    </section>
                </div>

                <?php foreach ($quiz['questions'] as $index => $question) : ?>
                    <section class="question-card">
                        <div class="question-card__header">
                            <div>
                                <span class="section-kicker"><i data-lucide="circle-help"></i> Question <?= $app->e((string) ($index + 1)) ?></span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]"><?= $app->e($question['question_text']) ?></h3>
                            </div>
                            <span class="status-badge status-active"><?= $app->e($question['difficulty']) ?></span>
                        </div>
                        <div class="choices-grid">
                            <?php foreach ($question['choices'] as $choiceIndex => $choice) : ?>
                                <div class="quiz-choice <?= (int) $choice['is_correct'] === 1 ? '!border-[rgba(22,163,74,0.24)] !bg-[rgba(22,163,74,0.05)]' : '' ?>">
                                    <span class="choice-bullet"><?= $app->e(chr(65 + $choiceIndex)) ?></span>
                                    <div>
                                        <div class="text-sm font-medium text-[var(--text)]"><?= $app->e($choice['choice_text']) ?></div>
                                        <?php if ((int) $choice['is_correct'] === 1) : ?>
                                            <div class="mt-2 text-sm text-[var(--success)]">Correct answer</div>
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
