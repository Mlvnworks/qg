<?php
$auth->requireAuth();
$shareCode = trim((string) ($_GET['quiz'] ?? ''));
$attemptId = (int) ($_GET['attempt'] ?? 0);

if ($shareCode === '') {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$currentUser = $auth->user();
$quiz = $quizService->getPublicQuizByShareCode($shareCode);

if (!$quiz) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$attempt = $attemptId > 0 ? $quizService->getAttemptForUser($attemptId, (int) $currentUser['id']) : null;
$isActiveAttempt = $attempt !== null && ($attempt['status'] ?? '') === 'ongoing';
$totalDurationSeconds = max(1, (int) $quiz['timer_per_question']);
$remainingSeconds = $totalDurationSeconds;
$progressWidth = 0;

if ($isActiveAttempt) {
    $startedAt = strtotime((string) ($attempt['started_at'] ?? '')) ?: time();
    $elapsedSeconds = max(0, time() - $startedAt);
    $remainingSeconds = max(0, $totalDurationSeconds - $elapsedSeconds);
    $progressWidth = min(100, max(0, ($elapsedSeconds / max(1, $totalDurationSeconds)) * 100));
}
?>
<section class="section-block">
    <div class="container page-stack">
        <div class="surface-card !max-w-4xl !mx-auto">
            <span class="section-kicker"><i data-lucide="play-circle"></i> Quiz</span>
            <h1 class="section-title !text-[2.8rem]"><?= $app->e($quiz['title']) ?></h1>
            <p class="section-copy mt-3"><?= $app->e($quiz['creator_name']) ?> · <?= $app->e($quiz['difficulty']) ?> · <?= $app->e((string) $quiz['question_count']) ?> questions · <?= $app->e((string) $totalDurationSeconds) ?> sec total</p>

            <div class="cards-grid cards-grid--3 mt-4">
                <article class="timeline-card">
                    <p class="metric-label">Status</p>
                    <h3 class="metric-value !text-[2rem]"><?= $app->e(ucfirst($quiz['status'])) ?></h3>
                </article>
                <article class="timeline-card">
                    <p class="metric-label">Passing Rate</p>
                    <h3 class="metric-value !text-[2rem]"><?= $app->e((string) $quiz['passing_rate']) ?>%</h3>
                </article>
                <article class="timeline-card">
                    <p class="metric-label"><?= $attempt !== null ? 'Time Used' : 'Time Limit' ?></p>
                    <div class="progress-bar mt-3" data-quiz-progress>
                        <span style="width: <?= $app->e(number_format((float) $progressWidth, 2, '.', '')) ?>%"></span>
                    </div>
                    <p class="compact-copy mt-3 mb-0" data-quiz-progress-copy>
                        <?= $isActiveAttempt ? $app->e((string) round($progressWidth)) . '% used' : $app->e((string) $totalDurationSeconds) . ' sec total' ?>
                    </p>
                </article>
            </div>

            <div class="question-card mt-5">
                <div class="question-card__header">
                    <div>
                        <span class="section-kicker"><i data-lucide="monitor-play"></i> Start</span>
                        <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Ready</h3>
                    </div>
                    <span class="status-badge status-ongoing" data-quiz-timer-badge><i data-lucide="timer"></i> <?= $isActiveAttempt ? gmdate('i:s', $remainingSeconds) : $app->e((string) $totalDurationSeconds) . ' sec' ?></span>
                </div>

                <?php if ($isActiveAttempt) : ?>
                    <form method="post" action="<?= $app->e($app->url('home')) ?>" class="page-stack" data-quiz-attempt-form data-remaining-seconds="<?= $app->e((string) $remainingSeconds) ?>" data-total-seconds="<?= $app->e((string) $totalDurationSeconds) ?>">
                        <?= $security->csrfField() ?>
                        <input type="hidden" name="submit_quiz_form" value="1">
                        <input type="hidden" name="attempt_id" value="<?= $app->e((string) $attempt['id']) ?>">
                        <input type="hidden" name="share_code" value="<?= $app->e($shareCode) ?>">

                        <?php foreach ($attempt['questions'] as $questionIndex => $question) : ?>
                            <div class="surface-card !bg-[var(--card-alt)]">
                                <div class="question-card__header">
                                    <div>
                                        <p class="metric-label">Question <?= $app->e((string) ($questionIndex + 1)) ?></p>
                                        <h3 class="mt-2 mb-0 text-xl font-bold text-[var(--primary)]"><?= $app->e($question['question_text']) ?></h3>
                                    </div>
                                    <span class="status-badge status-active"><?= $app->e($question['difficulty']) ?></span>
                                </div>

                                <div class="choices-grid">
                                    <?php foreach ($question['choices'] as $choiceIndex => $choice) : ?>
                                        <label class="quiz-choice quiz-choice--large cursor-pointer">
                                            <input type="radio" name="answers[<?= $app->e((string) $question['id']) ?>]" value="<?= $app->e((string) $choice['id']) ?>" class="mt-1" required>
                                            <span class="choice-bullet"><?= $app->e(chr(65 + $choiceIndex)) ?></span>
                                            <div class="text-sm text-[var(--text)]"><?= $app->e($choice['choice_text']) ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="toolbar mt-4">
                            <a href="<?= $app->e($app->url('dashboard')) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                            <button class="btn-primary" type="submit" data-loading-text="Submitting..."><i data-lucide="send"></i><span>Submit Quiz</span></button>
                        </div>
                    </form>
                <?php elseif ($attempt !== null) : ?>
                    <div class="empty-state">
                        <div class="empty-state__icon"><i data-lucide="clock-alert"></i></div>
                        <p class="mb-0 muted-copy">This attempt is <?= $app->e(strtolower((string) $attempt['status'])) ?>.</p>
                    </div>
                    <div class="toolbar mt-4">
                        <a href="<?= $app->e($app->url('results', ['attempt' => $attempt['id'], 'quiz' => $shareCode])) ?>" class="btn-primary"><i data-lucide="badge-check"></i><span>View Result</span></a>
                        <a href="<?= $app->e($app->url('dashboard')) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                    </div>
                <?php else : ?>
                    <?php if ($quiz['questions'] !== []) : ?>
                        <p class="text-lg font-semibold text-[var(--primary)] mb-0"><?= $app->e($quiz['questions'][0]['question_text']) ?></p>
                        <div class="choices-grid">
                            <?php foreach ($quiz['questions'][0]['choices'] as $index => $choice) : ?>
                                <div class="quiz-choice quiz-choice--large">
                                    <span class="choice-bullet"><?= $app->e(chr(65 + $index)) ?></span>
                                    <div class="text-sm text-[var(--text)]"><?= $app->e($choice['choice_text']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="panel-copy mb-0">No questions yet.</p>
                    <?php endif; ?>

                    <div class="toolbar mt-4">
                        <a href="<?= $app->e($app->url('dashboard')) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back</span></a>
                        <?php if ($quiz['status'] !== 'active') : ?>
                            <button class="btn-secondary" type="button" disabled><i data-lucide="ban"></i><span>Quiz inactive</span></button>
                        <?php elseif ($quiz['questions'] === []) : ?>
                            <button class="btn-secondary" type="button" disabled><i data-lucide="circle-off"></i><span>No questions</span></button>
                        <?php else : ?>
                            <form method="post" action="<?= $app->e($app->url('home')) ?>">
                                <?= $security->csrfField() ?>
                                <input type="hidden" name="start_quiz_form" value="1">
                                <input type="hidden" name="share_code" value="<?= $app->e($shareCode) ?>">
                                <button class="btn-primary" type="submit" data-loading-text="Starting..."><i data-lucide="play"></i><span>Start Quiz</span></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php if ($isActiveAttempt) : ?>
<script>
    (() => {
        const form = document.querySelector('[data-quiz-attempt-form]');

        if (!form) {
            return;
        }

        const timerBadge = document.querySelector('[data-quiz-timer-badge]');
        const progressBar = document.querySelector('[data-quiz-progress] > span');
        const progressCopy = document.querySelector('[data-quiz-progress-copy]');
        const totalSeconds = parseInt(form.getAttribute('data-total-seconds') || '0', 10);
        let remainingSeconds = parseInt(form.getAttribute('data-remaining-seconds') || '0', 10);
        let hasSubmitted = false;
        let hasAbandoned = false;
        const postUrl = window.location.pathname + window.location.search;
        const csrfToken = form.querySelector('input[name="csrf_token"]')?.value || '';
        const attemptId = form.querySelector('input[name="attempt_id"]')?.value || '';
        const collectAnswers = () => {
            const answers = [];

            form.querySelectorAll('input[type="radio"]:checked').forEach((input) => {
                answers.push([input.name, input.value]);
            });

            return answers;
        };

        const formatTime = (seconds) => {
            const safeSeconds = Math.max(0, seconds);
            const minutes = Math.floor(safeSeconds / 60);
            const secs = safeSeconds % 60;
            return String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        };

        const updateUi = () => {
            const usedSeconds = Math.max(0, totalSeconds - remainingSeconds);
            const progressPercent = totalSeconds > 0 ? Math.min(100, (usedSeconds / totalSeconds) * 100) : 100;

            if (timerBadge) {
                timerBadge.innerHTML = '<i data-lucide="timer"></i> ' + formatTime(remainingSeconds);
            }

            if (progressBar) {
                progressBar.style.width = progressPercent.toFixed(2) + '%';
            }

            if (progressCopy) {
                progressCopy.textContent = Math.round(progressPercent) + '% used';
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        const submitTimedOutQuiz = () => {
            if (hasSubmitted) {
                return;
            }

            hasSubmitted = true;
            form.submit();
        };

        const markAsUnfinished = () => {
            if (hasSubmitted || hasAbandoned || remainingSeconds <= 0) {
                return;
            }

            hasAbandoned = true;
            const payload = new URLSearchParams();
            payload.append('abandon_quiz_form', '1');
            payload.append('attempt_id', attemptId);
            payload.append('csrf_token', csrfToken);

            collectAnswers().forEach(([name, value]) => {
                payload.append(name, value);
            });

            navigator.sendBeacon(postUrl, payload);
        };

        updateUi();

        if (remainingSeconds <= 0) {
            submitTimedOutQuiz();
            return;
        }

        window.setInterval(() => {
            if (hasSubmitted) {
                return;
            }

            remainingSeconds -= 1;
            updateUi();

            if (remainingSeconds <= 0) {
                submitTimedOutQuiz();
            }
        }, 1000);

        form.addEventListener('submit', () => {
            hasSubmitted = true;
        });

        window.addEventListener('pagehide', () => {
            markAsUnfinished();
        });
    })();
</script>
<?php endif; ?>
