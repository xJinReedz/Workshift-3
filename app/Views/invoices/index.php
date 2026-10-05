<div class="content-wrapper invoices-page">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/dashboard">WorkShift</a>
        <span class="breadcrumb-separator">/</span>
        <span>Invoices</span>
    </div>

    <!-- Page Header Bar -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold">Invoices & Billing</h1>
            <p class="text-sm text-subtle" style="margin-bottom: 0;">Track project payments, milestones, and billable hours in Philippine Peso (₱).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/invoices/create" class="clay-btn clay-btn-primary">
                <?= clay_icon('plus', 14) ?>
                <span>Create Invoice</span>
            </a>
        </div>
    </div>

    <!-- Billing Summary Stats Grid -->
    <div class="dashboard-stats-grid mb-6">
        <div class="clay-stat-tile">
            <div class="stat-icon-bubble">
                <?= clay_icon('invoice', 18) ?>
            </div>
            <div class="stat-val"><?= format_currency($stats['unpaid_total']) ?></div>
            <div class="stat-label">Unpaid Invoices</div>
            <div class="text-xs text-subtle mt-1"><?= $stats['unpaid_count'] ?> pending</div>
        </div>

        <div class="clay-stat-tile" style="border-left: 3px solid var(--color-danger);">
            <div class="stat-icon-bubble" style="background: var(--color-danger-bg); color: var(--color-danger); border-color: var(--color-danger);">
                <?= clay_icon('alert-circle', 18) ?>
            </div>
            <div class="stat-val text-danger"><?= format_currency($stats['overdue_total']) ?></div>
            <div class="stat-label">Overdue Amount</div>
            <div class="text-xs text-danger mt-1 font-bold"><?= $stats['overdue_count'] ?> past due date</div>
        </div>

        <div class="clay-stat-tile">
            <div class="stat-icon-bubble" style="color: var(--color-success);">
                <?= clay_icon('check', 18) ?>
            </div>
            <div class="stat-val" style="color: var(--color-success);"><?= format_currency($stats['paid_total']) ?></div>
            <div class="stat-label">Paid to Date</div>
            <div class="text-xs text-subtle mt-1">Settled collections</div>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="tab-nav mb-6">
        <a href="/invoices" class="tab-item <?= empty($currentStatus) ? 'active' : '' ?>">All Invoices</a>
        <a href="/invoices?status=draft" class="tab-item <?= $currentStatus === 'draft' ? 'active' : '' ?>">Draft</a>
        <a href="/invoices?status=sent" class="tab-item <?= $currentStatus === 'sent' ? 'active' : '' ?>">Sent</a>
        <a href="/invoices?status=overdue" class="tab-item <?= $currentStatus === 'overdue' ? 'active' : '' ?>">Overdue</a>
        <a href="/invoices?status=paid" class="tab-item <?= $currentStatus === 'paid' ? 'active' : '' ?>">Paid</a>
    </div>

    <!-- Invoices Table -->
    <?php if (empty($invoices)): ?>
        <?= empty_state('No invoices found', 'You have not created any invoices in this category.', '/invoices/create', 'Create First Invoice', 'invoice') ?>
    <?php else: ?>
        <div class="clay-table-wrap">
            <table class="clay-table clay-table-responsive">
                <thead>
                    <tr>
                        <th style="width: 18%;">Invoice #</th>
                        <th style="width: 26%;">Client</th>
                        <th style="width: 14%;">Issue Date</th>
                        <th style="width: 14%;">Due Date</th>
                        <th style="width: 14%;">Amount</th>
                        <th style="width: 14%;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td data-label="Invoice #">
                                <a href="/invoices/<?= $inv['id'] ?>" style="font-weight: var(--weight-semibold); color: var(--color-brand);">
                                    <?= $this->e($inv['invoice_number']) ?>
                                </a>
                            </td>
                            <td data-label="Client">
                                <span style="font-weight: var(--weight-medium);"><?= $this->e($inv['client_name']) ?></span>
                                <?php if (!empty($inv['client_company'])): ?>
                                    <span class="text-xs text-subtle block"><?= $this->e($inv['client_company']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Issue Date" class="text-xs text-subtle"><?= date('M j, Y', strtotime($inv['issue_date'])) ?></td>
                            <td data-label="Due Date" class="text-xs">
                                <span class="<?= $inv['status'] === 'overdue' ? 'text-danger font-bold' : 'text-subtle' ?>">
                                    <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                                </span>
                            </td>
                            <td data-label="Amount">
                                <span class="font-semibold text-sm tabular-nums"><?= format_currency((float)$inv['total_amount'], $inv['currency']) ?></span>
                            </td>
                            <td data-label="Status">
                                <?= status_chip($inv['status']) ?>
                            </td>
                            <td data-label="Actions" style="text-align: right;">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/invoices/<?= $inv['id'] ?>" class="clay-btn clay-btn-primary clay-btn-sm font-medium">View</a>
                                    <a href="/invoices/<?= $inv['id'] ?>/print" target="_blank" class="clay-btn clay-btn-ghost clay-btn-sm font-medium" title="Print/PDF">
                                        <?= clay_icon('printer', 14) ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
