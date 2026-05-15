<?php
$currentUser = $auth->user();
$topbarTitle = $topbarTitle ?? 'Dashboard';
$topbarCopy = $topbarCopy ?? '';
?>
<header class="dashboard-topbar">
    <div class="container dashboard-topbar__inner">
        <div class="flex items-center gap-3">
            <button class="mobile-sidebar-toggle" type="button" data-sidebar-target="#dashboard-sidebar">
                <i data-lucide="panel-left-open"></i>
            </button>
            <div>
                <h1 class="topbar-title"><?= $app->e($topbarTitle) ?></h1>
                <?php if ($topbarCopy !== '') : ?>
                    <p class="topbar-copy"><?= $app->e($topbarCopy) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
