<?php
if ($auth->check()) {
    $app->redirect('dashboard');
}
?>
<section class="hero-section">
    <div class="container">
        <div class="max-w-3xl">
            <span class="eyebrow"><i data-lucide="sparkles"></i> AI Quiz Creation</span>
            <h1 class="hero-title">Generate Smart Quizzes in Minutes</h1>
            <p class="hero-copy mt-4">Build from topics or files. Share fast. Track results clearly.</p>
            <div class="hero-actions">
                <a href="<?= $app->e($app->url('register')) ?>" class="btn-primary">
                    <i data-lucide="arrow-right"></i>
                    <span>Get Started</span>
                </a>
                <a href="<?= $app->e($app->url('login')) ?>" class="btn-secondary">
                    <i data-lucide="log-in"></i>
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>
</section>
