<form method="POST" action="/register">
    <?= $this->csrf() ?>

    <h2 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin-bottom: var(--space-1); text-align: center;">Create your account</h2>
    <p class="text-sm text-subtle text-center" style="margin-bottom: var(--space-6);">Start organizing your freelance clients in 60 seconds.</p>

    <div class="form-group">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" id="name" name="name" class="clay-input" value="<?= $this->e($old['name'] ?? '') ?>" placeholder="Alex Rivera" required autofocus autocomplete="name">
    </div>

    <div class="form-group">
        <label for="company_name" class="form-label">Studio / Brand Name</label>
        <input type="text" id="company_name" name="company_name" class="clay-input" value="<?= $this->e($old['company_name'] ?? '') ?>" placeholder="Rivera Digital Design" autocomplete="organization">
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Work Email</label>
        <input type="email" id="email" name="email" class="clay-input" value="<?= $this->e($old['email'] ?? '') ?>" placeholder="alex@riveradesign.ph" required autocomplete="email">
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password (min 8 characters)</label>
        <input type="password" id="password" name="password" class="clay-input" placeholder="••••••••" minlength="8" required autocomplete="new-password">
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="clay-input" placeholder="••••••••" minlength="8" required autocomplete="new-password">
    </div>

    <div style="margin-top: var(--space-6);">
        <button type="submit" class="clay-btn clay-btn-primary w-full font-medium" style="width: 100%;">
            Create Free Account
        </button>
    </div>

    <div class="mt-6 text-center text-sm text-subtle">
        Already have an account? <a href="/login" class="text-brand font-medium">Log in here</a>
    </div>
</form>
