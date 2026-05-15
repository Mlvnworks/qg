<?php
$auth->requireAuth('admin');
$summary = $adminService->dashboardSummary();
$recentUsers = $adminService->recentUsers();
$recentQuizzes = $adminService->recentQuizzes();
$recentAttempts = $adminService->recentAttempts();
$recentLogs = $adminService->recentActivity();
$topbarTitle = 'Admin Dashboard';
$topbarCopy = 'Users, quizzes, attempts.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="shield"></i> Admin Dashboard</span>
                        <h2 class="section-title !text-[2.6rem]">System Overview</h2>
                    </div>
                    <div class="filter-row">
                        <a href="<?= $app->e($app->url('admin-users')) ?>" class="btn-secondary"><i data-lucide="users-round"></i><span>Users</span></a>
                        <a href="<?= $app->e($app->url('admin-quizzes')) ?>" class="btn-secondary"><i data-lucide="notebook-tabs"></i><span>Quizzes</span></a>
                        <a href="<?= $app->e($app->url('admin-reports')) ?>" class="btn-primary"><i data-lucide="file-bar-chart-2"></i><span>Attempts</span></a>
                    </div>
                </div>

                <div class="metric-grid">
                    <?php foreach ([
                        ['icon' => 'users-round', 'label' => 'Users', 'value' => $summary['total_users'], 'trend' => 'All'],
                        ['icon' => 'user-check', 'label' => 'Active Users', 'value' => $summary['active_users'], 'trend' => 'Live'],
                        ['icon' => 'user-x', 'label' => 'Inactive Users', 'value' => $summary['inactive_users'], 'trend' => 'Paused'],
                        ['icon' => 'notebook-tabs', 'label' => 'Quizzes', 'value' => $summary['total_quizzes'], 'trend' => 'All'],
                    ] as $card) : ?>
                        <article class="metric-card metric-card--quarter">
                            <span class="icon-badge"><i data-lucide="<?= $app->e($card['icon']) ?>"></i></span>
                            <p class="metric-label mt-4"><?= $app->e($card['label']) ?></p>
                            <h3 class="metric-value"><?= $app->e((string) $card['value']) ?></h3>
                            <span class="metric-trend"><i data-lucide="dot"></i><?= $app->e($card['trend']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="user-round-plus"></i> Recent Users</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Newest</h3>
                            </div>
                            <a href="<?= $app->e($app->url('admin-users')) ?>" class="btn-secondary">View all</a>
                        </div>
                        <?php if ($recentUsers === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="users-round"></i></div>
                                <p class="mb-0 muted-copy">No users.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recentUsers as $user) : ?>
                            <a class="list-card list-card--panel" href="<?= $app->e($app->url('admin-user-view', ['id' => $user['id']])) ?>">
                                <div>
                                    <h4 class="list-title"><?= $app->e($user['full_name']) ?></h4>
                                    <p class="list-meta"><?= $app->e($user['email']) ?></p>
                                </div>
                                <div class="filter-row">
                                    <span class="status-badge status-<?= $app->e($user['status']) ?>"><?= $app->e(ucfirst($user['status'])) ?></span>
                                    <span class="status-badge status-active"><?= $app->e(ucfirst($user['role'])) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </section>

                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="library"></i> Recent Quizzes</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Latest</h3>
                            </div>
                            <a href="<?= $app->e($app->url('admin-quizzes')) ?>" class="btn-secondary">View all</a>
                        </div>
                        <?php if ($recentQuizzes === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="notebook-tabs"></i></div>
                                <p class="mb-0 muted-copy">No quizzes.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recentQuizzes as $quiz) : ?>
                            <a class="list-card list-card--panel" href="<?= $app->e($app->url('admin-quiz-view', ['id' => $quiz['id']])) ?>">
                                <div>
                                    <h4 class="list-title"><?= $app->e($quiz['title']) ?></h4>
                                    <p class="list-meta"><?= $app->e($quiz['creator_name']) ?> · <?= $app->e($quiz['difficulty']) ?></p>
                                </div>
                                <span class="status-badge status-<?= $app->e($quiz['status']) ?>"><?= $app->e(ucfirst($quiz['status'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </section>
                </div>

                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="history"></i> Recent Attempts</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Latest</h3>
                            </div>
                            <a href="<?= $app->e($app->url('admin-reports')) ?>" class="btn-secondary">View all</a>
                        </div>
                        <?php if ($recentAttempts === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="clipboard-list"></i></div>
                                <p class="mb-0 muted-copy">No attempts.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recentAttempts as $attempt) : ?>
                            <a class="list-card list-card--panel" href="<?= $app->e($app->url('admin-attempt-review', ['id' => $attempt['id']])) ?>">
                                <div>
                                    <h4 class="list-title"><?= $app->e($attempt['quiz_title']) ?></h4>
                                    <p class="list-meta"><?= $app->e($attempt['taker_name']) ?> · <?= $app->e((string) $attempt['percentage']) ?>%</p>
                                </div>
                                <span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </section>

                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="scroll-text"></i> Activity</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">System log</h3>
                            </div>
                        </div>
                        <?php if ($recentLogs === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="scroll-text"></i></div>
                                <p class="mb-0 muted-copy">No logs.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recentLogs as $log) : ?>
                            <div class="list-card list-card--panel">
                                <div>
                                    <h4 class="list-title"><?= $app->e($log['action']) ?></h4>
                                    <p class="list-meta"><?= $app->e($log['description']) ?></p>
                                </div>
                                <span class="metric-label"><?= $app->e($tools->formatTimestamp($log['created_at']) ?: '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
