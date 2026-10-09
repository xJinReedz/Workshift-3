<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>500 Server Error — WorkShift</title>
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
<body style="margin: 0; padding: 0; width: 100vw; min-height: 100vh; background: var(--color-bg-page, #1D2125); color: var(--color-text, #DEE4EA); display: flex; align-items: center; justify-content: center;">
    <div style="width: 100%; max-width: 440px; padding: var(--space-4); margin: auto;">
        <div class="panel text-center p-8" style="background: var(--color-bg-surface, #22272B); border: 1px solid var(--color-border, #38414A); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
            <div class="inline-flex items-center justify-center mb-4" style="color: var(--color-danger); background: var(--color-danger-bg); width: 56px; height: 56px; border-radius: 50%;">
                <?= clay_icon('alert-circle', 28) ?>
            </div>
            <h1 class="font-heading text-4xl font-bold text-danger mb-1">500</h1>
            <h2 class="font-heading text-base font-bold mb-2" style="color: var(--color-text);">Internal Server Error</h2>
            <p class="text-xs text-subtle mb-6" style="line-height: 1.5; color: var(--color-text-subtle);"><?= e($message ?? 'An unexpected error occurred while processing your request.') ?></p>
            <div>
                <a href="<?= base_url('/') ?>" class="clay-btn clay-btn-primary w-full font-semibold" style="width: 100%;">Return Home</a>
            </div>
        </div>
    </div>
</body>
</html>
