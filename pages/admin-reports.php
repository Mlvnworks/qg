<?php
$auth->requireAuth('admin');
$search = trim((string) ($_GET['search'] ?? ''));
$quizId = (int) ($_GET['quiz_id'] ?? 0);
$creatorId = (int) ($_GET['creator_id'] ?? 0);
$takerId = (int) ($_GET['taker_id'] ?? 0);
$resultFilter = trim((string) ($_GET['result'] ?? ''));
$statusFilter = trim((string) ($_GET['attempt_status'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$sortBy = trim((string) ($_GET['sort'] ?? 'started_at'));
$sortDir = trim((string) ($_GET['dir'] ?? 'desc'));

$reports = $adminService->listAttempts([
    'search' => $search,
    'quiz_id' => $quizId,
    'creator_id' => $creatorId,
    'taker_id' => $takerId,
    'result' => $resultFilter,
    'attempt_status' => $statusFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sortBy,
    'dir' => $sortDir,
]);
$quizOptions = $adminService->quizOptions();
$userOptions = $adminService->userOptions();
$allowedResults = ['Passed', 'Failed'];
$allowedStatuses = ['ongoing', 'completed', 'unfinished'];
$allowedSorts = ['quiz', 'creator', 'taker', 'score', 'percentage', 'result', 'status', 'started_at', 'completed_at'];
$sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'started_at';
$sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

$buildSortUrl = static function (string $column) use ($search, $quizId, $creatorId, $takerId, $resultFilter, $statusFilter, $dateFrom, $dateTo, $sortBy, $sortDir): string {
    $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';
    $params = ['c' => 'admin-reports', 'sort' => $column, 'dir' => $nextDir];

    foreach ([
        'search' => $search,
        'quiz_id' => $quizId,
        'creator_id' => $creatorId,
        'taker_id' => $takerId,
        'result' => $resultFilter,
        'attempt_status' => $statusFilter,
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
    header('Content-Disposition: attachment; filename="questra-admin-attempts.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Quiz Title', 'Quiz Topic', 'Quiz Creator', 'Quiz Taker', 'Taker Email', 'Score', 'Total Questions', 'Percentage', 'Result', 'Attempt Status', 'Started At', 'Completed At']);

    foreach ($reports as $report) {
        fputcsv($output, [
            $report['quiz_title'],
            $report['topic'],
            $report['creator_name'],
            $report['taker_name'],
            $report['taker_email'],
            $report['score'],
            $report['total_questions'],
            $report['percentage'],
            $report['result'],
            $report['status'],
            $report['started_at'],
            $report['completed_at'],
        ]);
    }

    fclose($output);
    $currentUser = $auth->user();
    if ($currentUser !== null) {
        $auth->logActivity((int) $currentUser['id'], 'admin_report_exported', 'Exported admin attempt report.');
    }
    exit;
}

$exportParams = ['c' => 'admin-reports', 'export' => 'csv'];
foreach ([
    'search' => $search,
    'quiz_id' => $quizId,
    'creator_id' => $creatorId,
    'taker_id' => $takerId,
    'result' => $resultFilter,
    'attempt_status' => $statusFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sortBy,
    'dir' => $sortDir,
] as $key => $value) {
    if ($value !== '' && $value !== 0) {
        $exportParams[$key] = $value;
    }
}

$topbarTitle = 'Admin Attempts';
$topbarCopy = 'System-wide records.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="file-bar-chart-2"></i> Attempts</span>
                        <h2 class="section-title !text-[2.4rem]">Quiz Records</h2>
                    </div>
                    <a href="<?= $app->e('?' . http_build_query($exportParams)) ?>" class="btn-primary" data-button-loading="Exporting..." data-loading-reset-ms="2200"><i data-lucide="download"></i><span>Export CSV</span></a>
                </div>

                <section class="table-card">
                    <form method="get" action="" class="toolbar mb-4">
                        <input type="hidden" name="c" value="admin-reports">
                        <div class="filter-row">
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="search"></i></span>
                                <input class="field-input field-input--icon" type="text" name="search" value="<?= $app->e($search) ?>" placeholder="Search quiz or user">
                            </div>
                            <select class="field-select" name="quiz_id">
                                <option value="0">All quizzes</option>
                                <?php foreach ($quizOptions as $quizOption) : ?>
                                    <option value="<?= $app->e((string) $quizOption['id']) ?>" <?= $quizId === (int) $quizOption['id'] ? 'selected' : '' ?>><?= $app->e($quizOption['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="creator_id">
                                <option value="0">All creators</option>
                                <?php foreach ($userOptions as $user) : ?>
                                    <option value="<?= $app->e((string) $user['id']) ?>" <?= $creatorId === (int) $user['id'] ? 'selected' : '' ?>><?= $app->e($user['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="taker_id">
                                <option value="0">All takers</option>
                                <?php foreach ($userOptions as $user) : ?>
                                    <option value="<?= $app->e((string) $user['id']) ?>" <?= $takerId === (int) $user['id'] ? 'selected' : '' ?>><?= $app->e($user['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="result">
                                <option value="">All results</option>
                                <?php foreach ($allowedResults as $result) : ?>
                                    <option value="<?= $app->e($result) ?>" <?= $resultFilter === $result ? 'selected' : '' ?>><?= $app->e($result) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="attempt_status">
                                <option value="">All attempt status</option>
                                <?php foreach ($allowedStatuses as $status) : ?>
                                    <option value="<?= $app->e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $app->e(ucfirst($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="field-input" type="date" name="date_from" value="<?= $app->e($dateFrom) ?>">
                            <input class="field-input" type="date" name="date_to" value="<?= $app->e($dateTo) ?>">
                            <button class="btn-primary" type="submit"><i data-lucide="search"></i><span>Apply</span></button>
                            <a href="?c=admin-reports" class="btn-secondary"><i data-lucide="rotate-ccw"></i><span>Reset</span></a>
                        </div>
                    </form>

                    <?php if ($reports === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="file-search"></i></div>
                            <p class="mb-0 muted-copy">No attempts found.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><a href="<?= $app->e($buildSortUrl('quiz')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Quiz</span><i data-lucide="<?= $app->e($sortIcon('quiz')) ?>"></i></a></th>
                                        <th>Topic</th>
                                        <th><a href="<?= $app->e($buildSortUrl('creator')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Creator</span><i data-lucide="<?= $app->e($sortIcon('creator')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('taker')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Taker</span><i data-lucide="<?= $app->e($sortIcon('taker')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('score')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Score</span><i data-lucide="<?= $app->e($sortIcon('score')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('percentage')) ?>" class="inline-flex items-center gap-2 no-underline"><span>%</span><i data-lucide="<?= $app->e($sortIcon('percentage')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('result')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Result</span><i data-lucide="<?= $app->e($sortIcon('result')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('status')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Status</span><i data-lucide="<?= $app->e($sortIcon('status')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('started_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Started</span><i data-lucide="<?= $app->e($sortIcon('started_at')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('completed_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Completed</span><i data-lucide="<?= $app->e($sortIcon('completed_at')) ?>"></i></a></th>
                                        <th>Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report) : ?>
                                        <?php
                                        $resultBadgeClass = $report['result'] !== null && $report['result'] !== ''
                                            ? strtolower((string) $report['result'])
                                            : strtolower((string) $report['status']);
                                        $resultLabel = $report['result'] ?? 'Pending';
                                        ?>
                                        <tr>
                                            <td><?= $app->e($report['quiz_title']) ?></td>
                                            <td><?= $app->e($report['topic']) ?></td>
                                            <td><?= $app->e($report['creator_name']) ?></td>
                                            <td>
                                                <strong class="text-[var(--primary)]"><?= $app->e($report['taker_name']) ?></strong>
                                                <div class="list-meta"><?= $app->e($report['taker_email']) ?></div>
                                            </td>
                                            <td><?= $app->e((string) $report['score']) ?>/<?= $app->e((string) $report['total_questions']) ?></td>
                                            <td><?= $app->e((string) $report['percentage']) ?>%</td>
                                            <td><span class="status-badge status-<?= $app->e($resultBadgeClass) ?>"><?= $app->e($resultLabel) ?></span></td>
                                            <td><span class="status-badge status-<?= $app->e(strtolower((string) $report['status'])) ?>"><?= $app->e(ucfirst($report['status'])) ?></span></td>
                                            <td><?= $app->e($tools->formatTimestamp($report['started_at']) ?: '') ?></td>
                                            <td><?= $app->e($tools->formatTimestamp($report['completed_at']) ?: 'Pending') ?></td>
                                            <td><a href="<?= $app->e($app->url('admin-attempt-review', ['id' => $report['id']])) ?>" class="btn-secondary"><i data-lucide="scan-eye"></i><span>View</span></a></td>
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
