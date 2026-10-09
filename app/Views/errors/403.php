<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>403 Access Forbidden — WorkShift</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
    <script>
        (function() {
            const saved = localStorage.getItem('workshift-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--surface-bg);">
    <div class="auth-wrapper" style="width: 100%; max-width: 440px; padding: var(--space-4);">
        <div class="panel text-center p-8 auth-card" style="background: var(--surface-card); border: 1px solid var(--border-outline); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal);">
            <div class="inline-flex items-center justify-center mb-4" style="color: var(--color-danger); background: var(--color-danger-subtle); width: 56px; height: 56px; border-radius: 50%;">
                <?= clay_icon('shield', 28) ?>
            </div>
            <h1 class="font-heading text-4xl font-bold text-danger mb-1">403</h1>
            <h2 class="font-heading text-base font-bold text-primary mb-2">Access Forbidden</h2>
            <p class="text-xs text-muted mb-6" style="line-height: 1.5;"><?= e($message ?? 'You do not have permission to access this resource or client portal.') ?></p>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-primary w-full font-semibold">Return to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
