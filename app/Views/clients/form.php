<div class="content-wrapper">
    <!-- Breadcrumbs -->
    <div class="breadcrumb">
        <a href="/clients">Clients</a>
        <span class="breadcrumb-separator">/</span>
        <span><?= $isEdit ? 'Edit Client' : 'New Client' ?></span>
    </div>

    <!-- Title Row -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary m-0"><?= $isEdit ? 'Edit Client Profile' : 'Add New Client' ?></h1>
            <p class="text-sm text-subtle mt-1 mb-0">
                <?= $isEdit ? 'Update client contact specifications and contract details.' : 'Adding a client automatically generates their dedicated workspace board and client portal link.' ?>
            </p>
        </div>
    </div>

    <!-- Two-Column Desktop Form Layout (Left: 8 cols, Right: 4 cols sticky aside) -->
    <form method="POST" action="<?= $isEdit ? '/clients/' . $client['id'] . '/update' : '/clients' ?>" class="dirty-check" id="client-form">
        <?= $this->csrf() ?>

        <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: var(--space-6); align-items: start;">
            <!-- Left Column: Form Section Cards (8 cols) -->
            <div style="grid-column: span 8; display: flex; flex-direction: column; gap: var(--space-6);" class="client-form-main-col">
                
                <!-- Card 1: Contact Details -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h3 class="caption-uppercase mb-4 text-primary font-bold">Contact Details</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5);" class="client-grid-2col">
                        <div class="form-group mb-0">
                            <label for="name" class="form-label">Client Contact Name *</label>
                            <input type="text" id="name" name="name" class="form-input w-full" value="<?= $this->e($client['name'] ?? '') ?>" placeholder="e.g. Maria Santos" required autofocus autocomplete="name">
                        </div>
                        <div class="form-group mb-0">
                            <label for="company" class="form-label">Company / Brand Name</label>
                            <input type="text" id="company" name="company" class="form-input w-full" value="<?= $this->e($client['company'] ?? '') ?>" placeholder="e.g. Acme Tech Philippines" autocomplete="organization">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5); margin-top: var(--space-5);" class="client-grid-2col">
                        <div class="form-group mb-0">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input w-full" value="<?= $this->e($client['email'] ?? '') ?>" placeholder="maria@acmetech.ph" required autocomplete="email">
                            <span class="form-help">Used for automated blocker reminders and invoice receipts.</span>
                        </div>
                        <div class="form-group mb-0">
                            <label for="phone" class="form-label">Phone / Mobile (Optional)</label>
                            <input type="tel" id="phone" name="phone" class="form-input w-full" value="<?= $this->e($client['phone'] ?? '') ?>" placeholder="+63 917 123 4567" autocomplete="tel">
                        </div>
                    </div>
                </div>

                <!-- Card 2: Contract & Billing -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h3 class="caption-uppercase mb-4 text-primary font-bold">Contract & Billing</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-5);" class="client-grid-3col">
                        <div class="form-group mb-0">
                            <label for="pipeline_stage" class="form-label">Pipeline Stage</label>
                            <select id="pipeline_stage" name="pipeline_stage" class="form-select w-full">
                                <option value="inquiry" <?= ($client['pipeline_stage'] ?? '') === 'inquiry' ? 'selected' : '' ?>>Inquiry / Lead</option>
                                <option value="active" <?= ($client['pipeline_stage'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active Contract</option>
                                <option value="completed" <?= ($client['pipeline_stage'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="archived" <?= ($client['pipeline_stage'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label for="billing_type" class="form-label">Billing Type</label>
                            <select id="billing_type" name="billing_type" class="form-select w-full" onchange="updateRateLabel(this.value)">
                                <option value="hourly" <?= ($client['billing_type'] ?? 'hourly') === 'hourly' ? 'selected' : '' ?>>Hourly (Logged × Rate)</option>
                                <option value="fixed" <?= ($client['billing_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Price Milestone</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label for="rate" id="rate-label" class="form-label">
                                <?= ($client['billing_type'] ?? 'hourly') === 'hourly' ? 'Hourly Rate (PHP ₱)' : 'Fixed Project Amount (PHP ₱)' ?>
                            </label>
                            <input type="number" step="0.01" min="0" id="rate" name="rate" class="form-input w-full" value="<?= $this->e($client['rate'] ?? $defaultRate ?? '750.00') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Workflow Stages -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="caption-uppercase m-0 text-primary font-bold">Workflow Stages</h3>
                        <span class="lozenge lozenge-default">Default Template</span>
                    </div>
                    <p class="text-xs text-subtle mb-4">These stage columns will structure the client's dedicated Kanban board:</p>

                    <!-- Editable Stage Chips -->
                    <div id="stages-chips-container" style="display: flex; flex-wrap: wrap; gap: var(--space-2); margin-bottom: var(--space-4);">
                        <div class="stage-chip" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 6px 12px; font-size: 0.8125rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <span class="text-subtle">⋮⋮</span>
                            <span class="stage-chip-title">To Do</span>
                        </div>
                        <div class="stage-chip" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 6px 12px; font-size: 0.8125rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <span class="text-subtle">⋮⋮</span>
                            <span class="stage-chip-title">In Progress</span>
                        </div>
                        <div class="stage-chip" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-warning-border); color: var(--color-warning-text); border-radius: var(--radius-md); padding: 6px 12px; font-size: 0.8125rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <span class="text-subtle">⋮⋮</span>
                            <span class="stage-chip-title">Review (Client)</span>
                        </div>
                        <div class="stage-chip" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-success-border); color: var(--color-success-text); border-radius: var(--radius-md); padding: 6px 12px; font-size: 0.8125rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <span class="text-subtle">⋮⋮</span>
                            <span class="stage-chip-title">Done</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Internal Notes -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h3 class="caption-uppercase mb-2 text-primary font-bold">Internal Notes (Private to you)</h3>
                    <p class="text-xs text-subtle mb-3">Notes, communication preferences, or background scope. Strictly never visible on the client portal.</p>
                    <textarea id="notes" name="notes" rows="4" class="form-textarea w-full" placeholder="Project scope details, communication preferences, contract milestones..."><?= $this->e($client['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Right Column: Sticky Aside (4 cols) -->
            <div style="grid-column: span 4; display: flex; flex-direction: column; gap: var(--space-6); position: sticky; top: calc(var(--topbar-height) + var(--space-6));" class="client-form-aside-col">
                
                <!-- Action Buttons Card (Always visible at top of aside) -->
                <div class="panel p-5" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <button type="submit" class="btn btn-primary w-full font-semibold mb-2" style="height: 42px;">
                        <?= $isEdit ? 'Save Changes' : 'Create Client' ?>
                    </button>
                    <a href="<?= $isEdit ? '/clients/' . $client['id'] : '/clients' ?>" class="btn btn-secondary w-full text-center font-medium">
                        Cancel
                    </a>
                </div>

                <!-- Card A: What Happens Next -->
                <div class="panel p-5" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h4 class="font-bold text-sm text-primary mb-3 flex items-center gap-2">
                        <?= clay_icon('sparkles', 16, 'text-primary') ?>
                        <span>What Happens Next</span>
                    </h4>
                    <ul class="flex flex-col gap-3 text-xs text-subtle" style="list-style: none; padding: 0; margin: 0;">
                        <li class="flex items-start gap-2">
                            <span class="text-success font-bold">1.</span>
                            <span>A dedicated Kanban board is initialized with the 4 standard workflow stages.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-success font-bold">2.</span>
                            <span>A secure, passwordless client portal link is generated for seamless milestone sign-offs.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-success font-bold">3.</span>
                            <span>You can start logging billable hours or assigning deliverable scope right away.</span>
                        </li>
                    </ul>

                    <!-- Mini Lane Preview -->
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin-top: 16px; padding: 8px; background: var(--color-bg-surface-sunken); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                        <div style="height: 32px; background: var(--color-bg-surface); border-radius: 4px; border: 1px solid var(--color-border);"></div>
                        <div style="height: 32px; background: var(--color-bg-surface); border-radius: 4px; border: 1px solid var(--color-border);"></div>
                        <div style="height: 32px; background: var(--color-bg-surface); border-radius: 4px; border: 1px solid var(--color-warning-border);"></div>
                        <div style="height: 32px; background: var(--color-bg-surface); border-radius: 4px; border: 1px solid var(--color-success-border);"></div>
                    </div>
                </div>

                <!-- Card B: Plan Usage -->
                <div class="panel p-5" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-xs text-primary">Plan Usage</span>
                        <span class="lozenge <?= ($currentUser['plan'] ?? 'basic') === 'pro' ? 'lozenge-discovery' : 'lozenge-default' ?>">
                            <?= ($currentUser['plan'] ?? 'basic') === 'pro' ? 'Pro Plan' : 'Basic Plan' ?>
                        </span>
                    </div>

                    <?php if (($currentUser['plan'] ?? 'basic') === 'pro'): ?>
                        <div class="text-xs text-subtle mb-1">Active Clients Quota</div>
                        <div class="font-heading font-bold text-lg text-primary mb-2">Unlimited</div>
                        <p class="text-xs text-subtle m-0">You have no active client limits on WorkShift Pro.</p>
                    <?php else: ?>
                        <div class="flex items-center justify-between text-xs text-subtle mb-1">
                            <span>Active Clients</span>
                            <span class="font-bold text-primary"><?= (int)($planLimits['active_clients'] ?? 2) ?> / 3</span>
                        </div>
                        <div style="height: 6px; background: var(--color-border); border-radius: 3px; overflow: hidden; margin-bottom: 12px;">
                            <div style="width: <?= min(100, ((int)($planLimits['active_clients'] ?? 2) / 3) * 100) ?>%; height: 100%; background: var(--color-brand); border-radius: 3px;"></div>
                        </div>
                        <a href="/settings?upgrade=1" class="btn btn-secondary btn-sm w-full text-center">
                            Upgrade to Pro (₱499/mo)
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </form>
</div>

<style>
@media (max-width: 1023px) {
    .client-form-main-col {
        grid-column: span 12 !important;
    }
    .client-form-aside-col {
        grid-column: span 12 !important;
        position: static !important;
    }
}
@media (max-width: 639px) {
    .client-grid-2col,
    .client-grid-3col {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function updateRateLabel(val) {
    const lbl = document.getElementById('rate-label');
    if (!lbl) return;
    if (val === 'fixed') {
        lbl.innerText = 'Fixed Project Amount (PHP ₱)';
    } else {
        lbl.innerText = 'Hourly Rate (PHP ₱)';
    }
}
</script>
