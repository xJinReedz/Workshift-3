<div class="client-detail-page content-wrapper">
    <!-- Breadcrumb: Clients / [Client name] -->
    <div class="breadcrumb">
        <a href="/clients">Clients</a>
        <span class="breadcrumb-separator">/</span>
        <span><?= $this->e($client['name']) ?></span>
    </div>

    <!-- Title Row with Client Avatar & Actions -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="user-avatar-circle" style="width: 44px; height: 44px; font-size: 1.125rem;">
                <?= strtoupper(substr($client['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="font-heading text-2xl font-bold flex items-center gap-2 flex-wrap text-primary m-0">
                    <span><?= $this->e($client['name']) ?></span>
                    <?php if (!empty($client['company'])): ?>
                        <span class="text-sm font-normal text-subtle">(<?= $this->e($client['company']) ?>)</span>
                    <?php endif; ?>
                </h1>
                <div class="flex items-center gap-2 text-xs text-subtle flex-wrap mt-1">
                    <?= status_chip($client['pipeline_stage']) ?>
                    <span>·</span>
                    <span>Rate: <strong class="text-primary"><?= $client['billing_type'] === 'hourly' ? format_currency((float)$client['rate']) . '/hr' : format_currency((float)$client['rate']) . ' fixed' ?></strong></span>
                    <span>·</span>
                    <span><a href="mailto:<?= $this->e($client['email']) ?>" class="text-subtle hover:text-primary"><?= $this->e($client['email']) ?></a></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="/boards/<?= $client['board_id'] ?>" class="btn btn-primary btn-sm">
                <?= clay_icon('kanban', 14) ?>
                <span>Open Board</span>
            </a>
            <a href="/invoices/create?client_id=<?= $client['id'] ?>" class="btn btn-secondary btn-sm">
                <?= clay_icon('invoice', 14) ?>
                <span>Invoice</span>
            </a>
            <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn-secondary btn-sm">
                <?= clay_icon('edit', 14) ?>
                <span>Edit</span>
            </a>

            <!-- Client Stage / Archive Action Form -->
            <form method="POST" action="/clients/<?= $client['id'] ?>/stage" style="margin: 0;">
                <?= $this->csrf() ?>
                <?php if ($client['pipeline_stage'] === 'archived'): ?>
                    <input type="hidden" name="pipeline_stage" value="active">
                    <button type="submit" class="btn btn-secondary btn-sm"
                            data-confirm-title="Restore Client to Active?"
                            data-confirm-message="Restoring this client will count toward your active client quota."
                            data-confirm-text="Restore Client"
                            data-confirm-variant="confirm">
                        Restore
                    </button>
                <?php else: ?>
                    <input type="hidden" name="pipeline_stage" value="archived">
                    <button type="submit" class="btn btn-secondary btn-sm text-subtle"
                            data-confirm-title="Archive Client?"
                            data-confirm-message="Archived clients stop counting toward your active client limit. Their dedicated board and historical records remain safe."
                            data-confirm-text="Archive Client"
                            data-confirm-variant="warning">
                        Archive
                    </button>
                <?php endif; ?>
            </form>

            <!-- Permanent Delete Client Form with Exact Name Match -->
            <form method="POST" action="/clients/<?= $client['id'] ?>/delete" style="margin: 0;">
                <?= $this->csrf() ?>
                <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Permanently Delete Client"
                        data-confirm-title="Permanently Delete Client?"
                        data-confirm-message="This will delete this client, their dedicated Kanban board, all associated tasks, time entries, and deliverables. This action CANNOT be undone."
                        data-confirm-text="Delete Client"
                        data-confirm-variant="danger"
                        data-confirm-match="<?= $this->e($client['name']) ?>">
                    <?= clay_icon('trash', 14) ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Underlined Tab Navigation -->
    <div class="tab-nav mb-6">
        <a href="#summary" class="tab-item active" onclick="switchClientTab(event, 'tab-summary')">Summary</a>
        <a href="/boards/<?= $client['board_id'] ?>" class="tab-item">Board &rarr;</a>
        <a href="#timelog" class="tab-item" onclick="switchClientTab(event, 'tab-timelog')">Time Log (<?= count($timeEntries) ?>)</a>
        <a href="#invoices" class="tab-item" onclick="switchClientTab(event, 'tab-invoices')">Invoices (<?= count($invoices) ?>)</a>
    </div>

    <!-- Portal Invite Strip -->
    <div class="panel mb-6 p-4" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center" style="width: 32px; height: 32px; border-radius: var(--radius-md); background: rgba(87, 157, 255, 0.12); color: var(--color-brand);">
                    <?= clay_icon('link', 16) ?>
                </span>
                <div>
                    <div class="font-bold text-sm text-primary">Dedicated Client Portal Link</div>
                    <div class="text-xs text-subtle">Clients can view deliverables, approve milestones, and settle invoices without logging in.</div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <input type="text" readonly id="portal-link-input" class="form-input" value="<?= $this->e($portalUrl) ?>" style="height: 32px; font-size: 0.8125rem; width: 320px; max-width: 100%;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyPortalLink('<?= $this->e($portalUrl) ?>', this)">
                    <?= clay_icon('link', 12) ?>
                    <span>Copy</span>
                </button>
                <a href="<?= $this->e($portalUrl) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                    <?= clay_icon('external-link', 12) ?>
                    <span>Open &nearr;</span>
                </a>

                <!-- Regenerate Portal Token Form -->
                <form method="POST" action="/clients/<?= $client['id'] ?>/regenerate-token" style="margin: 0;">
                    <?= $this->csrf() ?>
                    <button type="submit" class="btn btn-ghost btn-sm text-subtle" title="Regenerate Link"
                            data-confirm-title="Regenerate Portal Link?"
                            data-confirm-message="The client's current link will stop working immediately. You will need to share the new link with them."
                            data-confirm-text="Regenerate Link"
                            data-confirm-variant="danger">
                        <span>Regenerate</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB: Summary -->
    <div id="tab-summary" class="client-tab-content">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);" class="client-summary-grid">
            <div style="display: flex; flex-direction: column; gap: var(--space-6);">
                <!-- Internal Notes -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h3 style="font-size: var(--text-body); font-weight: var(--weight-bold); margin-bottom: var(--space-3); display: flex; align-items: center; gap: var(--space-2);" class="text-primary">
                        <?= clay_icon('file-text', 14, 'text-subtle') ?>
                        <span>Internal Notes (Private to you)</span>
                    </h3>
                    <?php if (!empty($client['notes'])): ?>
                        <div class="text-sm text-subtle" style="background: var(--color-bg-surface-sunken); padding: var(--space-4); border-radius: var(--radius-md); border: 1px solid var(--color-border); line-height: 1.6;">
                            <?= nl2br($this->e($client['notes'])) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-subtle text-xs italic m-0">No internal notes added yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Invoices for this Client -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
                        <h3 style="font-size: var(--text-body); font-weight: var(--weight-bold); display: flex; align-items: center; gap: var(--space-2); margin-bottom: 0;" class="text-primary">
                            <?= clay_icon('invoice', 14, 'text-subtle') ?>
                            <span>Recent Invoices</span>
                        </h3>
                        <a href="/invoices/create?client_id=<?= $client['id'] ?>" class="btn btn-secondary btn-sm font-medium">
                            <?= clay_icon('plus', 12) ?>
                            <span>New Invoice</span>
                        </a>
                    </div>

                    <?php if (empty($invoices)): ?>
                        <p class="text-subtle text-xs italic py-4 text-center m-0">No invoices generated for this client yet.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Issue Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr>
                                            <td data-label="Invoice"><a href="/invoices/<?= $inv['id'] ?>" class="font-semibold text-primary"><?= $this->e($inv['invoice_number']) ?></a></td>
                                            <td data-label="Issue Date" class="text-xs"><?= date('M j, Y', strtotime($inv['issue_date'])) ?></td>
                                            <td data-label="Total"><strong class="text-primary"><?= format_currency((float)$inv['total_amount'], $inv['currency']) ?></strong></td>
                                            <td data-label="Status"><?= status_chip($inv['status']) ?></td>
                                            <td data-label="Action" style="text-align: right;"><a href="/invoices/<?= $inv['id'] ?>" class="btn btn-ghost btn-sm py-1">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <!-- Time Logs for this Client -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
                        <h3 style="font-size: var(--text-body); font-weight: var(--weight-bold); display: flex; align-items: center; gap: var(--space-2); margin-bottom: 0;" class="text-primary">
                            <?= clay_icon('clock', 14, 'text-subtle') ?>
                            <span>Recent Time</span>
                        </h3>
                        <span class="text-xs text-subtle"><?= count($timeEntries) ?> entries</span>
                    </div>

                    <?php if (empty($timeEntries)): ?>
                        <p class="text-subtle text-xs italic py-4 text-center m-0">No time logged for this client yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                            <?php foreach ($timeEntries as $te): ?>
                                <div class="panel flex items-center justify-between p-3" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
                                    <div>
                                        <div class="text-xs text-subtle"><?= date('M j, Y', strtotime($te['entry_date'])) ?></div>
                                        <div class="font-medium text-xs text-primary"><?= $this->e($te['task_title']) ?></div>
                                        <?php if (!empty($te['notes'])): ?>
                                            <div class="text-xs text-subtle mt-0.5"><?= $this->e($te['notes']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-xs text-primary"><?= format_duration((int)$te['duration_minutes']) ?></span>
                                        <?php if ($te['is_private']): ?>
                                            <span class="lozenge lozenge-default block mt-1" style="font-size: 10px;">Private</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: Time Log (Full) -->
    <div id="tab-timelog" class="client-tab-content hidden">
        <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
            <h3 style="font-size: var(--text-body); font-weight: var(--weight-bold); margin-bottom: var(--space-4);" class="text-primary">All Time Entries</h3>
            <?php if (empty($timeEntries)): ?>
                <p class="text-subtle text-xs italic py-4 text-center m-0">No time logged yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Task Deliverable</th>
                                <th>Work Notes</th>
                                <th style="text-align: right;">Time Logged</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timeEntries as $te): ?>
                                <tr>
                                    <td data-label="Date" class="text-xs"><?= date('M j, Y', strtotime($te['entry_date'])) ?></td>
                                    <td data-label="Task" class="font-semibold text-primary text-xs"><?= $this->e($te['task_title']) ?></td>
                                    <td data-label="Notes" class="text-subtle text-xs"><?= $this->e($te['notes'] ?: 'Development & design work') ?></td>
                                    <td data-label="Time" style="text-align: right;" class="font-bold text-xs text-primary"><?= format_duration((int)$te['duration_minutes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: Invoices (Full) -->
    <div id="tab-invoices" class="client-tab-content hidden">
        <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
            <div class="flex items-center justify-between mb-4">
                <h3 style="font-size: var(--text-body); font-weight: var(--weight-bold); margin: 0;" class="text-primary">Invoices</h3>
                <a href="/invoices/create?client_id=<?= $client['id'] ?>" class="btn btn-primary btn-sm font-semibold">
                    <?= clay_icon('plus', 12) ?>
                    <span>Create Invoice</span>
                </a>
            </div>
            <?php if (empty($invoices)): ?>
                <p class="text-subtle text-xs italic py-4 text-center m-0">No invoices yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td data-label="Invoice"><a href="/invoices/<?= $inv['id'] ?>" class="font-semibold text-primary"><?= $this->e($inv['invoice_number']) ?></a></td>
                                    <td data-label="Issue Date" class="text-xs"><?= date('M j, Y', strtotime($inv['issue_date'])) ?></td>
                                    <td data-label="Due Date" class="text-xs"><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                                    <td data-label="Total"><strong class="text-primary"><?= format_currency((float)$inv['total_amount'], $inv['currency']) ?></strong></td>
                                    <td data-label="Status"><?= status_chip($inv['status']) ?></td>
                                    <td data-label="Action" style="text-align: right;"><a href="/invoices/<?= $inv['id'] ?>" class="btn btn-ghost btn-sm py-1">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 1023px) {
    .client-summary-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function switchClientTab(event, targetId) {
    if (targetId.startsWith('tab-')) {
        event.preventDefault();
        document.querySelectorAll('.client-tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(targetId)?.classList.remove('hidden');
        document.querySelectorAll('.tab-nav .tab-item').forEach(el => el.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }
}
</script>
