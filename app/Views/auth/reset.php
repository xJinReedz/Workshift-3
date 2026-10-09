<form method="POST" action="/reset-password">
    <?= $this->csrf() ?>
    <input type="hidden" name="token" value="<?= $this->e($token) ?>">

    <h2 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin-bottom: var(--space-1); text-align: center;">Set new password</h2>
    <p class="text-sm text-subtle text-center" style="margin-bottom: var(--space-6);">Choose a strong password to protect your account.</p>

    <div class="form-group">
        <label for="password" class="form-label">New Password (min 8 characters)</label>
        <input type="password" id="password" name="password" class="clay-input" placeholder="••••••••" minlength="8" required autofocus autocomplete="new-password">
    </div>

    <div class="form-group mb-6">
        <label for="password_confirmation" class="form-label">Confirm New Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="clay-input" placeholder="••••••••" minlength="8" required autocomplete="new-password">
    </div>

    <div>
        <button type="submit" class="clay-btn clay-btn-primary w-full font-medium" style="width: 100%;">
            Update Password & Log In
        </button>
    </div>
</form>
