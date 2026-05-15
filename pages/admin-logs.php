<?php
$auth->requireAuth('admin');
$search = trim((string) ($_GET['search'] ?? ''));
$userId = (int) ($_GET['user_id'] ?? 0);
$actionFilter = trim((string) ($_GET['action'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$sortBy = trim((string) ($_GET['sort'] ?? 'created_at'));
$sortDir = trim((string) ($_GET['dir'] ?? 'desc'));

$logs = $adminService->listActivityLogs([
    'search' => $search,
    'user_id' => $userId,
    'action' => $actionFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sortBy,
    'dir' => $sortDir,
]);
$userOptions = $adminService->userOptions();
$actionOptions = $adminService->activityActionOptions();
$allowedSorts = ['action', 'user', 'created_at'];
$sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
$sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

$buildSortUrl = static function (string $column) use ($search, $userId, $actionFilter, $dateFrom, $dateTo, $sortBy, $sortDir): string {
    $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';
    $params = ['c' => 'admin-logs', 'sort' => $column, 'dir' => $nextDir];

    foreach ([
        'search' => $search,
        'user_id' => $userId,
        'action' => $actionFilter,
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
    header('Content-Disposition: attachment; filename="questra-admin-logs.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Action', 'User Name', 'User Email', 'Description', 'Created At']);

    foreach ($logs as $log) {
        fputcsv($output, [
            $log['action'],
            $log['full_name'],
            $log['email'],
            $log['description'],
            $log['created_at'],
        ]);
    }

    fclose($output);
    $currentUser = $auth->user();
    if ($currentUser !== null) {
        $auth->logActivity((int) $currentUser['id'], 'admin_logs_exported', 'Exported activity logs.');
    }
    exit;
}

$exportParams = ['c' => 'admin-logs', 'export' => 'csv'];
foreach ([
    'search' => $search,
    'user_id' => $userId,
    'action' => $actionFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sortBy,
    'dir' => $sortDir,
] as $key => $value) {
    if ($value !== '' && $value !== 0) {
        $exportParams[$key] = $value;
    }
}

$topbarTitle = 'Admin Logs';
$topbarCopy = 'System activity.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="scroll-text"></i> Activity Logs</span>
                        <h2 class="section-title !text-[2.4rem]">Audit Trail</h2>
                    </div>
                    <a href="<?= $app->e('?' . http_build_query($exportParams)) ?>" class="btn-primary" data-button-loading="Exporting..." data-loading-reset-ms="2200"><i data-lucide="download"></i><span>Export CSV</span></a>
                </div>

                <section class="table-card">
                    <form method="get" action="" class="toolbar mb-4">
                        <input type="hidden" name="c" value="admin-logs">
                        <div class="filter-row">
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="search"></i></span>
                                <input class="field-input field-input--icon" type="text" name="search" value="<?= $app->e($search) ?>" placeholder="Search logs">
                            </div>
                            <select class="field-select" name="user_id">
                                <option value="0">All users</option>
                                <?php foreach ($userOptions as $user) : ?>
                                    <option value="<?= $app->e((string) $user['id']) ?>" <?= $userId === (int) $user['id'] ? 'selected' : '' ?>><?= $app->e($user['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="action">
                                <option value="">All actions</option>
                                <?php foreach ($actionOptions as $action) : ?>
                                    <option value="<?= $app->e($action) ?>" <?= $actionFilter === $action ? 'selected' : '' ?>><?= $app->e($action) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="field-input" type="date" name="date_from" value="<?= $app->e($dateFrom) ?>">
                            <input class="field-input" type="date" name="date_to" value="<?= $app->e($dateTo) ?>">
                            <button class="btn-primary" type="submit"><i data-lucide="search"></i><span>Apply</span></button>
                            <a href="?c=admin-logs" class="btn-secondary"><i data-lucide="rotate-ccw"></i><span>Reset</span></a>
                        </div>
                    </form>

                    <?php if ($logs === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="scroll-text"></i></div>
                            <p class="mb-0 muted-copy">No logs found.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><a href="<?= $app->e($buildSortUrl('action')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Action</span><i data-lucide="<?= $app->e($sortIcon('action')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('user')) ?>" class="inline-flex items-center gap-2 no-underline"><span>User</span><i data-lucide="<?= $app->e($sortIcon('user')) ?>"></i></a></th>
                                        <th>Description</th>
                                        <th><a href="<?= $app->e($buildSortUrl('created_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Date</span><i data-lucide="<?= $app->e($sortIcon('created_at')) ?>"></i></a></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log) : ?>
                                        <tr>
                                            <td><span class="status-badge status-active"><?= $app->e($log['action']) ?></span></td>
                                            <td>
                                                <?php if ($log['full_name'] !== null && $log['full_name'] !== '') : ?>
                                                    <strong class="text-[var(--primary)]"><?= $app->e($log['full_name']) ?></strong>
                                                    <div class="list-meta"><?= $app->e($log['email'] ?? '') ?></div>
                                                <?php else : ?>
                                                    <span class="muted-copy">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $app->e($log['description']) ?></td>
                                            <td><?= $app->e($tools->formatTimestamp($log['created_at']) ?: '') ?></td>
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
