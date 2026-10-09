<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $this->e($pageTitle ?? 'WorkShift — The Client CRM for Freelancers') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
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
<body style="background-color: var(--color-bg-page); min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column;">
    <!-- Public Header -->
    <header style="height: var(--topbar-height); padding: 0 var(--space-6); background: var(--color-bg-surface); border-bottom: 1px solid var(--color-border); position: sticky; top: 0; z-index: var(--z-topbar);">
        <div style="max-width: 1200px; height: 100%; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: var(--space-4);">
            <a href="<?= base_url('/') ?>" style="display: flex; align-items: center; gap: var(--space-2); text-decoration: none; color: var(--color-text);">
                <?= logo_svg(28) ?>
                <span style="font-size: var(--text-body-lg); font-weight: var(--weight-semibold); color: var(--color-text);">WorkShift</span>
            </a>

            <div style="display: flex; align-items: center; gap: var(--space-3);">
                <!-- Theme Toggle -->
                <button type="button" class="clay-btn clay-btn-ghost clay-btn-icon-sm" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
                    <span class="theme-toggle-icon" data-theme="dark"><?= clay_icon('sun', 16) ?></span>
                    <span class="theme-toggle-icon" data-theme="light"><?= clay_icon('moon', 16) ?></span>
                </button>

                <a href="<?= base_url('/login') ?>" class="clay-btn clay-btn-ghost clay-btn-sm font-medium">Log In</a>
                <a href="<?= base_url('/register') ?>" class="clay-btn clay-btn-primary clay-btn-sm font-medium">Start Free</a>
            </div>
        </div>
    </header>

    <main style="flex: 1;">
        <?= $content ?>
    </main>

    <!-- Public Footer -->
    <footer style="background: var(--color-bg-surface); border-top: 1px solid var(--color-border); padding: var(--space-12) var(--space-6) var(--space-6); margin-top: auto;">
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-8); margin-bottom: var(--space-8);">
            <div>
                <div style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3);">
                    <?= logo_svg(24) ?>
                    <span style="font-size: var(--text-body-lg); font-weight: var(--weight-semibold); color: var(--color-text);">WorkShift</span>
                </div>
                <p class="text-sm text-subtle">The client CRM built specifically for freelancers and independent contractors in the Philippines.</p>
            </div>
            <div>
                <h4 class="caption-uppercase mb-3">Product</h4>
                <div style="display: flex; flex-direction: column; gap: var(--space-2); font-size: var(--text-body);">
                    <a href="#features" class="text-subtle hover:text-primary">Features</a>
                    <a href="#blockers" class="text-subtle hover:text-primary">Blocker Engine</a>
                    <a href="#pricing" class="text-subtle hover:text-primary">Pricing (Basic vs Pro)</a>
                </div>
            </div>
            <div>
                <h4 class="caption-uppercase mb-3">Account</h4>
                <div style="display: flex; flex-direction: column; gap: var(--space-2); font-size: var(--text-body);">
                    <a href="<?= base_url('/login') ?>" class="text-subtle hover:text-primary">Log In</a>
                    <a href="<?= base_url('/register') ?>" class="text-subtle hover:text-primary">Sign Up Free</a>
                    <a href="<?= base_url('/install') ?>" class="text-subtle hover:text-primary">Database Setup</a>
                </div>
            </div>
            <div>
                <h4 class="caption-uppercase mb-3">About</h4>
                <div style="display: flex; flex-direction: column; gap: var(--space-2); font-size: var(--text-body); color: var(--color-text-subtle);">
                    <span>Studio Click Up</span>
                    <span>Philippines</span>
                    <span>Currency: PHP (₱)</span>
                </div>
            </div>
        </div>

        <div style="max-width: 1200px; margin: 0 auto; padding-top: var(--space-4); border-top: 1px solid var(--color-border); text-align: center; font-size: var(--text-caption); color: var(--color-text-subtlest);">
            <p>&copy; <?= date('Y') ?> WorkShift by Studio Click Up. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?= asset('/assets/js/app.js') ?>"></script>
    <!-- In-App Modal System -->
    <?php include APP_PATH . '/Views/partials/modal.php'; ?>
    <?php if ($flashInfo = \WorkShift\Core\Session::flash('info')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.WS && window.WS.modal) {
                    window.WS.modal.info({
                        title: 'Logged Out',
                        message: <?= json_encode((string)$flashInfo) ?>
                    });
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
