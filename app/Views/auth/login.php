<form method="POST" action="/login">
    <?= $this->csrf() ?>

    <h2 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin-bottom: var(--space-1); text-align: center;">Log in to your account</h2>
    <p class="text-sm text-subtle text-center" style="margin-bottom: var(--space-6);">Enter your email and password to access your workspace.</p>

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="clay-input" value="<?= $this->e($email) ?>" placeholder="alex@studioclickup.com" required autofocus autocomplete="email">
    </div>

    <div class="form-group">
        <div class="flex items-center justify-between mb-1">
            <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
            <a href="/forgot-password" class="text-xs text-brand font-medium">Forgot password?</a>
        </div>
        <input type="password" id="password" name="password" class="clay-input" placeholder="••••••••" required autocomplete="current-password">
    </div>

    <div style="margin-top: var(--space-6);">
        <button type="submit" class="clay-btn clay-btn-primary w-full font-medium" style="width: 100%;">
            Log In
        </button>
    </div>

    <!-- Demo Credentials Box -->
    <div class="panel mt-6 p-3 text-xs" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-border);">
        <div class="caption-uppercase text-brand mb-1">
            <span>Demo Credentials</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px; color: var(--color-text-subtle);">
            <div>Email: <code class="tabular-nums" style="background: var(--color-bg-surface); padding: 1px 4px; border-radius: 3px; border: 1px solid var(--color-border); font-size: 11px;">alex@studioclickup.com</code></div>
            <div>Password: <code class="tabular-nums" style="background: var(--color-bg-surface); padding: 1px 4px; border-radius: 3px; border: 1px solid var(--color-border); font-size: 11px;">password123</code></div>
        </div>
    </div>

    <div class="mt-6 text-center text-sm text-subtle">
        Don't have an account yet? <a href="/register" class="text-brand font-medium">Sign up free</a>
    </div>
</form>
