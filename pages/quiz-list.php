<?php
$auth->requireAuth();
$currentUser = $auth->user();
$quizzes = $quizService->listOwnedQuizzes((int) $currentUser['id']);
$topbarTitle = 'Quiz Library';
$topbarCopy = 'Your quizzes.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="notebook-tabs"></i> My Quizzes</span>
                        <h2 class="section-title !text-[2.4rem]">Your generated quiz list</h2>
                    </div>
                    <a href="<?= $app->e($app->url('quiz-create')) ?>" class="btn-primary"><i data-lucide="plus"></i><span>Create Quiz</span></a>
                </div>

                <section class="table-card">
                    <div class="toolbar mb-4">
                        <div class="filter-row">
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="search"></i></span>
                                <input class="field-input field-input--icon" type="text" placeholder="Search quiz">
                            </div>
                            <select class="field-select">
                                <option>All statuses</option>
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <?php if ($quizzes === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="notebook-tabs"></i></div>
                            <p class="mb-0 muted-copy">No quizzes.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Difficulty</th>
                                        <th>Questions</th>
                                        <th>Attempts</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizzes as $quiz) : ?>
                                        <tr>
                                            <td>
                                                <strong class="text-[var(--primary)]"><?= $app->e($quiz['title']) ?></strong>
                                                <div class="list-meta flex items-center gap-2 flex-wrap">
                                                    <span>Share code: <?= $app->e($quiz['share_code']) ?></span>
                                                    <button
                                                        class="btn-ghost !px-3 !py-2"
                                                        type="button"
                                                        data-copy-text="<?= $app->e($quiz['share_code']) ?>"
                                                        data-copy-label="Copy"
                                                        data-copy-success="Copied"
                                                    >
                                                        <i data-lucide="copy"></i>
                                                        <span>Copy</span>
                                                    </button>
                                                </div>
                                            </td>
                                            <td><?= $app->e($quiz['difficulty']) ?></td>
                                            <td><?= $app->e((string) $quiz['question_count']) ?></td>
                                            <td><?= $app->e((string) ($quiz['total_attempts'] ?? 0)) ?></td>
                                            <td><span class="status-badge status-<?= $app->e($quiz['status']) ?>"><?= $app->e(ucfirst($quiz['status'])) ?></span></td>
                                            <td>
                                                <div class="filter-row">
                                                    <a href="<?= $app->e($app->url('quiz-view', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="eye"></i><span>View</span></a>
                                                    <a href="<?= $app->e($app->url('quiz-reports', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="file-bar-chart-2"></i><span>Reports</span></a>
                                                    <a href="<?= $app->e($app->url('quiz-edit', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="square-pen"></i><span>Edit</span></a>
                                                    <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                                        <?= $security->csrfField() ?>
                                                        <input type="hidden" name="quiz_status_form" value="1">
                                                        <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                                        <input type="hidden" name="status" value="<?= $app->e($quiz['status'] === 'active' ? 'inactive' : 'active') ?>">
                                                        <input type="hidden" name="redirect_page" value="quiz-list">
                                                        <button class="btn-ghost" type="submit" data-loading-text="<?= $quiz['status'] === 'active' ? 'Deactivating...' : 'Activating...' ?>">
                                                            <i data-lucide="<?= $quiz['status'] === 'active' ? 'toggle-left' : 'toggle-right' ?>"></i>
                                                            <span><?= $quiz['status'] === 'active' ? 'Deactivate' : 'Activate' ?></span>
                                                        </button>
                                                    </form>
                                                    <a href="<?= $app->e($app->url('take-quiz', ['quiz' => $quiz['share_code']])) ?>" class="btn-ghost" data-button-loading="Opening..."><i data-lucide="external-link"></i><span>Open</span></a>
                                                    <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                                        <?= $security->csrfField() ?>
                                                        <input type="hidden" name="quiz_delete_form" value="1">
                                                        <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                                        <button class="btn-danger" type="submit" data-loading-text="Deleting..."><i data-lucide="trash-2"></i><span>Delete</span></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</div>
