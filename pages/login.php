<?php
if ($auth->check()) {
    $currentUser = $auth->user();
    $app->redirect($currentUser !== null && $currentUser['role'] === 'admin' ? 'admin-dashboard' : 'dashboard');
}
?>
<section class="auth-shell">
    <div class="auth-card">
        <div class="auth-header">
            <a class="brand-mark" href="<?= $app->e($app->url('home')) ?>">
                <span class="brand-mark__icon"><i data-lucide="graduation-cap"></i></span>
                <span><?= $app->e(APP_NAME) ?></span>
            </a>
        </div>
        <h1 class="section-title mt-4 !text-[2.5rem]">Sign in</h1>

        <form method="post" action="<?= $app->e($app->url('home')) ?>" class="auth-form">
            <?= $security->csrfField() ?>
            <input type="hidden" name="login_form" value="1">

            <div class="form-field">
                <label class="field-label" for="email">Email address</label>
                <div class="field-wrap">
                    <span class="field-wrap__icon"><i data-lucide="mail"></i></span>
                    <input class="field-input field-input--icon" id="email" name="email" type="email" required>
                </div>
            </div>

            <div class="form-field">
                <label class="field-label" for="password">Password</label>
                <div class="password-wrap">
                    <span class="field-wrap__icon"><i data-lucide="lock"></i></span>
                    <input class="field-input" id="password" name="password" type="password" required>
                    <button class="password-toggle" type="button" data-password-toggle="password">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
            </div>

            <button class="btn-primary w-full" type="submit" data-loading-text="Signing in...">
                <i data-lucide="log-in"></i>
                <span>Sign In</span>
            </button>
        </form>

        <div class="auth-card__links">
            <a href="<?= $app->e($app->url('register')) ?>">Create account</a>
            <a href="<?= $app->e($app->url('home')) ?>">Back</a>
        </div>
    </div>
</section>
