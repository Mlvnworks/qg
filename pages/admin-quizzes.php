<?php
$auth->requireAuth('admin');
$search = trim((string) ($_GET['search'] ?? ''));
$creatorId = (int) ($_GET['creator_id'] ?? 0);
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$difficultyFilter = trim((string) ($_GET['difficulty'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$sortBy = trim((string) ($_GET['sort'] ?? 'created_at'));
$sortDir = trim((string) ($_GET['dir'] ?? 'desc'));
$quizzes = $adminService->listQuizzes([
    'search' => $search,
    'creator_id' => $creatorId,
    'status' => $statusFilter,
    'difficulty' => $difficultyFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sortBy,
    'dir' => $sortDir,
]);
$users = $adminService->userOptions();
$allowedStatuses = ['active', 'inactive', 'draft'];
$allowedDifficulties = ['Easy', 'Medium', 'Hard'];
$allowedSorts = ['title', 'creator', 'difficulty', 'status', 'created_at', 'updated_at'];
$sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
$sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

$buildSortUrl = static function (string $column) use ($search, $creatorId, $statusFilter, $difficultyFilter, $dateFrom, $dateTo, $sortBy, $sortDir): string {
    $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';
    $params = ['c' => 'admin-quizzes', 'sort' => $column, 'dir' => $nextDir];
    foreach ([
        'search' => $search,
        'creator_id' => $creatorId,
        'status' => $statusFilter,
        'difficulty' => $difficultyFilter,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ] as $key => $value) {
        if ($value !== '' && $value !== 0) {
            $params[$key] = $value;
        }
    }

    return '?' . http_build_query($params);
};

$sortIcon = static function (string $column) use ($sortBy, $sortDir): string {
    if ($sortBy !== $column) {
        return 'arrow-up-down';
    }

    return $sortDir === 'asc' ? 'arrow-up' : 'arrow-down';
};

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="questra-admin-quizzes.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Quiz Title', 'Topic', 'Creator', 'Difficulty', 'Questions', 'Quiz Timer', 'Passing Rate', 'Status', 'Share Code', 'Attempts', 'Created At', 'Updated At']);

    foreach ($quizzes as $quiz) {
        fputcsv($output, [
            $quiz['title'],
            $quiz['topic'],
            $quiz['creator_name'],
            $quiz['difficulty'],
            $quiz['question_count'],
            $quiz['timer_per_question'],
            $quiz['passing_rate'],
            $quiz['status'],
            $quiz['share_code'],
            $quiz['total_attempts'],
            $quiz['created_at'],
            $quiz['updated_at'],
        ]);
    }

    fclose($output);
    exit;
}

$exportParams = ['c' => 'admin-quizzes', 'export' => 'csv'];
foreach ([
    'search' => $search,
    'creator_id' => $creatorId,
    'status' => $statusFilter,
    'difficulty' => $difficultyFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sortBy,
    'dir' => $sortDir,
] as $key => $value) {
    if ($value !== '' && $value !== 0) {
        $exportParams[$key] = $value;
    }
}

$topbarTitle = 'Admin Quizzes';
$topbarCopy = 'All quizzes.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="notebook-tabs"></i> Quizzes</span>
                        <h2 class="section-title !text-[2.4rem]">Manage Quizzes</h2>
                    </div>
                    <div class="filter-row">
                        <a href="<?= $app->e($app->url('quiz-create')) ?>" class="btn-primary"><i data-lucide="sparkles"></i><span>Create Quiz</span></a>
                        <a href="<?= $app->e('?' . http_build_query($exportParams)) ?>" class="btn-secondary" data-button-loading="Exporting..." data-loading-reset-ms="2200"><i data-lucide="download"></i><span>Export CSV</span></a>
                    </div>
                </div>

                <section class="table-card">
                    <form method="get" action="" class="toolbar mb-4">
                        <input type="hidden" name="c" value="admin-quizzes">
                        <div class="filter-row">
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="search"></i></span>
                                <input class="field-input field-input--icon" type="text" name="search" value="<?= $app->e($search) ?>" placeholder="Search quiz">
                            </div>
                            <select class="field-select" name="creator_id">
                                <option value="0">All creators</option>
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?= $app->e((string) $user['id']) ?>" <?= $creatorId === (int) $user['id'] ? 'selected' : '' ?>><?= $app->e($user['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="status">
                                <option value="">All statuses</option>
                                <?php foreach ($allowedStatuses as $status) : ?>
                                    <option value="<?= $app->e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $app->e(ucfirst($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="difficulty">
                                <option value="">All difficulty</option>
                                <?php foreach ($allowedDifficulties as $difficulty) : ?>
                                    <option value="<?= $app->e($difficulty) ?>" <?= $difficultyFilter === $difficulty ? 'selected' : '' ?>><?= $app->e($difficulty) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="field-input" type="date" name="date_from" value="<?= $app->e($dateFrom) ?>">
                            <input class="field-input" type="date" name="date_to" value="<?= $app->e($dateTo) ?>">
                            <button class="btn-primary" type="submit"><i data-lucide="search"></i><span>Apply</span></button>
                            <a href="?c=admin-quizzes" class="btn-secondary"><i data-lucide="rotate-ccw"></i><span>Reset</span></a>
                        </div>
                    </form>

                    <?php if ($quizzes === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="notebook-tabs"></i></div>
                            <p class="mb-0 muted-copy">No quizzes found.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><a href="<?= $app->e($buildSortUrl('title')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Quiz</span><i data-lucide="<?= $app->e($sortIcon('title')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('creator')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Creator</span><i data-lucide="<?= $app->e($sortIcon('creator')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('difficulty')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Difficulty</span><i data-lucide="<?= $app->e($sortIcon('difficulty')) ?>"></i></a></th>
                                        <th>Questions</th>
                                        <th>Attempts</th>
                                        <th><a href="<?= $app->e($buildSortUrl('status')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Status</span><i data-lucide="<?= $app->e($sortIcon('status')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('created_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Created</span><i data-lucide="<?= $app->e($sortIcon('created_at')) ?>"></i></a></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizzes as $quiz) : ?>
                                        <tr>
                                            <td>
                                                <strong class="text-[var(--primary)]"><?= $app->e($quiz['title']) ?></strong>
                                                <div class="list-meta"><?= $app->e($quiz['topic']) ?></div>
                                            </td>
                                            <td><?= $app->e($quiz['creator_name']) ?></td>
                                            <td><?= $app->e($quiz['difficulty']) ?></td>
                                            <td><?= $app->e((string) $quiz['question_count']) ?></td>
                                            <td><?= $app->e((string) $quiz['total_attempts']) ?></td>
                                            <td><span class="status-badge status-<?= $app->e($quiz['status']) ?>"><?= $app->e(ucfirst($quiz['status'])) ?></span></td>
                                            <td><?= $app->e($tools->formatTimestamp($quiz['created_at']) ?: '') ?></td>
                                            <td>
                                                <div class="filter-row">
                                                    <a href="<?= $app->e($app->url('admin-quiz-view', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="eye"></i><span>View</span></a>
                                                    <a href="<?= $app->e($app->url('admin-quiz-edit', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="square-pen"></i><span>Edit</span></a>
                                                    <a href="<?= $app->e($app->url('admin-reports', ['quiz_id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="file-bar-chart-2"></i><span>Reports</span></a>
                                                    <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                                        <?= $security->csrfField() ?>
                                                        <input type="hidden" name="admin_quiz_status_form" value="1">
                                                        <input type="hidden" name="quiz_id" value="<?= $app->e((string) $quiz['id']) ?>">
                                                        <input type="hidden" name="status" value="<?= $app->e($quiz['status'] === 'active' ? 'inactive' : 'active') ?>">
                                                        <input type="hidden" name="redirect_page" value="admin-quizzes">
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
