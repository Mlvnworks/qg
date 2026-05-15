<nav class="public-navbar">
    <div class="container public-navbar__inner">
        <a class="brand-mark" href="<?= $app->e($app->url('home')) ?>">
            <span class="brand-mark__icon"><i data-lucide="sparkles"></i></span>
            <span><?= $app->e(APP_NAME) ?></span>
        </a>

        <div class="nav-actions">
            <a class="btn-secondary" href="<?= $app->e($app->url('login')) ?>">Login</a>
            <a class="btn-primary" href="<?= $app->e($app->url('register')) ?>">Sign Up</a>
        </div>
    </div>
</nav>
