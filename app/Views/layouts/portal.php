<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $this->e($pageTitle ?? 'Client Workspace — WorkShift') ?></title>
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
<body class="portal-body" style="background-color: var(--color-bg-page); min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column;">
    <!-- Client Portal Fixed Top Bar (No Sidebar) -->
    <header style="height: var(--topbar-height); padding: 0 var(--space-6); background: var(--color-bg-surface); border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: var(--z-topbar);">
        <div style="display: flex; align-items: center; gap: var(--space-3);">
            <?= logo_svg(28) ?>
            <div>
                <div style="font-size: var(--text-body); font-weight: var(--weight-semibold); line-height: 1.2; color: var(--color-text);">Client Workspace</div>
                <div style="font-size: 11px; color: var(--color-text-subtle);">
                    For <?= $this->e($client['name']) ?> · with <strong><?= $this->e($client['freelancer_company'] ?: $client['freelancer_name']) ?></strong>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: var(--space-3);">
            <!-- Live Sync Indicator -->
            <div class="lozenge lozenge-default" style="gap: 6px;">
                <span style="display: inline-block; width: 6px; height: 6px; border-radius: var(--radius-full); background: var(--color-success);"></span>
                <span>Live Sync</span>
            </div>

            <!-- Theme Toggle -->
            <button type="button" class="clay-btn clay-btn-ghost clay-btn-icon-sm" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
                <span class="theme-toggle-icon" data-theme="dark"><?= clay_icon('sun', 16) ?></span>
                <span class="theme-toggle-icon" data-theme="light"><?= clay_icon('moon', 16) ?></span>
            </button>

            <!-- Book Call Button -->
            <?php if (!empty($client['freelancer_scheduling_link'])): ?>
                <a href="<?= $this->e($client['freelancer_scheduling_link']) ?>" target="_blank" rel="noopener" class="clay-btn clay-btn-primary clay-btn-sm">
                    <?= clay_icon('scheduling', 14) ?>
                    <span>Book a Call</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Portal Content -->
    <main style="flex: 1; padding: var(--space-6); max-width: 1760px; margin: 0 auto; width: 100%;">
        <?= $content ?>
    </main>

    <!-- Task Modal for Portal (Review notes, uploads) -->
    <div id="task-modal" class="clay-modal-backdrop hidden" role="dialog" aria-modal="true">
        <div class="clay-modal">
            <div class="modal-header">
                <h3 id="modal-task-title" style="font-size: var(--text-h3); font-weight: var(--weight-semibold); margin: 0;">Task Details</h3>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeTaskModal()" aria-label="Close modal">
                    <?= clay_icon('x', 16) ?>
                </button>
            </div>
            <div id="modal-task-body" class="modal-body"></div>
        </div>
    </div>

    <!-- Portal Footer -->
    <footer style="padding: var(--space-6); text-align: center; font-size: var(--text-caption); color: var(--color-text-subtle); border-top: 1px solid var(--color-border); background: var(--color-bg-surface); margin-top: auto;">
        <p>Private & Secure Workspace for <?= $this->e($client['name']) ?> · Powered by WorkShift</p>
    </footer>

    <script src="<?= asset('/assets/js/app.js') ?>"></script>
    <script src="<?= asset('/assets/js/portal.js') ?>"></script>
    <!-- In-App Modal System -->
    <?php include APP_PATH . '/Views/partials/modal.php'; ?>
</body>
</html>
