<?php
$auth->requireAuth('admin');
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$roleFilter = trim((string) ($_GET['role'] ?? ''));
$sortBy = trim((string) ($_GET['sort'] ?? 'created_at'));
$sortDir = trim((string) ($_GET['dir'] ?? 'desc'));
$users = $adminService->listUsers([
    'search' => $search,
    'status' => $statusFilter,
    'role' => $roleFilter,
    'sort' => $sortBy,
    'dir' => $sortDir,
]);
$allowedStatuses = ['active', 'inactive'];
$allowedRoles = ['admin', 'user'];
$allowedSorts = ['name', 'email', 'role', 'status', 'created_at', 'updated_at'];
$sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
$sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

$buildSortUrl = static function (string $column) use ($search, $statusFilter, $roleFilter, $sortBy, $sortDir): string {
    $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';
    $params = ['c' => 'admin-users', 'sort' => $column, 'dir' => $nextDir];

    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }
    if ($roleFilter !== '') {
        $params['role'] = $roleFilter;
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
    header('Content-Disposition: attachment; filename="questra-admin-users.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Full Name', 'Email', 'Role', 'Status', 'Quizzes Created', 'Attempts Taken', 'Created At', 'Updated At']);

    foreach ($users as $user) {
        fputcsv($output, [
            $user['full_name'],
            $user['email'],
            $user['role'],
            $user['status'],
            $user['quizzes_created'],
            $user['attempts_taken'],
            $user['created_at'],
            $user['updated_at'],
        ]);
    }

    fclose($output);
    exit;
}

$exportParams = ['c' => 'admin-users', 'export' => 'csv'];
if ($search !== '') {
    $exportParams['search'] = $search;
}
if ($statusFilter !== '') {
    $exportParams['status'] = $statusFilter;
}
if ($roleFilter !== '') {
    $exportParams['role'] = $roleFilter;
}
if ($sortBy !== '') {
    $exportParams['sort'] = $sortBy;
}
if ($sortDir !== '') {
    $exportParams['dir'] = $sortDir;
}

$topbarTitle = 'Admin Users';
$topbarCopy = 'Accounts and access.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="users-round"></i> Users</span>
                        <h2 class="section-title !text-[2.4rem]">Manage Accounts</h2>
                    </div>
                    <div class="filter-row">
                        <a href="<?= $app->e($app->url('admin-user-edit')) ?>" class="btn-primary"><i data-lucide="user-plus"></i><span>New User</span></a>
                        <a href="<?= $app->e('?' . http_build_query($exportParams)) ?>" class="btn-secondary" data-button-loading="Exporting..." data-loading-reset-ms="2200"><i data-lucide="download"></i><span>Export CSV</span></a>
                    </div>
                </div>

                <section class="table-card">
                    <form method="get" action="" class="toolbar mb-4">
                        <input type="hidden" name="c" value="admin-users">
                        <div class="filter-row">
                            <div class="field-wrap">
                                <span class="field-wrap__icon"><i data-lucide="search"></i></span>
                                <input class="field-input field-input--icon" type="text" name="search" value="<?= $app->e($search) ?>" placeholder="Search user">
                            </div>
                            <select class="field-select" name="status">
                                <option value="">All statuses</option>
                                <?php foreach ($allowedStatuses as $status) : ?>
                                    <option value="<?= $app->e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $app->e(ucfirst($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="field-select" name="role">
                                <option value="">All roles</option>
                                <?php foreach ($allowedRoles as $role) : ?>
                                    <option value="<?= $app->e($role) ?>" <?= $roleFilter === $role ? 'selected' : '' ?>><?= $app->e(ucfirst($role)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn-primary" type="submit"><i data-lucide="search"></i><span>Apply</span></button>
                            <a href="?c=admin-users" class="btn-secondary"><i data-lucide="rotate-ccw"></i><span>Reset</span></a>
                        </div>
                    </form>

                    <?php if ($users === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="users-round"></i></div>
                            <p class="mb-0 muted-copy">No users found.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><a href="<?= $app->e($buildSortUrl('name')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Name</span><i data-lucide="<?= $app->e($sortIcon('name')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('email')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Email</span><i data-lucide="<?= $app->e($sortIcon('email')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('role')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Role</span><i data-lucide="<?= $app->e($sortIcon('role')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('status')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Status</span><i data-lucide="<?= $app->e($sortIcon('status')) ?>"></i></a></th>
                                        <th>Quizzes</th>
                                        <th>Attempts</th>
                                        <th><a href="<?= $app->e($buildSortUrl('created_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Created</span><i data-lucide="<?= $app->e($sortIcon('created_at')) ?>"></i></a></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user) : ?>
                                        <tr>
                                            <td><?= $app->e($user['full_name']) ?></td>
                                            <td><?= $app->e($user['email']) ?></td>
                                            <td><span class="status-badge status-active"><?= $app->e(ucfirst($user['role'])) ?></span></td>
                                            <td><span class="status-badge status-<?= $app->e($user['status']) ?>"><?= $app->e(ucfirst($user['status'])) ?></span></td>
                                            <td><?= $app->e((string) $user['quizzes_created']) ?></td>
                                            <td><?= $app->e((string) $user['attempts_taken']) ?></td>
                                            <td><?= $app->e($tools->formatTimestamp($user['created_at']) ?: '') ?></td>
                                            <td>
                                                <div class="filter-row">
                                                    <a href="<?= $app->e($app->url('admin-user-view', ['id' => $user['id']])) ?>" class="btn-secondary"><i data-lucide="eye"></i><span>View</span></a>
                                                    <a href="<?= $app->e($app->url('admin-user-edit', ['id' => $user['id']])) ?>" class="btn-secondary"><i data-lucide="square-pen"></i><span>Edit</span></a>
                                                    <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                                        <?= $security->csrfField() ?>
                                                        <input type="hidden" name="admin_user_status_form" value="1">
                                                        <input type="hidden" name="user_id" value="<?= $app->e((string) $user['id']) ?>">
                                                        <input type="hidden" name="status" value="<?= $app->e($user['status'] === 'active' ? 'inactive' : 'active') ?>">
                                                        <input type="hidden" name="redirect_page" value="admin-users">
                                                        <button class="btn-ghost" type="submit" data-loading-text="<?= $user['status'] === 'active' ? 'Deactivating...' : 'Activating...' ?>" data-confirm-title="<?= $app->e($user['status'] === 'active' ? 'Deactivate account?' : 'Activate account?') ?>" data-confirm-message="<?= $app->e($user['full_name']) ?>" data-confirm-submit-label="<?= $app->e($user['status'] === 'active' ? 'Deactivate' : 'Activate') ?>">
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
