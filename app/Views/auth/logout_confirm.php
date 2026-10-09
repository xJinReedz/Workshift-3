<div class="text-center p-6">
    <div class="inline-flex items-center justify-center mb-4" style="color: var(--color-danger); background: var(--color-danger-subtle); width: 56px; height: 56px; border-radius: 50%;">
        <?= clay_icon('logout', 28) ?>
    </div>
    <h1 class="font-heading text-xl font-bold text-primary mb-2">Log out of WorkShift?</h1>
    <p class="text-xs text-secondary mb-6 leading-relaxed">
        You will need to sign in again to access your clients, projects, and deliverables.
    </p>

    <form method="POST" action="<?= base_url('/logout') ?>" class="mb-3">
        <?= csrf_field() ?>
        <button type="submit" class="clay-btn clay-btn-danger w-full font-semibold mb-2" style="width: 100%;">Log Out</button>
    </form>

    <a href="<?= base_url('/dashboard') ?>" class="clay-btn clay-btn-secondary w-full text-center" style="width: 100%;">Cancel & Return to Dashboard</a>
</div>
