<?php
$auth->requireAuth();
$currentUser = $auth->user();
$pdo = $app->ensureDatabase();
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$sortBy = trim((string) ($_GET['sort'] ?? 'started_at'));
$sortDir = trim((string) ($_GET['dir'] ?? 'desc'));
$allowedStatuses = ['Passed', 'Failed', 'ongoing', 'completed'];
$allowedSorts = [
    'title' => 'q.title',
    'score' => 'qa.score',
    'percentage' => 'qa.percentage',
    'status' => 'COALESCE(qa.result, qa.status)',
    'started_at' => 'qa.started_at',
    'completed_at' => 'qa.completed_at',
];
$sortBy = array_key_exists($sortBy, $allowedSorts) ? $sortBy : 'started_at';
$sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

$sql = '
    SELECT q.title, q.topic, qa.score, qa.total_questions, qa.percentage, qa.result, qa.status, qa.started_at, qa.completed_at
    FROM quiz_attempts qa
    INNER JOIN quizzes q ON q.id = qa.quiz_id
    WHERE qa.user_id = :user_id
';

$params = [
    'user_id' => (int) $currentUser['id'],
];

if ($search !== '') {
    $sql .= ' AND q.title LIKE :search ';
    $params['search'] = '%' . $search . '%';
}

if (in_array($statusFilter, $allowedStatuses, true)) {
    if ($statusFilter === 'Passed' || $statusFilter === 'Failed') {
        $sql .= ' AND qa.result = :result_filter ';
        $params['result_filter'] = $statusFilter;
    } else {
        $sql .= ' AND qa.status = :status_filter ';
        $params['status_filter'] = $statusFilter;
    }
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
    $sql .= ' AND DATE(qa.started_at) >= :date_from ';
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
    $sql .= ' AND DATE(qa.started_at) <= :date_to ';
    $params['date_to'] = $dateTo;
}

$sql .= ' ORDER BY ' . $allowedSorts[$sortBy] . ' ' . strtoupper($sortDir) . ', qa.id DESC LIMIT 25';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="questra-user-reports.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Quiz Title', 'Quiz Topic', 'Score', 'Total Questions', 'Percentage', 'Status', 'Started At', 'Completed At']);

    foreach ($reports as $report) {
        fputcsv($output, [
            $report['title'],
            $report['topic'],
            $report['score'],
            $report['total_questions'],
            $report['percentage'],
            $report['result'] ?? ucfirst((string) $report['status']),
            $report['started_at'],
            $report['completed_at'],
        ]);
    }

    fclose($output);
    exit;
}

$exportParams = ['c' => 'reports', 'export' => 'csv'];

if ($search !== '') {
    $exportParams['search'] = $search;
}

if ($statusFilter !== '') {
    $exportParams['status'] = $statusFilter;
}

if ($dateFrom !== '') {
    $exportParams['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $exportParams['date_to'] = $dateTo;
}

if ($sortBy !== '') {
    $exportParams['sort'] = $sortBy;
}

if ($sortDir !== '') {
    $exportParams['dir'] = $sortDir;
}

$exportUrl = '?' . http_build_query($exportParams);
$buildSortUrl = static function (string $column) use ($search, $statusFilter, $dateFrom, $dateTo, $sortBy, $sortDir): string {
    $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';
    $params = [
        'c' => 'reports',
        'sort' => $column,
        'dir' => $nextDir,
    ];

    if ($search !== '') {
        $params['search'] = $search;
    }

    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }

    if ($dateFrom !== '') {
        $params['date_from'] = $dateFrom;
    }

    if ($dateTo !== '') {
        $params['date_to'] = $dateTo;
    }

    return '?' . http_build_query($params);
};

$sortIcon = static function (string $column) use ($sortBy, $sortDir): string {
    if ($sortBy !== $column) {
        return 'arrow-up-down';
    }

    return $sortDir === 'asc' ? 'arrow-up' : 'arrow-down';
};
$topbarTitle = 'Reports';
$topbarCopy = 'Attempts and scores.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="file-bar-chart-2"></i> Reports</span>
                        <h2 class="section-title !text-[2.4rem]">Attempts</h2>
                    </div>
                    <a href="<?= $app->e($exportUrl) ?>" class="btn-primary" data-button-loading="Exporting..." data-loading-reset-ms="2200"><i data-lucide="download"></i><span>Export CSV</span></a>
                </div>

                <section class="table-card">
                    <form method="get" action="" class="toolbar mb-4">
                        <input type="hidden" name="c" value="reports">
                        <div class="filter-row">
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="search"></i></span>
                                <input class="field-input field-input--icon" type="text" name="search" value="<?= $app->e($search) ?>" placeholder="Filter quiz">
                            </div>
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="funnel"></i></span>
                                <select class="field-select field-input--icon" name="status">
                                    <option value="">All statuses</option>
                                    <?php foreach ($allowedStatuses as $status) : ?>
                                        <option value="<?= $app->e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $app->e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="calendar-range"></i></span>
                                <input class="field-input field-input--icon" type="date" name="date_from" value="<?= $app->e($dateFrom) ?>">
                            </div>
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="calendar-range"></i></span>
                                <input class="field-input field-input--icon" type="date" name="date_to" value="<?= $app->e($dateTo) ?>">
                            </div>
                            <button class="btn-primary" type="submit"><i data-lucide="search"></i><span>Apply</span></button>
                            <a href="?c=reports" class="btn-secondary"><i data-lucide="rotate-ccw"></i><span>Reset</span></a>
                        </div>
                    </form>
                    <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><a href="<?= $app->e($buildSortUrl('title')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Quiz</span><i data-lucide="<?= $app->e($sortIcon('title')) ?>"></i></a></th>
                                        <th>Topic</th>
                                        <th><a href="<?= $app->e($buildSortUrl('score')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Score</span><i data-lucide="<?= $app->e($sortIcon('score')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('percentage')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Percentage</span><i data-lucide="<?= $app->e($sortIcon('percentage')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('status')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Status</span><i data-lucide="<?= $app->e($sortIcon('status')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('started_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Started</span><i data-lucide="<?= $app->e($sortIcon('started_at')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('completed_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Completed</span><i data-lucide="<?= $app->e($sortIcon('completed_at')) ?>"></i></a></th>
                                    </tr>
                                </thead>
                            <tbody>
                                <?php foreach ($reports as $report) : ?>
                                    <tr>
                                        <td><?= $app->e($report['title']) ?></td>
                                        <td><?= $app->e($report['topic']) ?></td>
                                        <td><?= $app->e((string) $report['score']) ?>/<?= $app->e((string) $report['total_questions']) ?></td>
                                        <td><?= $app->e((string) $report['percentage']) ?>%</td>
                                        <td><span class="status-badge status-<?= $app->e(strtolower((string) ($report['result'] ?? $report['status']))) ?>"><?= $app->e($report['result'] ?? ucfirst($report['status'])) ?></span></td>
                                        <td><?= $app->e($tools->formatTimestamp($report['started_at']) ?: '') ?></td>
                                        <td><?= $app->e($tools->formatTimestamp($report['completed_at']) ?: 'Pending') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($reports === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="file-search"></i></div>
                            <p class="mb-0 muted-copy">No results.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</div>
