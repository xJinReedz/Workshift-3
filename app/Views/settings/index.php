<div class="content-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/dashboard">WorkShift</a>
        <span class="breadcrumb-separator">/</span>
        <span>Settings</span>
    </div>

    <!-- Page Header Bar -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary m-0">Account & Plan Settings</h1>
            <p class="text-sm text-subtle mt-1 mb-0">Configure your freelancer profile, scheduling links, and subscription tier.</p>
        </div>
    </div>

    <!-- Upgrade Prompt Banner if query param upgrade=1 -->
    <?php if (!empty($showUpgrade)): ?>
        <div class="panel p-4 mb-6 flex items-center justify-between flex-wrap gap-4" style="background: rgba(87, 157, 255, 0.1); border: 1px solid var(--color-brand); border-radius: var(--radius-lg);">
            <div>
                <strong class="text-primary font-bold">Upgrade to WorkShift Pro (₱499/mo):</strong>
                <p class="text-xs text-subtle mt-1 mb-0">Unlock unlimited active clients, Maya QR Ph in-app checkout, automated blocker reminders, and 50 GB storage!</p>
            </div>
            <?php if (!empty($appConfig['app']['allow_dev_plan_toggle'])): ?>
                <form method="POST" action="/settings/toggle-plan" style="margin: 0;">
                    <?= $this->csrf() ?>
                    <input type="hidden" name="plan" value="pro">
                    <button type="submit" class="btn btn-primary btn-sm font-semibold">Simulate Pro Upgrade Now</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Two-Column Settings Layout (Sub-navigation left 240px, content right) -->
    <div class="settings-container-grid">
        <!-- Sub-navigation left (240px sticky) -->
        <nav class="settings-subnav" aria-label="Settings Sections">
            <a href="#profile-section" class="settings-nav-item active" onclick="activateNav(this)">
                <?= clay_icon('settings', 16) ?>
                <span>Studio Profile</span>
            </a>
            <a href="#plan-section" class="settings-nav-item" onclick="activateNav(this)">
                <?= clay_icon('sparkles', 16) ?>
                <span>Subscription Plan</span>
            </a>
        </nav>

        <!-- Content Right: Two-Column Responsive Grid on Desktop (1280px+) -->
        <div class="settings-cards-grid">
            <!-- Card 1: Studio & Profile -->
            <div id="profile-section" class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                <div class="flex items-center gap-2 mb-1">
                    <?= clay_icon('settings', 16, 'text-brand') ?>
                    <h3 style="font-size: var(--text-h3); font-weight: var(--weight-bold); margin: 0;" class="text-primary">Studio & Profile</h3>
                </div>
                <p class="text-xs text-subtle mb-5 pb-3" style="border-bottom: 1px solid var(--color-border);">This branding is displayed on your invoices and client portals.</p>

                <form method="POST" action="/settings" class="dirty-check" id="settings-profile-form">
                    <?= $this->csrf() ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);" class="settings-fields-2col">
                        <div class="form-group mb-4">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-input w-full" value="<?= $this->e($user['name']) ?>" required autocomplete="name">
                        </div>

                        <div class="form-group mb-4">
                            <label for="company_name" class="form-label">Studio / Brand Name</label>
                            <input type="text" id="company_name" name="company_name" class="form-input w-full" value="<?= $this->e($user['company_name'] ?? '') ?>" placeholder="e.g. Studio Click Up" autocomplete="organization">
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="hourly_rate" class="form-label">Default Hourly Rate (PHP ₱)</label>
                        <input type="number" step="0.01" min="0" id="hourly_rate" name="hourly_rate" class="form-input w-full tabular-nums font-semibold" value="<?= $this->e($user['hourly_rate'] ?? '750.00') ?>" required>
                    </div>

                    <div class="form-group mb-6">
                        <label for="scheduling_link" class="form-label">Booking / Scheduling Link (Calendly, Cal.com)</label>
                        <input type="url" id="scheduling_link" name="scheduling_link" class="form-input w-full" value="<?= $this->e($user['scheduling_link'] ?? '') ?>" placeholder="https://cal.com/your-username">
                        <span class="form-help">Displayed in client portals when tasks are blocked waiting on scheduling a call.</span>
                    </div>

                    <div class="flex items-center justify-end pt-4" style="border-top: 1px solid var(--color-border);">
                        <button type="submit" class="btn btn-primary font-semibold">Save Profile Settings</button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Subscription Plan -->
            <div id="plan-section" class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                <div class="flex items-center justify-between mb-1 pb-3" style="border-bottom: 1px solid var(--color-border);">
                    <div class="flex items-center gap-2">
                        <?= clay_icon('sparkles', 16, 'text-brand') ?>
                        <h3 style="font-size: var(--text-h3); font-weight: var(--weight-bold); margin: 0;" class="text-primary">Subscription Plan</h3>
                    </div>
                    <span class="lozenge <?= $planDetails['is_pro'] ? 'lozenge-discovery' : 'lozenge-default' ?>">
                        <?= strtoupper($planDetails['plan']) ?>
                    </span>
                </div>

                <!-- Usage Progress: Clients -->
                <div class="mb-5 mt-4">
                    <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                        <span class="text-subtle">Active Clients Quota</span>
                        <span class="text-primary">
                            <?= $planDetails['active_clients'] ?> / <?= $planDetails['max_active_clients'] === -1 ? 'Unlimited' : $planDetails['max_active_clients'] ?>
                        </span>
                    </div>
                    <?php if ($planDetails['max_active_clients'] !== -1): ?>
                        <div style="background: var(--color-bg-surface-sunken); height: 6px; border-radius: var(--radius-full); overflow: hidden; border: 1px solid var(--color-border);">
                            <div style="background: var(--color-brand); height: 100%; width: <?= min(100, ($planDetails['active_clients'] / 3) * 100) ?>%;"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Usage Progress: Storage -->
                <div class="mb-5">
                    <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                        <span class="text-subtle">Storage Used</span>
                        <span class="text-primary">
                            <?= format_bytes($planDetails['storage_used_bytes']) ?> / <?= format_bytes($planDetails['storage_max_bytes']) ?>
                            (<?= $planDetails['storage_percent'] ?>%)
                        </span>
                    </div>
                    <div style="background: var(--color-bg-surface-sunken); height: 6px; border-radius: var(--radius-full); overflow: hidden; border: 1px solid var(--color-border);">
                        <div style="background: <?= $planDetails['storage_percent'] > 85 ? 'var(--color-danger)' : 'var(--color-brand)' ?>; height: 100%; width: <?= $planDetails['storage_percent'] ?>%;"></div>
                    </div>
                </div>

                <!-- Plan Features Summary -->
                <div class="panel p-4 text-xs mb-6" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                    <strong style="font-size: var(--text-body); font-weight: var(--weight-bold); display: block; margin-bottom: var(--space-2);" class="text-primary">
                        <?= $planDetails['is_pro'] ? 'WorkShift Pro (₱499/mo)' : 'WorkShift Basic (Free Forever)' ?> includes:
                    </strong>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;" class="text-subtle">
                        <li class="flex items-center gap-2"><?= $planDetails['is_pro'] ? '<span class="text-success font-bold">✓</span> Unlimited active clients' : '<span class="text-success font-bold">✓</span> Up to 3 active clients' ?></li>
                        <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Signature blocker labels & days waiting</li>
                        <li class="flex items-center gap-2"><span class="text-success font-bold">✓</span> Dedicated client boards & real-time live sync</li>
                        <li class="flex items-center gap-2"><?= $planDetails['is_pro'] ? '<span class="text-success font-bold">✓</span> In-app client payments (Maya QR Ph & Cards)' : '<span class="text-subtle font-bold">✗</span> In-app client payments (Pro only)' ?></li>
                        <li class="flex items-center gap-2"><?= $planDetails['is_pro'] ? '<span class="text-success font-bold">✓</span> Automated blocker reminder emails' : '<span class="text-subtle font-bold">✗</span> Automated blocker reminder emails (Pro only)' ?></li>
                        <li class="flex items-center gap-2"><?= $planDetails['is_pro'] ? '<span class="text-success font-bold">✓</span> 50 GB file storage' : '<span class="text-success font-bold">✓</span> 2 GB file storage' ?></li>
                    </ul>
                </div>

                <!-- Plan Actions with Confirm Modals -->
                <?php if (!empty($appConfig['app']['allow_dev_plan_toggle'])): ?>
                    <div class="pt-4" style="border-top: 1px solid var(--color-border);">
                        <div class="caption-uppercase mb-1 text-primary font-bold">Subscription Actions</div>
                        <p class="text-xs text-subtle mb-3">Upgrade or switch plans for your studio workspace.</p>
                        <form method="POST" action="/settings/toggle-plan" style="margin: 0;" id="plan-toggle-form">
                            <?= $this->csrf() ?>
                            <?php if ($planDetails['is_pro']): ?>
                                <input type="hidden" name="plan" value="basic">
                                <button type="submit" class="btn btn-secondary btn-sm"
                                        data-confirm-title="Downgrade to Basic Plan?"
                                        data-confirm-message="Your account will return to the 3-active-client quota and Maya payments will be disabled. Are you sure?"
                                        data-confirm-text="Downgrade"
                                        data-confirm-variant="danger">
                                    Switch to Basic Plan
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="plan" value="pro">
                                <button type="submit" class="btn btn-primary btn-sm font-semibold"
                                        data-confirm-title="Upgrade to WorkShift Pro?"
                                        data-confirm-message="Upgrade for ₱499/month to unlock unlimited clients, Maya QR Ph payments, and automated email reminders."
                                        data-confirm-text="Confirm Upgrade"
                                        data-confirm-variant="confirm">
                                    Upgrade to Pro Plan (₱499/mo)
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container-grid {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: var(--space-8);
    align-items: start;
}

.settings-subnav {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    position: sticky;
    top: calc(var(--topbar-height) + var(--space-6));
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    font-size: var(--text-body);
    font-weight: var(--weight-medium);
    color: var(--color-text-subtle);
    text-decoration: none;
    transition: background var(--transition-fast), color var(--transition-fast);
    position: relative;
}

.settings-nav-item:hover {
    background: var(--color-bg-hover);
    color: var(--color-text);
    text-decoration: none;
}

.settings-nav-item.active {
    background: var(--color-bg-selected);
    color: var(--color-brand);
    font-weight: var(--weight-semibold);
}

.settings-nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 4px;
    bottom: 4px;
    width: 3px;
    border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    background: var(--color-brand);
}

.settings-cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-6);
    align-items: start;
}

@media (max-width: 1279px) {
    .settings-cards-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1023px) {
    .settings-container-grid {
        grid-template-columns: 1fr;
        gap: var(--space-6);
    }
    .settings-subnav {
        flex-direction: row;
        position: static;
        overflow-x: auto;
        border-bottom: 1px solid var(--color-border);
        padding-bottom: var(--space-2);
    }
}

@media (max-width: 639px) {
    .settings-fields-2col {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function activateNav(el) {
    document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
}
</script>
