<?php
$auth->requireAuth();
$currentUser = $auth->user();
$quizId = (int) ($_GET['id'] ?? 0);
$sortBy = trim((string) ($_GET['sort'] ?? 'started_at'));
$sortDir = trim((string) ($_GET['dir'] ?? 'desc'));

try {
    $reportData = $quizService->getOwnerQuizAttempts($quizId, (int) $currentUser['id'], $sortBy, $sortDir);
} catch (Throwable $err) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$quiz = $reportData['quiz'];
$attempts = $reportData['attempts'];
$pusherReady = $pusherService->isReady();
$pusherConfig = $pusherService->getClientConfig();
$allowedSorts = ['taker', 'score', 'percentage', 'remark', 'started_at', 'completed_at'];
$sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'started_at';
$sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

$buildSortUrl = static function (string $column) use ($quizId, $sortBy, $sortDir): string {
    $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';
    return '?' . http_build_query([
        'c' => 'quiz-reports',
        'id' => $quizId,
        'sort' => $column,
        'dir' => $nextDir,
    ]);
};

$sortIcon = static function (string $column) use ($sortBy, $sortDir): string {
    if ($sortBy !== $column) {
        return 'arrow-up-down';
    }

    return $sortDir === 'asc' ? 'arrow-up' : 'arrow-down';
};

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="questra-quiz-' . $quizId . '-reports.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Quiz Title', 'Taker Name', 'Taker Email', 'Score', 'Total Questions', 'Percentage', 'Remark', 'Started At', 'Completed At']);

    foreach ($attempts as $attempt) {
        fputcsv($output, [
            $quiz['title'],
            $attempt['taker_name'],
            $attempt['taker_email'],
            $attempt['score'],
            $attempt['total_questions'],
            $attempt['percentage'],
            $attempt['result'] ?? ucfirst((string) $attempt['status']),
            $attempt['started_at'],
            $attempt['completed_at'],
        ]);
    }

    fclose($output);
    exit;
}

