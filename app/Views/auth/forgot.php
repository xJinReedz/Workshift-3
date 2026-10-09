<form method="POST" action="/forgot-password">
    <?= $this->csrf() ?>

    <h2 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin-bottom: var(--space-1); text-align: center;">Reset password</h2>
    <p class="text-sm text-subtle text-center" style="margin-bottom: var(--space-6);">Enter your email address and we'll send you a password reset link.</p>

    <div class="form-group mb-6">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="clay-input" placeholder="alex@studioclickup.com" required autofocus autocomplete="email">
    </div>

    <div>
        <button type="submit" class="clay-btn clay-btn-primary w-full font-medium" style="width: 100%;">
            Send Reset Link
        </button>
    </div>

    <div class="mt-6 text-center text-sm text-subtle">
        Remembered your password? <a href="/login" class="text-brand font-medium">Log in</a>
    </div>
</form>
