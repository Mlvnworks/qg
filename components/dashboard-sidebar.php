<?php
$currentUser = $auth->user();
$isAdminShell = $currentUser !== null && $currentUser['role'] === 'admin';
$currentPage = $normalizedContent ?? 'dashboard';
$sidebarLinks = $isAdminShell
    ? [
        ['page' => 'admin-dashboard', 'label' => 'Overview', 'icon' => 'layout-dashboard', 'meta' => 'Admin'],
        ['page' => 'admin-users', 'label' => 'Users', 'icon' => 'users-round', 'meta' => 'Accounts'],
        ['page' => 'admin-quizzes', 'label' => 'Quizzes', 'icon' => 'notebook-tabs', 'meta' => 'Library'],
        ['page' => 'admin-reports', 'label' => 'Attempts', 'icon' => 'file-bar-chart-2', 'meta' => 'Results'],
        ['page' => 'admin-logs', 'label' => 'Logs', 'icon' => 'scroll-text', 'meta' => 'Audit'],
    ]
    : [
        ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'meta' => 'Home'],
        ['page' => 'quiz-create', 'label' => 'Create Quiz', 'icon' => 'sparkles', 'meta' => 'New'],
        ['page' => 'quiz-list', 'label' => 'My Quizzes', 'icon' => 'notebook-tabs', 'meta' => 'List'],
        ['page' => 'reports', 'label' => 'Reports', 'icon' => 'file-bar-chart-2', 'meta' => 'Data'],
    ];
?>
<aside class="dashboard-sidebar" id="dashboard-sidebar">
    <a class="brand-mark" href="<?= $app->e($isAdminShell ? $app->url('admin-dashboard') : $app->url('dashboard')) ?>">
        <span class="brand-mark__icon"><i data-lucide="graduation-cap"></i></span>
        <span><?= $app->e(APP_NAME) ?></span>
    </a>

    <nav class="sidebar-nav">
        <?php foreach ($sidebarLinks as $link) : ?>
            <a class="sidebar-link <?= $currentPage === $link['page'] ? 'is-active' : '' ?>" href="<?= $app->e($app->url($link['page'])) ?>">
                <i data-lucide="<?= $app->e($link['icon']) ?>"></i>
                <span><?= $app->e($link['label']) ?></span>
                <span class="sidebar-link__meta"><?= $app->e($link['meta']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-note">
        <div class="flex items-center gap-3">
            <span class="avatar-dot"><?= $app->e(strtoupper(substr((string) $currentUser['full_name'], 0, 1))) ?></span>
            <div>
                <p class="mb-0 font-semibold text-white"><?= $app->e($currentUser['full_name']) ?></p>
                <p class="mb-0 text-sm text-white/70"><?= $app->e(ucfirst($currentUser['role'])) ?></p>
            </div>
        </div>
        <form method="post" action="<?= $app->e($app->url('home')) ?>" class="mt-4">
            <?= $security->csrfField() ?>
            <input type="hidden" name="logout_form" value="1">
            <button class="btn-secondary w-full !justify-start" type="submit" data-loading-text="Logging out...">
                <i data-lucide="log-out"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