$topbarTitle = 'Quiz Reports';
$topbarCopy = 'Takers and scores.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="file-bar-chart-2"></i> Quiz Reports</span>
                        <h2 class="section-title !text-[2.4rem]"><?= $app->e($quiz['title']) ?></h2>
                        <p class="compact-copy mt-2"><?= $app->e($quiz['topic']) ?> · <?= $app->e($quiz['difficulty']) ?> · <?= $app->e((string) $quiz['question_count']) ?> questions</p>
                    </div>
                    <div class="filter-row">
                        <a href="?c=quiz-reports&id=<?= $app->e((string) $quiz['id']) ?>&export=csv" class="btn-primary" data-button-loading="Exporting..." data-loading-reset-ms="2200"><i data-lucide="download"></i><span>Export CSV</span></a>
                        <a href="<?= $app->e($app->url('quiz-view', ['id' => $quiz['id']])) ?>" class="btn-secondary"><i data-lucide="arrow-left"></i><span>Back to Quiz</span></a>
                    </div>
                </div>

                <section class="table-card">
                    <div class="panel-header mb-4">
                        <div>
                            <span class="section-kicker"><i data-lucide="users-round"></i> Attempts</span>
                        </div>
                        <span class="status-badge <?= $pusherReady ? 'status-active' : 'status-inactive' ?>" data-realtime-connection-status>
                            <?= $pusherReady ? 'Live Connected' : 'Not Ready' ?>
                        </span>
                    </div>
                    <?php if ($attempts === []) : ?>
                        <div class="empty-state">
                            <div class="empty-state__icon"><i data-lucide="users-round"></i></div>
                            <p class="mb-0 muted-copy">No takers yet.</p>
                        </div>
                    <?php else : ?>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><a href="<?= $app->e($buildSortUrl('taker')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Taker</span><i data-lucide="<?= $app->e($sortIcon('taker')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('score')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Score</span><i data-lucide="<?= $app->e($sortIcon('score')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('percentage')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Percentage</span><i data-lucide="<?= $app->e($sortIcon('percentage')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('remark')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Remark</span><i data-lucide="<?= $app->e($sortIcon('remark')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('started_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Started</span><i data-lucide="<?= $app->e($sortIcon('started_at')) ?>"></i></a></th>
                                        <th><a href="<?= $app->e($buildSortUrl('completed_at')) ?>" class="inline-flex items-center gap-2 no-underline"><span>Completed</span><i data-lucide="<?= $app->e($sortIcon('completed_at')) ?>"></i></a></th>
                                        <th>Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attempts as $attempt) : ?>
                                        <tr>
                                            <td>
                                                <strong class="text-[var(--primary)]"><?= $app->e($attempt['taker_name']) ?></strong>
                                                <div class="list-meta"><?= $app->e($attempt['taker_email']) ?></div>
                                            </td>
                                            <td><?= $app->e((string) $attempt['score']) ?>/<?= $app->e((string) $attempt['total_questions']) ?></td>
                                            <td><?= $app->e((string) $attempt['percentage']) ?>%</td>
                                            <td><span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span></td>
                                            <td><?= $app->e($tools->formatTimestamp($attempt['started_at']) ?: '') ?></td>
                                            <td><?= $app->e($tools->formatTimestamp($attempt['completed_at']) ?: 'Pending') ?></td>
                                            <td>
                                                <a href="<?= $app->e($app->url('quiz-attempt-review', ['quiz_id' => $quiz['id'], 'attempt_id' => $attempt['id']])) ?>" class="btn-secondary">
                                                    <i data-lucide="scan-eye"></i>
                                                    <span>View Answers</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</div>
<script>
    (() => {
        const realtimeConfig = <?= json_encode([
            'enabled' => $pusherReady,
            'key' => $pusherConfig['key'] ?? '',
            'cluster' => $pusherConfig['cluster'] ?? '',
            'quizId' => (int) $quiz['id'],
            'postUrl' => $app->url('home'),
            'csrfToken' => $security->csrfToken(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const connectionBadge = document.querySelector('[data-realtime-connection-status]');
        let reloadTimer = null;
        let liveToastTimer = null;

        const showRealtimeToast = (title, message, type = 'success') => {
            let stack = document.querySelector('[data-live-toast-stack]');

            if (!stack) {
                stack = document.createElement('div');
                stack.className = 'toast-stack';
                stack.setAttribute('data-live-toast-stack', '1');
                document.body.appendChild(stack);
            }

            const toast = document.createElement('div');
            toast.className = 'toast-card toast-card--' + type;
            toast.innerHTML =
                '<span class="icon-badge"><i data-lucide="bell-ring"></i></span>' +
                '<div><p class="mb-1 font-semibold text-[var(--primary)]">' + title + '</p>' +
                '<p class="mb-0 text-sm text-[var(--muted)]">' + message + '</p></div>';

            stack.appendChild(toast);

            if (window.lucide) {
                window.lucide.createIcons();
            }

            window.clearTimeout(liveToastTimer);
            liveToastTimer = window.setTimeout(() => {
                toast.remove();

                if (stack && stack.children.length === 0) {
                    stack.remove();
                }
            }, 4200);
        };

        if (realtimeConfig.enabled && window.Pusher) {
            const pusher = new window.Pusher(realtimeConfig.key, {
                cluster: realtimeConfig.cluster
            });
            const channel = pusher.subscribe('quiz-' + realtimeConfig.quizId);

            ['quiz.attempt.started', 'quiz.attempt.completed', 'quiz.attempt.unfinished', 'quiz.test.ping'].forEach((eventName) => {
                channel.bind(eventName, (payload) => {
                    const eventPayload = payload || {};

                    if (eventName === 'quiz.attempt.completed') {
                        const actorName = eventPayload.actor_name || 'A user';
                        const quizTitle = eventPayload.quiz_title || 'this quiz';
                        showRealtimeToast('New quiz submission', actorName + ' submitted ' + quizTitle + '.');
                    }

                    if (reloadTimer !== null) {
                        window.clearTimeout(reloadTimer);
                    }

                    reloadTimer = window.setTimeout(() => {
                        window.location.reload();
                    }, eventName === 'quiz.attempt.completed' ? 1400 : 350);
                });
            });
        } else if (connectionBadge) {
            connectionBadge.textContent = 'Not Ready';
        }
    })();
</script>
