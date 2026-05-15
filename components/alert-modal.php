<?php
$flash = $app->consumeFlash();
$alertPayload = $flash ?? ($_SESSION['alert'] ?? null);

if (!is_array($alertPayload)) {
    return;
}

$alertType = (string) ($alertPayload['type'] ?? (!empty($alertPayload['error']) ? 'danger' : 'success'));
$alertTitle = (string) ($alertPayload['title'] ?? 'Update');
$alertContent = (string) ($alertPayload['message'] ?? $alertPayload['content'] ?? '');
$icons = [
    'success' => 'check-circle-2',
    'danger' => 'shield-alert',
    'warning' => 'triangle-alert',
    'info' => 'info',
];
$icon = $icons[$alertType] ?? $icons['info'];
?>
<div class="toast-stack">
    <div class="toast-card toast-card--<?= $app->e($alertType) ?>" data-toast-card>
        <span class="icon-badge"><i data-lucide="<?= $app->e($icon) ?>"></i></span>
        <div>
            <p class="mb-1 font-semibold text-[var(--primary)]"><?= $app->e($alertTitle) ?></p>
            <p class="mb-0 text-sm text-[var(--muted)]"><?= $app->e($alertContent) ?></p>
        </div>
    </div>
</div>
