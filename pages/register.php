<?php
if ($auth->check()) {
    $app->redirect('dashboard');
}
?>
<section class="auth-shell">
    <div class="auth-card">
        <div class="auth-header">
            <a class="brand-mark" href="<?= $app->e($app->url('home')) ?>">
                <span class="brand-mark__icon"><i data-lucide="sparkles"></i></span>
                <span><?= $app->e(APP_NAME) ?></span>
            </a>
        </div>
        <h1 class="section-title mt-4 !text-[2.5rem]">Create account</h1>

        <form method="post" action="<?= $app->e($app->url('home')) ?>" class="auth-form">
            <?= $security->csrfField() ?>
            <input type="hidden" name="register_form" value="1">

            <div class="form-field">
                <label class="field-label" for="full_name">Full name</label>
                <div class="field-wrap">
                    <span class="field-wrap__icon"><i data-lucide="user"></i></span>
                    <input class="field-input field-input--icon" id="full_name" name="full_name" type="text" required>
                </div>
            </div>

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
                    <input class="field-input" id="password" name="password" type="password" minlength="8" required>
                    <button class="password-toggle" type="button" data-password-toggle="password"><i data-lucide="eye"></i></button>
                </div>
                <span class="field-help">Minimum 8 characters.</span>
            </div>

            <div class="form-field">
                <label class="field-label" for="password_confirmation">Confirm password</label>
                <div class="password-wrap">
                    <span class="field-wrap__icon"><i data-lucide="lock-keyhole"></i></span>
                    <input class="field-input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
                    <button class="password-toggle" type="button" data-password-toggle="password_confirmation"><i data-lucide="eye"></i></button>
                </div>
            </div>

            <button class="btn-primary w-full" type="submit" data-loading-text="Creating account...">
                <i data-lucide="user-plus"></i>
                <span>Sign Up</span>
            </button>
        </form>

        <div class="auth-card__links">
            <a href="<?= $app->e($app->url('login')) ?>">Sign in</a>
            <a href="<?= $app->e($app->url('home')) ?>">Back</a>
        </div>
    </div>
</section>
