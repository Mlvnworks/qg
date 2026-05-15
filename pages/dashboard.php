<?php
$auth->requireAuth();
$currentUser = $auth->user();
$summary = $quizService->dashboardSummary((int) $currentUser['id']);
$recentQuizzes = $quizService->recentQuizzes((int) $currentUser['id']);
$recentAttempts = $quizService->recentAttempts((int) $currentUser['id']);
$realtimeState = $quizService->getRealtimePanelState((int) $currentUser['id']);
$realtimeCards = $realtimeState['cards'];
$realtimeQuizMap = [];

foreach ($realtimeState['channels'] as $channel) {
    $realtimeQuizMap[(int) $channel['id']] = $channel['title'];
}

$pusherReady = $pusherService->isReady();
$pusherConfig = $pusherService->getClientConfig();
$testQuizId = isset($realtimeState['channels'][0]['id']) ? (int) $realtimeState['channels'][0]['id'] : 0;
$topbarTitle = 'User Dashboard';
$topbarCopy = 'Quizzes, attempts, and live activity.';
?>
<div class="app-shell">
    <?php include __DIR__ . '/../components/dashboard-sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/dashboard-topbar.php'; ?>
        <main class="app-content">
            <div class="container page-stack">
                <div class="toolbar">
                    <div>
                        <span class="section-kicker"><i data-lucide="layout-dashboard"></i> Overview</span>
                        <h2 class="section-title !text-[2.6rem]">Welcome back, <?= $app->e($currentUser['full_name']) ?></h2>
                    </div>
                    <div class="filter-row">
                        <a href="<?= $app->e($app->url('quiz-create')) ?>" class="btn-primary"><i data-lucide="sparkles"></i><span>Create Quiz</span></a>
                        <button class="btn-secondary" type="button" data-modal-open="#join-quiz-modal"><i data-lucide="log-in"></i><span>Join Quiz</span></button>
                        <a href="<?= $app->e($app->url('reports')) ?>" class="btn-secondary"><i data-lucide="file-bar-chart-2"></i><span>Reports</span></a>
                    </div>
                </div>

                <div class="metric-grid">
                    <?php foreach ([
                        ['icon' => 'notebook-tabs', 'label' => 'Quizzes', 'value' => $summary['total_quizzes_created'], 'trend' => 'Created'],
                        ['icon' => 'check-check', 'label' => 'Taken', 'value' => $summary['total_quizzes_taken'], 'trend' => 'Completed'],
                        ['icon' => 'users', 'label' => 'Ongoing', 'value' => $summary['ongoing_attempts'], 'trend' => 'Live'],
                        ['icon' => 'circle-check-big', 'label' => 'Completed', 'value' => $summary['completed_attempts'], 'trend' => 'Finished'],
                    ] as $card) : ?>
                        <article class="metric-card metric-card--quarter">
                            <span class="icon-badge"><i data-lucide="<?= $app->e($card['icon']) ?>"></i></span>
                            <p class="metric-label mt-4"><?= $app->e($card['label']) ?></p>
                            <h3 class="metric-value"><?= $app->e((string) $card['value']) ?></h3>
                            <span class="metric-trend"><i data-lucide="dot"></i><?= $app->e($card['trend']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="cards-grid cards-grid--2">
                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="book-marked"></i> Recent Quizzes</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Latest</h3>
                            </div>
                            <a href="<?= $app->e($app->url('quiz-list')) ?>" class="btn-secondary">View all</a>
                        </div>
                        <?php if ($recentQuizzes === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="notebook-tabs"></i></div>
                                <p class="mb-0 muted-copy">No quizzes.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recentQuizzes as $quiz) : ?>
                            <a class="list-card list-card--panel" href="<?= $app->e($app->url('quiz-view', ['id' => $quiz['id']])) ?>">
                                <div>
                                    <h4 class="list-title"><?= $app->e($quiz['title']) ?></h4>
                                    <p class="list-meta"><?= $app->e($quiz['difficulty']) ?> · <?= $app->e((string) $quiz['question_count']) ?> Qs</p>
                                </div>
                                <span class="status-badge status-<?= $app->e($quiz['status']) ?>"><?= $app->e(ucfirst($quiz['status'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </section>

                    <section class="surface-card">
                        <div class="panel-header">
                            <div>
                                <span class="section-kicker"><i data-lucide="history"></i> Recent Attempts</span>
                                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Latest activity</h3>
                            </div>
                        </div>
                        <?php if ($recentAttempts === []) : ?>
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="history"></i></div>
                                <p class="mb-0 muted-copy">No attempts.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recentAttempts as $attempt) : ?>
                            <div class="list-card list-card--panel">
                                <div>
                                    <h4 class="list-title"><?= $app->e($attempt['title']) ?></h4>
                                    <p class="list-meta"><?= $app->e((string) $attempt['score']) ?>/<?= $app->e((string) $attempt['total_questions']) ?> · <?= $app->e((string) $attempt['percentage']) ?>%</p>
                                </div>
                                <span class="status-badge status-<?= $app->e(strtolower((string) ($attempt['result'] ?? $attempt['status']))) ?>"><?= $app->e($attempt['result'] ?? ucfirst($attempt['status'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </section>
                </div>

                <section class="surface-card">
                    <div class="panel-header">
                        <div>
                            <span class="section-kicker"><i data-lucide="radio-tower"></i> Real-Time Activity</span>
                            <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]">Live feed</h3>
                        </div>
                        <span class="status-badge <?= $pusherReady ? 'status-active' : 'status-inactive' ?>" data-realtime-connection-status>
                            <?= $pusherReady ? 'Live Connected' : 'Not Ready' ?>
                        </span>
                    </div>
                    <div class="cards-grid cards-grid--3 mt-4">
                        <article class="timeline-card" data-realtime-card="latest_taker">
                            <p class="metric-label">Latest Taker</p>
                            <h4 class="mt-3 text-xl font-bold text-[var(--primary)]" data-realtime-value><?= $app->e($realtimeCards['latest_taker']['value']) ?></h4>
                            <p class="compact-copy mt-2 mb-0" data-realtime-meta><?= $app->e($realtimeCards['latest_taker']['meta']) ?></p>
                        </article>
                        <article class="timeline-card" data-realtime-card="completion_status">
                            <p class="metric-label">Completion Status</p>
                            <h4 class="mt-3 text-xl font-bold text-[var(--primary)]" data-realtime-value><?= $app->e($realtimeCards['completion_status']['value']) ?></h4>
                            <p class="compact-copy mt-2 mb-0" data-realtime-meta><?= $app->e($realtimeCards['completion_status']['meta']) ?></p>
                        </article>
                        <article class="timeline-card" data-realtime-card="last_score">
                            <p class="metric-label">Last Score</p>
                            <h4 class="mt-3 text-xl font-bold text-[var(--primary)]" data-realtime-value><?= $app->e($realtimeCards['last_score']['value']) ?></h4>
                            <p class="compact-copy mt-2 mb-0" data-realtime-meta><?= $app->e($realtimeCards['last_score']['meta']) ?></p>
                        </article>
                    </div>
                    <div class="cards-grid mt-4" data-realtime-feed>
                        <?php if ($realtimeCards['events'] === []) : ?>
                            <div class="empty-state" data-realtime-empty>
                                <div class="empty-state__icon"><i data-lucide="radio-tower"></i></div>
                                <p class="mb-0 muted-copy">No live events.</p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($realtimeCards['events'] as $event) : ?>
                                <article class="list-card list-card--panel">
                                    <div>
                                        <div class="filter-row">
                                            <span class="status-badge <?= $app->e($event['event_name'] === 'quiz.attempt.completed' ? 'status-active' : ($event['event_name'] === 'quiz.attempt.started' ? 'status-ongoing' : 'status-draft')) ?>"><?= $app->e($event['label']) ?></span>
                                        </div>
                                        <h4 class="list-title mt-3"><?= $app->e($event['quiz_title']) ?></h4>
                                        <p class="list-meta"><?= $app->e($event['summary']) ?></p>
                                    </div>
                                    <span class="metric-label"><?= $app->e($tools->formatTimestamp($event['created_at']) ?: 'Now') ?></span>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<div class="questra-modal" id="join-quiz-modal" aria-hidden="true">
    <div class="questra-modal__backdrop" data-modal-close></div>
    <div class="questra-modal__dialog surface-card" role="dialog" aria-modal="true" aria-labelledby="join-quiz-title">
        <div class="panel-header">
            <div>
                <span class="section-kicker"><i data-lucide="key-round"></i> Join Quiz</span>
                <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]" id="join-quiz-title">Enter a share code</h3>
            </div>
            <button class="btn-secondary !p-3" type="button" data-modal-close aria-label="Close join quiz modal">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form method="get" action="" class="page-stack mt-4">
            <input type="hidden" name="c" value="take-quiz">
            <div class="field-wrap">
                <span class="field-wrap__icon"><i data-lucide="hash"></i></span>
                <input class="field-input field-input--icon" type="text" name="quiz" placeholder="Quiz code" required>
            </div>
            <div class="filter-row">
                <button class="btn-primary" type="submit" data-loading-text="Joining...">
                    <i data-lucide="arrow-right-circle"></i>
                    <span>Join</span>
                </button>
                <button class="btn-secondary" type="button" data-modal-close>
                    <i data-lucide="x"></i>
                    <span>Cancel</span>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    (() => {
        const realtimeConfig = <?= json_encode([
            'enabled' => $pusherReady,
            'key' => $pusherConfig['key'] ?? '',
            'cluster' => $pusherConfig['cluster'] ?? '',
            'quizIds' => array_map('intval', array_keys($realtimeQuizMap)),
            'quizTitles' => $realtimeQuizMap,
            'postUrl' => $app->url('home'),
            'csrfToken' => $security->csrfToken(),
            'testQuizId' => $testQuizId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const feed = document.querySelector('[data-realtime-feed]');
        const emptyState = feed?.querySelector('[data-realtime-empty]');
        const connectionBadge = document.querySelector('[data-realtime-connection-status]');
        const cardElements = {
            latest_taker: document.querySelector('[data-realtime-card="latest_taker"]'),
            completion_status: document.querySelector('[data-realtime-card="completion_status"]'),
            last_score: document.querySelector('[data-realtime-card="last_score"]')
        };
        const testButton = document.querySelector('[data-pusher-test-trigger]');
        const eventLabels = {
            'quiz.created': 'Quiz Created',
            'quiz.attempt.started': 'Attempt Started',
            'quiz.attempt.completed': 'Attempt Submitted',
            'quiz.attempt.unfinished': 'Attempt Unfinished',
            'quiz.test.ping': 'Live Test'
        };
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

        const setCard = (cardKey, value, meta) => {
            const card = cardElements[cardKey];

            if (!card) {
                return;
            }

            const valueNode = card.querySelector('[data-realtime-value]');
            const metaNode = card.querySelector('[data-realtime-meta]');

            if (valueNode) {
                valueNode.textContent = value;
            }

            if (metaNode) {
                metaNode.textContent = meta;
            }
        };

        const buildEventCard = (label, title, summary) => {
            const wrapper = document.createElement('article');
            wrapper.className = 'list-card list-card--panel';

            const left = document.createElement('div');
            const badgeRow = document.createElement('div');
            badgeRow.className = 'filter-row';

            const badge = document.createElement('span');
            badge.className = 'status-badge ' + (label === 'Attempt Submitted' ? 'status-active' : (label === 'Attempt Started' ? 'status-ongoing' : 'status-draft'));
            badge.textContent = label;
            badgeRow.appendChild(badge);

            const heading = document.createElement('h4');
            heading.className = 'list-title mt-3';
            heading.textContent = title;

            const copy = document.createElement('p');
            copy.className = 'list-meta';
            copy.textContent = summary;

            left.appendChild(badgeRow);
            left.appendChild(heading);
            left.appendChild(copy);

            const time = document.createElement('span');
            time.className = 'metric-label';
            time.textContent = 'Just now';

            wrapper.appendChild(left);
            wrapper.appendChild(time);

            return wrapper;
        };

        const prependEvent = (eventName, payload) => {
            if (!feed) {
                return;
            }

            if (emptyState) {
                emptyState.remove();
            }

            const actorName = payload.actor_name || 'A user';
            const quizTitle = payload.quiz_title || realtimeConfig.quizTitles[String(payload.quiz_id)] || 'Quiz activity';
            let summary = actorName + ' sent a live event for ' + quizTitle + '.';

            if (eventName === 'quiz.created') {
                summary = actorName + ' created ' + quizTitle + '.';
            } else if (eventName === 'quiz.attempt.started') {
                summary = actorName + ' started ' + quizTitle + '.';
            } else if (eventName === 'quiz.attempt.completed') {
                const percentage = typeof payload.percentage === 'number' ? payload.percentage.toFixed(2) : payload.percentage;
                summary = actorName + ' submitted ' + quizTitle + ' with ' + (percentage || '0.00') + '%.';
            } else if (eventName === 'quiz.attempt.unfinished') {
                summary = actorName + ' left ' + quizTitle + ' unfinished.';
            } else if (eventName === 'quiz.test.ping') {
                summary = actorName + ' sent a live Pusher test for ' + quizTitle + '.';
            }

            feed.prepend(buildEventCard(eventLabels[eventName] || 'Activity', quizTitle, summary));

            while (feed.children.length > 6) {
                feed.removeChild(feed.lastElementChild);
            }
        };

        const applyRealtimeEvent = (eventName, payload) => {
            const actorName = payload.actor_name || 'A user';
            const quizTitle = payload.quiz_title || realtimeConfig.quizTitles[String(payload.quiz_id)] || 'Quiz';

            if (eventName === 'quiz.attempt.started') {
                setCard('latest_taker', actorName, quizTitle);
                setCard('completion_status', 'Ongoing', actorName + ' started ' + quizTitle + '.');
            } else if (eventName === 'quiz.attempt.completed') {
                const percentage = typeof payload.percentage === 'number' ? payload.percentage.toFixed(2) : payload.percentage;
                setCard('latest_taker', actorName, quizTitle);
                setCard('completion_status', 'Completed', actorName + ' submitted ' + quizTitle + '.');
                setCard('last_score', (percentage || '0.00') + '%', payload.result || 'Latest submitted result.');
                showRealtimeToast('New quiz submission', actorName + ' submitted ' + quizTitle + '.');
            } else if (eventName === 'quiz.attempt.unfinished') {
                setCard('latest_taker', actorName, quizTitle);
                setCard('completion_status', 'Unfinished', actorName + ' left ' + quizTitle + '.');
            } else if (eventName === 'quiz.test.ping') {
                setCard('completion_status', 'Live Signal', 'Manual Pusher test was received.');
            }

            prependEvent(eventName, payload);
        };

        if (realtimeConfig.enabled && window.Pusher && realtimeConfig.quizIds.length > 0) {
            const pusher = new window.Pusher(realtimeConfig.key, {
                cluster: realtimeConfig.cluster
            });

            realtimeConfig.quizIds.forEach((quizId) => {
                const channel = pusher.subscribe('quiz-' + quizId);

                Object.keys(eventLabels).forEach((eventName) => {
                    channel.bind(eventName, (payload) => {
                        applyRealtimeEvent(eventName, payload || {});
                    });
                });
            });
        } else if (connectionBadge) {
            connectionBadge.textContent = 'Not Ready';
        }

        if (testButton) {
            testButton.addEventListener('click', async () => {
                if (testButton.disabled || !realtimeConfig.testQuizId) {
                    return;
                }

                testButton.disabled = true;
                const originalMarkup = testButton.innerHTML;
                testButton.innerHTML = '<span class="loading-spinner" aria-hidden="true"></span><span>' + (testButton.getAttribute('data-loading-text') || 'Sending...') + '</span>';

                try {
                    const formData = new FormData();
                    formData.append('pusher_test_form', '1');
                    formData.append('quiz_id', String(realtimeConfig.testQuizId));
                    formData.append('csrf_token', realtimeConfig.csrfToken);
                    formData.append('redirect_page', 'dashboard');

                    const response = await fetch(realtimeConfig.postUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const payload = await response.json();

                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.message || 'Live test failed.');
                    }
                } catch (error) {
                    if (connectionBadge) {
                        connectionBadge.className = 'status-badge status-inactive';
                        connectionBadge.textContent = 'Test Failed';
                    }

                    prependEvent('quiz.test.ping', {
                        actor_name: 'System',
                        quiz_title: 'Pusher Connection',
                        percentage: 0
                    });
                    setCard('completion_status', 'Test Failed', error.message || 'Pusher request failed.');
                } finally {
                    testButton.innerHTML = originalMarkup;
                    testButton.disabled = false;

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                }
            });
        }
    })();
</script>
