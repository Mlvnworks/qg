<?php
$auth->requireAuth('admin');
$userId = (int) ($_GET['id'] ?? 0);
$user = $adminService->getUserById($userId);

if ($user === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$createdQuizzes = $adminService->getUserCreatedQuizzes($userId);
$attempts = $adminService->getUserAttempts($userId);
$topbarTitle = 'User Details';
$topbarCopy = 'Profile and activity.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="user-round"></i> Account</span>
                        <h2 class="section-title !text-[2.4rem]"><?= $app->e($user['full_name']) ?></h2>
                        <p class="section-copy mt-3"><?= $app->e($user['email']) ?></p>
                        <div class="filter-row mt-4">
                            <span class="status-badge status-active"><?= $app->e(ucfirst($user['role'])) ?></span>
                            <span class="status-badge status-<?= $app->e($user['status']) ?>"><?= $app->e(ucfirst($user['status'])) ?></span>
                        </div>
                        <div class="cards-grid cards-grid--3 mt-4">
                            <article class="timeline-card">
                                <p class="metric-label">Quizzes</p>
                                <h3 class="metric-value !text-[2.1rem]"><?= $app->e((string) $user['quizzes_created']) ?></h3>
                            </article>
                            <article class="timeline-card">
                                <p class="metric-label">Attempts</p>
                                <h3 class="metric-value !text-[2.1rem]"><?= $app->e((string) $user['attempts_taken']) ?></h3>
                            </article>
                            <article class="timeline-card">
                                <p class="metric-label">Avg Score</p>
                                <h3 class="metric-value !text-[2.1rem]"><?= $app->e(number_format((float) $user['average_score'], 2)) ?>%</h3>
                            </article>
                        </div>
                        <div class="filter-row mt-4">
                            <a href="<?= $app->e($app->url('admin-user-edit', ['id' => $user['id']])) ?>" class="btn-secondary"><i data-lucide="square-pen"></i><span>Edit</span></a>
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="admin_user_status_form" value="1">
                                <input type="hidden" name="user_id" value="<?= $app->e((string) $user['id']) ?>">
                                <input type="hidden" name="status" value="<?= $app->e($user['status'] === 'active' ? 'inactive' : 'active') ?>">
                                <input type="hidden" name="redirect_page" value="admin-user-view">
                                <button class="btn-ghost" type="submit" data-loading-text="<?= $user['status'] === 'active' ? 'Deactivating...' : 'Activating...' ?>" data-confirm-title="<?= $app->e($user['status'] === 'active' ? 'Deactivate account?' : 'Activate account?') ?>" data-confirm-message="<?= $app->e($user['email']) ?>" data-confirm-submit-label="<?= $app->e($user['status'] === 'active' ? 'Deactivate' : 'Activate') ?>">
                                    <i data-lucide="<?= $user['status'] === 'active' ? 'toggle-left' : 'toggle-right' ?>"></i>
                                    <span><?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?></span>
                                </button>
                            </form>
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="admin_user_delete_form" value="1">
                                <input type="hidden" name="user_id" value="<?= $app->e((string) $user['id']) ?>">
                                <button class="btn-danger" type="submit" data-loading-text="Deleting..." data-confirm-title="Delete user?" data-confirm-message="<?= $app->e($user['email']) ?>" data-confirm-submit-label="Delete">
                                    <i data-lucide="trash-2"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </section>

                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="calendar-range"></i> Account Dates</span>
                        <div class="list-card">
                            <div>
                                <h4 class="list-title">Created</h4>
                                <p class="list-meta"><?= $app->e($tools->formatTimestamp($user['created_at']) ?: '') ?></p>
                            </div>
                        </div>
                        <div class="list-card">
                            <div>
                                <h4 class="list-title">Updated</h4>
                                <p class="list-meta"><?= $app->e($tools->formatTimestamp($user['updated_at']) ?: '') ?></p>
                            </div>
                        </div>
                        <div class="list-card">
                            <div>
                                <h4 class="list-title">Attempt Report</h4>
                                <p class="list-meta">Filtered attempt history.</p>
                            </div>
                            <a href="<?= $app->e($app->url('admin-reports', ['taker_id' => $user['id']])) ?>" class="btn-secondary"><i data-lucide="file-bar-chart-2"></i><span>View</span></a>
                        </div>
                    </section>
                </div>

                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="notebook-tabs"></i> Created Quizzes</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Owned</h3>
                            </div>
                        </div>
                        <?php if ($createdQuizzes === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="notebook-tabs"></i></div>
                                <p class="mb-0 muted-copy">No quizzes.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($createdQuizzes as $quiz) : ?>
                            <a class="list-card list-card--panel" href="<?= $app->e($app->url('admin-quiz-view', ['id' => $quiz['id']])) ?>">
                                <div>
                                    <h4 class="list-title"><?= $app->e($quiz['title']) ?></h4>
                                    <p class="list-meta"><?= $app->e($quiz['topic']) ?></p>
                                </div>
                                <span class="status-badge status-<?= $app->e($quiz['status']) ?>"><?= $app->e(ucfirst($quiz['status'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </section>

                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="clipboard-list"></i> Attempts</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Taken</h3>
                            </div>
                        </div>
                        <?php if ($attempts === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="clipboard-list"></i></div>
                                <p class="mb-0 muted-copy">No attempts.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($attempts as $attempt) : ?>
                            <a class="list-card list-card--panel" href="<?= $app->e($app->url('admin-attempt-review', ['id' => $attempt['id']])) ?>">
                                <div>
                                    <h4 class="list-title"><?= $app->e($attempt['title']) ?></h4>
                                    <p class="list-meta"><?= $app->e((string) $attempt['score']) ?>/<?= $app->e((string) $attempt['total_questions']) ?> · <?= $app->e((string) $attempt['percentage']) ?>%</p>
                                </div>
                                <span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
