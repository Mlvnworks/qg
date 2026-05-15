<?php
$auth->requireAuth('admin');
$quizId = (int) ($_GET['id'] ?? 0);
$quiz = $adminService->getQuizById($quizId);

if ($quiz === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$attempts = $adminService->getQuizAttempts($quizId);
$topbarTitle = 'Quiz Details';
$topbarCopy = 'Settings and attempts.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="clipboard-pen-line"></i> Quiz</span>
                        <h2 class="section-title !text-[2.4rem]"><?= $app->e($quiz['title']) ?></h2>
                        <p class="section-copy mt-3"><?= $app->e($quiz['topic']) ?></p>
                        <div class="filter-row mt-4">
                            <span class="status-badge status-active"><?= $app->e($quiz['difficulty']) ?></span>
                            <span class="status-badge status-<?= $app->e($quiz['status']) ?>"><?= $app->e(ucfirst($quiz['status'])) ?></span>
                        </div>
                        <div class="cards-grid cards-grid--3 mt-4">
                            <article class="timeline-card">
                                <p class="metric-label">Questions</p>
                                <h3 class="metric-value !text-[2.1rem]"><?= $app->e((string) $quiz['question_count']) ?></h3>
                            </article>
                            <article class="timeline-card">
                                <p class="metric-label">Attempts</p>
                                <h3 class="metric-value !text-[2.1rem]"><?= $app->e((string) $quiz['total_attempts']) ?></h3>
                            </article>
                            <article class="timeline-card">
                                <p class="metric-label">Avg Score</p>
                                <h3 class="metric-value !text-[2.1rem]"><?= $app->e(number_format((float) $quiz['average_score'], 2)) ?>%</h3>
                            </article>
                        </div>
                    </section>

                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="link"></i> Sharing</span>
                        <div class="list-card">
                            <div>
                                <h4 class="list-title">Creator</h4>
                                <p class="list-meta"><?= $app->e($quiz['creator_name']) ?> · <?= $app->e($quiz['creator_email']) ?></p>
                            </div>
                        </div>
                        <div class="list-card">
                            <div>
                                <h4 class="list-title">Share code</h4>
                                <p class="list-meta"><?= $app->e($quiz['share_code']) ?></p>
                            </div>
                            <button class="btn-secondary" type="button" data-copy-text="<?= $app->e($quiz['share_code']) ?>" data-copy-success="Copied"><i data-lucide="copy"></i><span>Copy</span></button>
                        </div>
                        <div class="list-card">
                            <div>
                                <h4 class="list-title">Share link</h4>
                                <p class="list-meta break-all"><?= $app->e($quiz['share_url']) ?></p>
                            </div>
                            <button class="btn-secondary" type="button" data-copy-text="<?= $app->e($quiz['share_url']) ?>" data-copy-success="Copied"><i data-lucide="copy"></i><span>Copy</span></button>
                        </div>
                        <div class="filter-row mt-4">
                            <a href="<?= $app->e($app->url('admin-quiz-edit', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="square-pen"></i><span>Edit</span></a>
                            <a href="<?= $app->e($app->url('admin-reports', ['quiz_id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="file-bar-chart-2"></i><span>Reports</span></a>
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="admin_quiz_status_form" value="1">
                                <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                <input type="hidden" name="status" value="<?= $app->e($quiz['status'] === 'active' ? 'inactive' : 'active') ?>">
                                <input type="hidden" name="redirect_page" value="admin-quiz-view">
                                <button class="btn-ghost" type="submit" data-loading-text="<?= $quiz['status'] === 'active' ? 'Deactivating...' : 'Activating...' ?>" data-confirm-title="<?= $app->e($quiz['status'] === 'active' ? 'Deactivate quiz?' : 'Activate quiz?') ?>" data-confirm-message="<?= $app->e($quiz['title']) ?>" data-confirm-submit-label="<?= $app->e($quiz['status'] === 'active' ? 'Deactivate' : 'Activate') ?>">
                                    <i data-lucide="<?= $quiz['status'] === 'active' ? 'toggle-left' : 'toggle-right' ?>"></i>
                                    <span><?= $quiz['status'] === 'active' ? 'Deactivate' : 'Activate' ?></span>
                                </button>
                            </form>
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="admin_quiz_delete_form" value="1">
                                <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                <button class="btn-danger" type="submit" data-loading-text="Deleting..." data-confirm-title="Delete quiz?" data-confirm-message="<?= $app->e($quiz['title']) ?>" data-confirm-submit-label="Delete">
                                    <i data-lucide="trash-2"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </section>
                </div>

                <section class="surface-card">
                    <div class="panel-header">
                        <div>
                            <span class="section-kicker"><i data-lucide="users-round"></i> Attempts</span>
                            <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Recent takers</h3>
                        </div>
                        <a href="<?= $app->e($app->url('admin-reports', ['quiz_id' => $quiz['id']])) ?>" class="btn-secondary">View all</a>
                    </div>
                    <?php if ($attempts === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="users-round"></i></div>
                            <p class="mb-0 muted-copy">No attempts.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll mt-4">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Taker</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Started</th>
                                        <th>Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attempts as $attempt) : ?>
                                        <tr>
                                            <td>
                                                <strong class="text-[var(--primary)]"><?= $app->e($attempt['taker_name']) ?></strong>
                                                <div class="list-meta"><?= $app->e($attempt['taker_email']) ?></div>
                                            </td>
                                            <td><?= $app->e((string) $attempt['score']) ?>/<?= $app->e((string) $attempt['total_questions']) ?></td>
                                            <td><?= $app->e((string) $attempt['percentage']) ?>%</td>
                                            <td><span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span></td>
                                            <td><?= $app->e($tools->formatTimestamp($attempt['started_at']) ?: '') ?></td>
                                            <td><a href="<?= $app->e($app->url('admin-attempt-review', ['id' => $attempt['id']])) ?>" class="btn-secondary"><i data-lucide="scan-eye"></i><span>View</span></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

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
                    </section>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
