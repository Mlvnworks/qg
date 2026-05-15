<?php
$allowedPages = [];
$pageFiles = glob(__DIR__ . '/../pages/*.php') ?: [];
$normalizedContent = preg_match('/^[a-z0-9\-]+$/i', $content) === 1 ? $content : 'home';

$allowedPages = array_map(function ($file) {
    return basename($file, '.php');
}, $pageFiles);

$isAllowedPage = in_array($normalizedContent, $allowedPages, true);
$pageStyleFile = __DIR__ . '/../styling/page/' . $normalizedContent . '.css';
$appName = APP_NAME;
$publicPages = ['home', 'login', 'register'];
$isPublicPage = in_array($normalizedContent, $publicPages, true);

if ($isAllowedPage && isset($_GET['export']) && $_GET['export'] !== '') {
    require __DIR__ . '/../pages/' . $normalizedContent . '.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

    <link rel="stylesheet" href="./styling/style.css">
    <?php if ($isAllowedPage && is_file($pageStyleFile)) : ?>
        <link rel="stylesheet" href="./styling/page/<?= htmlspecialchars($normalizedContent, ENT_QUOTES, 'UTF-8') ?>.css">
    <?php endif; ?>

    <link rel="shortcut icon" href="./assets/img/icon.png" type="image/x-icon">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body class="questra-body antialiased<?= $normalizedContent === 'home' ? ' questra-body--home' : '' ?>">
    <?php
    if ($isAllowedPage) {
        if ($isPublicPage) {
            include __DIR__ . '/../components/navbar.php';
        }

        require __DIR__ . '/../pages/' . $normalizedContent . '.php';

        if ($isPublicPage) {
            include __DIR__ . '/../components/footer.php';
        }

        $tools->alert();
    } else {
        http_response_code(404);
        require __DIR__ . '/../components/404.php';
    }
    ?>
    <div class="questra-modal" id="confirm-action-modal" aria-hidden="true">
        <div class="questra-modal__backdrop" data-modal-close></div>
        <div class="questra-modal__dialog surface-card" role="dialog" aria-modal="true" aria-labelledby="confirm-action-title">
            <div class="panel-header">
                <div>
                    <span class="section-kicker"><i data-lucide="shield-alert"></i> Confirm Action</span>
                    <h3 class="mt-2 text-2xl font-bold text-[var(--primary)]" id="confirm-action-title">Continue?</h3>
                </div>
                <button class="btn-secondary !p-3" type="button" data-modal-close aria-label="Close confirm modal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <p class="compact-copy mt-4 mb-0" data-confirm-copy>Review this action before continuing.</p>
            <div class="filter-row mt-4">
                <button class="btn-danger" type="button" data-confirm-submit><i data-lucide="check"></i><span>Continue</span></button>
                <button class="btn-secondary" type="button" data-modal-close><i data-lucide="x"></i><span>Cancel</span></button>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('form button[type="submit"][data-loading-text]').forEach((button) => {
            button.form?.addEventListener('submit', () => {
                if (button.disabled) {
                    return;
                }

                button.dataset.originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="loading-spinner" aria-hidden="true"></span><span>' + button.getAttribute('data-loading-text') + '</span>';
            });
        });

        document.querySelectorAll('[data-button-loading]').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                button.dataset.originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="loading-spinner" aria-hidden="true"></span><span>' + button.getAttribute('data-button-loading') + '</span>';

                window.setTimeout(() => {
                    if (button.dataset.originalText) {
                        button.innerHTML = button.dataset.originalText;
                        button.disabled = false;
                    }
                }, parseInt(button.getAttribute('data-loading-reset-ms') || '2200', 10));
            });
        });

        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const target = document.getElementById(toggle.getAttribute('data-password-toggle'));

                if (!target) {
                    return;
                }

                target.type = target.type === 'password' ? 'text' : 'password';
            });
        });

        document.querySelectorAll('[data-sidebar-target]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const sidebar = document.querySelector(toggle.getAttribute('data-sidebar-target'));

                if (sidebar) {
                    sidebar.classList.toggle('is-open');
                }
            });
        });

        const setModalState = (modal, shouldOpen) => {
            if (!modal) {
                return;
            }

            modal.classList.toggle('is-open', shouldOpen);
            modal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
            document.body.classList.toggle('questra-modal-open', shouldOpen);

            if (shouldOpen) {
                const focusTarget = modal.querySelector('input, button, select, textarea, a[href]');
                focusTarget?.focus();
            }
        };

        document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const modal = document.querySelector(trigger.getAttribute('data-modal-open'));
                setModalState(modal, true);
            });
        });

        document.querySelectorAll('.questra-modal').forEach((modal) => {
            modal.querySelectorAll('[data-modal-close]').forEach((button) => {
                button.addEventListener('click', () => {
                    setModalState(modal, false);
                });
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const activeModal = document.querySelector('.questra-modal.is-open');

            if (activeModal) {
                setModalState(activeModal, false);
            }
        });

        const confirmModal = document.getElementById('confirm-action-modal');
        const confirmTitle = confirmModal?.querySelector('#confirm-action-title');
        const confirmCopy = confirmModal?.querySelector('[data-confirm-copy]');
        const confirmSubmit = confirmModal?.querySelector('[data-confirm-submit]');
        let pendingConfirmAction = null;

        document.querySelectorAll('button[data-confirm-title], a[data-confirm-title]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();

                if (!confirmModal || !confirmSubmit || !confirmTitle || !confirmCopy) {
                    return;
                }

                confirmTitle.textContent = trigger.getAttribute('data-confirm-title') || 'Continue?';
                confirmCopy.textContent = trigger.getAttribute('data-confirm-message') || 'Review this action before continuing.';
                const submitLabel = trigger.getAttribute('data-confirm-submit-label') || 'Continue';
                confirmSubmit.innerHTML = '<i data-lucide="check"></i><span>' + submitLabel + '</span>';
                pendingConfirmAction = () => {
                    if (trigger.tagName === 'A') {
                        window.location.href = trigger.getAttribute('href') || '#';
                        return;
                    }

                    if (trigger.form) {
                        trigger.form.submit();
                    }
                };

                setModalState(confirmModal, true);

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        });

        confirmSubmit?.addEventListener('click', () => {
            if (!pendingConfirmAction) {
                return;
            }

            confirmSubmit.disabled = true;
            pendingConfirmAction();
        });

        confirmModal?.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                pendingConfirmAction = null;
                if (confirmSubmit) {
                    confirmSubmit.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-copy-text]').forEach((button) => {
            button.addEventListener('click', async () => {
                const copyText = button.getAttribute('data-copy-text') || '';

                if (!copyText) {
                    return;
                }

                const originalMarkup = button.innerHTML;

                try {
                    await navigator.clipboard.writeText(copyText);
                    button.innerHTML = '<i data-lucide="check"></i><span>' + (button.getAttribute('data-copy-success') || 'Copied') + '</span>';
                } catch (error) {
                    button.innerHTML = '<i data-lucide="copy-x"></i><span>Failed</span>';
                }

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                window.setTimeout(() => {
                    button.innerHTML = originalMarkup;

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                }, 1800);
            });
        });

        const toastCard = document.querySelector('[data-toast-card]');

        if (toastCard) {
            setTimeout(() => {
                toastCard.remove();
            }, 4500);
        }

        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
</body>

</html>
