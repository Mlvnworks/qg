<?php
$auth->requireAuth();
$topbarTitle = 'Quiz Creation';
$topbarCopy = 'Create a quiz from a topic or file.';
$storedTimerSeconds = max(10, min(7200, (int) $app->old('timer_per_question', '300')));
$timerUnit = 'minutes';
$timerValue = $storedTimerSeconds;

if ($storedTimerSeconds % 3600 === 0) {
    $timerUnit = 'hours';
    $timerValue = (int) ($storedTimerSeconds / 3600);
} elseif ($storedTimerSeconds % 60 === 0) {
    $timerUnit = 'minutes';
    $timerValue = (int) ($storedTimerSeconds / 60);
}
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="sparkles"></i> Create Quiz</span>
                        <h2 class="section-title !text-[2.3rem]">New quiz</h2>
                        <div class="quick-actions mt-4">
                            <span class="filter-chip"><i data-lucide="brain-circuit"></i> Topic</span>
                            <span class="filter-chip"><i data-lucide="file-up"></i> Upload</span>
                            <span class="filter-chip"><i data-lucide="timer"></i> Timer</span>
                        </div>

                        <form method="post" action="<?= $app->e($app->url('home')) ?>" enctype="multipart/form-data" class="page-stack mt-4">
                            <?= $security->csrfField() ?>
                            <input type="hidden" name="quiz_create_form" value="1">

                            <div class="surface-card !bg-[var(--card-alt)]">
                                <div class="panel-header">
                                    <div>
                                        <p class="mb-1 font-semibold text-[var(--primary)]">1. Source</p>
                                    </div>
                                    <span class="icon-badge--soft"><i data-lucide="files"></i></span>
                                </div>
                                <div class="form-grid mt-4">
                                    <div class="form-field">
                                        <label class="field-label" for="title">Quiz title</label>
                                        <div class="field-wrap">
                                            <span class="field-wrap__icon"><i data-lucide="pen-square"></i></span>
                                            <input class="field-input field-input--icon" id="title" name="title" type="text" value="<?= $app->e($app->old('title')) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-field">
                                        <label class="field-label" for="topic">Topic</label>
                                        <div class="field-wrap">
                                            <span class="field-wrap__icon"><i data-lucide="brain"></i></span>
                                            <input class="field-input field-input--icon" id="topic" name="topic" type="text" value="<?= $app->e($app->old('topic')) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-field form-field--full">
                                        <label class="field-label" for="source_file">Upload source file</label>
                                        <input class="field-input" id="source_file" name="source_file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg,.webp,.txt">
                                        <span class="field-help">PDF, DOCX, PPTX, image, TXT. Max 10MB.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="surface-card !bg-[var(--card-alt)]">
                                <div class="panel-header">
                                    <div>
                                        <p class="mb-1 font-semibold text-[var(--primary)]">2. Settings</p>
                                    </div>
                                    <span class="icon-badge--soft"><i data-lucide="sliders-horizontal"></i></span>
                                </div>
                                <div class="form-grid mt-4">
                                    <div class="form-field">
                                        <label class="field-label" for="difficulty">Difficulty</label>
                                        <select class="field-select" id="difficulty" name="difficulty">
                                            <?php foreach (['Easy', 'Medium', 'Hard'] as $difficulty) : ?>
                                                <option value="<?= $app->e($difficulty) ?>" <?= $app->old('difficulty', 'Medium') === $difficulty ? 'selected' : '' ?>><?= $app->e($difficulty) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="field-label" for="question_count">Number of questions</label>
                                        <input class="field-input" id="question_count" name="question_count" type="number" min="1" max="20" value="<?= $app->e($app->old('question_count', '10')) ?>" required>
                                    </div>
                                    <div class="form-field form-field--full">
                                        <label class="field-label" for="timer_value">Quiz timer</label>
                                        <input type="hidden" id="timer_per_question" name="timer_per_question" value="<?= $app->e((string) $storedTimerSeconds) ?>">
                                        <div class="filter-row">
                                            <input class="field-input" id="timer_value" type="number" min="1" max="120" value="<?= $app->e((string) $timerValue) ?>" required>
                                            <select class="field-select" id="timer_unit">
                                                <option value="minutes" <?= $timerUnit === 'minutes' ? 'selected' : '' ?>>Minutes</option>
                                                <option value="hours" <?= $timerUnit === 'hours' ? 'selected' : '' ?>>Hours</option>
                                                <option value="seconds" <?= $timerUnit === 'seconds' ? 'selected' : '' ?>>Seconds</option>
                                            </select>
                                        </div>
                                        <span class="field-help" id="timer_help_text">Total quiz time.</span>
                                    </div>
                                    <div class="form-field">
                                        <label class="field-label" for="passing_rate">Passing rate</label>
                                        <input class="field-input" id="passing_rate" name="passing_rate" type="number" min="1" max="100" value="<?= $app->e($app->old('passing_rate', '70')) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="toolbar">
                                <button class="btn-primary" type="submit" data-loading-text="Generating quiz...">
                                    <i data-lucide="wand-sparkles"></i>
                                    <span>Generate Quiz</span>
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="surface-card">
                        <span class="section-kicker"><i data-lucide="eye"></i> Preview</span>
                        <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Preview</h3>
                        <div class="question-card mt-4">
                            <div class="question-card__header">
                                <div>
                                    <p class="metric-label">Example question</p>
                                    <h4 class="mt-2 mb-0 text-xl font-bold text-[var(--primary)]">What is the main purpose of photosynthesis?</h4>
                                </div>
                                <span class="status-badge status-active">Preview</span>
                            </div>
                            <div class="choices-grid">
                                <?php foreach ([
                                    'To convert light energy into chemical energy',
                                    'To remove all oxygen from plants',
                                    'To create minerals from soil',
                                    'To stop glucose production',
                                ] as $index => $choice) : ?>
                                    <div class="quiz-choice quiz-choice--large">
                                        <span class="choice-bullet"><?= $app->e(chr(65 + $index)) ?></span>
                                        <div class="text-sm text-[var(--text)]"><?= $app->e($choice) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    (() => {
        const hiddenTimerInput = document.getElementById('timer_per_question');
        const timerValueInput = document.getElementById('timer_value');
        const timerUnitInput = document.getElementById('timer_unit');
        const timerHelpText = document.getElementById('timer_help_text');

        if (!hiddenTimerInput || !timerValueInput || !timerUnitInput || !timerHelpText) {
            return;
        }

        const unitMultipliers = {
            seconds: 1,
            minutes: 60,
            hours: 3600
        };

        const syncTimerValue = () => {
            const rawValue = parseInt(timerValueInput.value || '0', 10);
            const safeValue = Math.max(1, rawValue || 1);
            const multiplier = unitMultipliers[timerUnitInput.value] || 60;
            const totalSeconds = Math.max(10, Math.min(7200, safeValue * multiplier));
            hiddenTimerInput.value = String(totalSeconds);

            const totalMinutes = totalSeconds / 60;

            if (totalSeconds < 60) {
                timerHelpText.textContent = totalSeconds + ' seconds total.';
            } else if (totalMinutes < 60) {
                timerHelpText.textContent = (totalMinutes % 1 === 0 ? totalMinutes.toFixed(0) : totalMinutes.toFixed(1)) + ' minutes total.';
            } else {
                const totalHours = totalSeconds / 3600;
                timerHelpText.textContent = (totalHours % 1 === 0 ? totalHours.toFixed(0) : totalHours.toFixed(1)) + ' hours total.';
            }
        };

        timerValueInput.addEventListener('input', syncTimerValue);
        timerUnitInput.addEventListener('change', syncTimerValue);
        syncTimerValue();
    })();
</script>
