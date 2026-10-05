<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>500 Server Error — WorkShift</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
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
            <div class="inline-flex items-center justify-center mb-4" style="color: var(--color-warning); background: var(--color-warning-subtle); width: 56px; height: 56px; border-radius: 50%;">
                <?= clay_icon('alert-circle', 28) ?>
            </div>
            <h1 class="font-heading text-4xl font-bold text-danger mb-1">500</h1>
            <h2 class="font-heading text-base font-bold text-primary mb-2">Internal Server Error</h2>
            <p class="text-xs text-muted mb-6" style="line-height: 1.5;"><?= $this->e($message ?? 'An unexpected error occurred while processing your request.') ?></p>
            <div>
                <a href="/" class="btn btn-primary w-full font-semibold">Return Home</a>
            </div>
        </div>
    </div>
</body>
</html>
