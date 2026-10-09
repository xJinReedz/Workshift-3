<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $this->e($pageTitle ?? 'WorkShift — Authentication') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
    <!-- Instant Dark/Light Mode Bootstrapper -->
    <script>
        window.__CSRF_TOKEN__ = "<?= csrf_token() ?>";
        window.__BASE_URL__ = "<?= base_url() ?>";
        (function() {
            const saved = localStorage.getItem('workshift-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'dark'));
        })();
    </script>
</head>
<body>
    <div class="auth-wrapper">
        <!-- Floating Theme Switcher -->
        <div style="position: absolute; top: var(--space-4); right: var(--space-4);">
            <button type="button" class="clay-btn clay-btn-ghost clay-btn-icon-sm" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
                <span class="theme-toggle-icon" data-theme="dark"><?= clay_icon('sun', 16) ?></span>
                <span class="theme-toggle-icon" data-theme="light"><?= clay_icon('moon', 16) ?></span>
            </button>
        </div>

        <div class="auth-card">
            <div class="text-center mb-6">
                <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-2 mb-2" style="text-decoration: none;">
                    <?= logo_svg(32) ?>
                    <span style="font-size: var(--text-h2); font-weight: var(--weight-semibold); color: var(--color-text);">WorkShift</span>
                </a>
                <p class="text-subtle text-xs" style="margin-bottom: 0;">Freelance Client CRM</p>
            </div>

            <?= $content ?>
        </div>

        <footer style="margin-top: var(--space-6); text-align: center; font-size: var(--text-caption); color: var(--color-text-subtlest);">
            <p>&copy; <?= date('Y') ?> WorkShift · Client CRM for Freelancers</p>
        </footer>
    </div>

    <script src="<?= asset('/assets/js/app.js') ?>"></script>
    <!-- In-App Modal System -->
    <?php include APP_PATH . '/Views/partials/modal.php'; ?>
</body>
</html>
