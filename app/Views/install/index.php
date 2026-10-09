<div class="content-container-narrow" style="padding-top: var(--space-8); max-width: 520px; margin: 0 auto;">
    <div class="panel p-8" style="background: var(--surface-card); border: 1px solid var(--border-outline); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal);">
        <div class="text-center mb-6">
            <?= logo_svg(44) ?>
            <h1 class="font-heading font-bold text-xl mt-3 text-primary">WorkShift Database Setup</h1>
            <p class="text-muted text-xs font-semibold">Schema initialization & demo environment seeder</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="panel p-3 mb-4 flex items-center gap-2" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--color-danger-text); border-radius: var(--radius-sm);">
                <?= clay_icon('alert-circle', 16, 'text-danger') ?>
                <span class="text-xs"><strong>Database Notice:</strong> <?= $this->e($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="panel p-4 text-xs mb-6" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
            <div class="flex items-center justify-between py-1.5" style="border-bottom: 1px solid var(--border-subtle);">
                <span class="text-muted font-bold">Database Host</span>
                <strong class="font-mono text-primary"><?= $this->e($dbConfig['host'] . ':' . $dbConfig['port']) ?></strong>
            </div>
            <div class="flex items-center justify-between py-1.5" style="border-bottom: 1px solid var(--border-subtle);">
                <span class="text-muted font-bold">Database Name</span>
                <strong class="font-mono text-primary"><?= $this->e($dbConfig['database']) ?></strong>
            </div>
            <div class="flex items-center justify-between py-1.5">
                <span class="text-muted font-bold">Status</span>
                <?= $isInstalled ? '<span class="lozenge lozenge-success font-bold">Installed & Ready</span>' : '<span class="lozenge lozenge-warning font-bold">Ready to Initialize</span>' ?>
            </div>
        </div>

        <?php if ($isInstalled): ?>
            <div class="panel p-3 text-center mb-5 flex items-center justify-center gap-2" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: var(--color-success-text); border-radius: var(--radius-sm);">
                <?= clay_icon('check', 16, 'text-success') ?>
                <span class="text-xs font-bold">Database tables are active and ready!</span>
            </div>
            <div class="text-center">
                <a href="/login" class="btn btn-primary w-full font-semibold">Proceed to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="/install">
                <?= $this->csrf() ?>
                <div class="panel p-4 mb-5" style="background: var(--surface-well); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="with_seed" value="1" checked style="accent-color: var(--color-primary); width: 16px; height: 16px;">
                        <span class="text-xs font-bold text-primary">Load demo freelancer account & 3 active client boards</span>
                    </label>
                    <small class="text-muted block text-xs mt-2 pl-7">Includes demo login: <code>alex@studioclickup.com</code> / <code>password123</code></small>
                </div>
                <button type="submit" class="btn btn-primary w-full font-semibold" style="height: 42px;">
                    Run Database Setup Now
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
