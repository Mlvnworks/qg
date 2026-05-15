<?php
$auth->requireAuth('admin');
$userId = (int) ($_GET['id'] ?? 0);
$isEdit = $userId > 0;
$user = $isEdit ? $adminService->getUserById($userId) : null;

if ($isEdit && $user === null) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$topbarTitle = $isEdit ? 'Edit User' : 'New User';
$topbarCopy = $isEdit ? 'Update account details.' : 'Create an account.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <section class="surface-card">
                    <div class="toolbar">
                        <div>
                            <span class="section-kicker"><i data-lucide="<?= $isEdit ? 'square-pen' : 'user-plus' ?>"></i> <?= $isEdit ? 'Edit User' : 'Create User' ?></span>
                            <h2 class="section-title !text-[2.4rem]"><?= $app->e($isEdit ? $user['full_name'] : 'New account') ?></h2>
                        </div>
                        <a href="<?= $app->e($isEdit ? $app->url('admin-user-view', ['id' => $userId]) : $app->url('admin-users')) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                    </div>

                    <form method="post" action="<?= $app->e($app->url('home')) ?>" class="page-stack mt-4">
                        <?= $security->csrfField() ?>
                        <input type="hidden" name="admin_user_save_form" value="1">
                        <input type="hidden" name="user_id" value="<?= $app->e((string) $userId) ?>">

                        <div class="form-grid">
                            <div class="form-field">
                                <label class="field-label" for="full_name">Full name</label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="user-round"></i></span>
                                    <input class="field-input field-input--icon" id="full_name" name="full_name" type="text" value="<?= $app->e($app->old('full_name', $user['full_name'] ?? '')) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="email">Email</label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="mail"></i></span>
                                    <input class="field-input field-input--icon" id="email" name="email" type="email" value="<?= $app->e($app->old('email', $user['email'] ?? '')) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="password"><?= $isEdit ? 'New password' : 'Password' ?></label>
                                <div class="field-wrap">
                                    <span class="field-wrap__icon"><i data-lucide="lock"></i></span>
                                    <input class="field-input field-input--icon" id="password" name="password" type="password" <?= $isEdit ? '' : 'required' ?>>
                                </div>
                                <span class="field-help"><?= $isEdit ? 'Leave blank to keep the current password.' : 'Minimum 8 characters.' ?></span>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="role">Role</label>
                                <select class="field-select" id="role" name="role">
                                    <?php foreach (['admin', 'user'] as $role) : ?>
                                        <?php $selectedRole = $app->old('role', $user['role'] ?? 'user'); ?>
                                        <option value="<?= $app->e($role) ?>" <?= $selectedRole === $role ? 'selected' : '' ?>><?= $app->e(ucfirst($role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="field-label" for="status">Status</label>
                                <select class="field-select" id="status" name="status">
                                    <?php foreach (['active', 'inactive'] as $status) : ?>
                                        <?php $selectedStatus = $app->old('status', $user['status'] ?? 'active'); ?>
                                        <option value="<?= $app->e($status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>><?= $app->e(ucfirst($status)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="toolbar">
                            <button class="btn-primary" type="submit" data-loading-text="<?= $isEdit ? 'Saving...' : 'Creating...' ?>"><i data-lucide="save"></i><span><?= $isEdit ? 'Save User' : 'Create User' ?></span></button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
