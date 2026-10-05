<div class="dashboard-page">
    <!-- Breadcrumb & Header Row -->
    <div class="breadcrumb">
        <span>WorkShift</span>
        <span class="breadcrumb-separator">/</span>
        <span>Dashboard</span>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold">Welcome back, <?= $this->e($user['name']) ?></h1>
            <p class="text-sm text-subtle" style="margin-bottom: 0;">Here is the state of your client projects and blocked deliverables today.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/clients/create" class="clay-btn clay-btn-primary">
                <?= clay_icon('plus', 14) ?>
                <span>Add Client</span>
            </a>
            <a href="/invoices/create" class="clay-btn clay-btn-secondary">
                <?= clay_icon('invoice', 14) ?>
                <span>New Invoice</span>
            </a>
        </div>
    </div>

    <!-- Responsive Grid of Stat Tiles (20px internal padding) -->
    <div class="dashboard-stats-grid">
        <!-- Active Clients Tile -->
        <div class="clay-stat-tile">
            <div class="stat-icon-bubble">
                <?= clay_icon('users', 18) ?>
            </div>
            <div class="stat-val"><?= $activeClientsCount ?></div>
            <div class="stat-label">Active Clients</div>
            <div class="text-xs text-subtle mt-1">
                <?php if ($user['plan'] === 'basic'): ?>
                    <?= $activeClientsCount ?> / 3 on Basic plan
                <?php else: ?>
                    Unlimited on Pro
                <?php endif; ?>
            </div>
        </div>

        <!-- Blocked on Client -->
        <div class="clay-stat-tile" style="border-left: 3px solid var(--blocker-feedback-border);">
            <div class="stat-icon-bubble" style="background: var(--blocker-feedback-bg); color: var(--blocker-feedback-text); border-color: var(--blocker-feedback-border);">
                <?= clay_icon('feedback', 18) ?>
            </div>
            <div class="stat-val" style="color: var(--blocker-feedback-text);"><?= count($blockedSummary['waitingOnClient']) ?></div>
            <div class="stat-label">Blocked on Client</div>
            <div class="text-xs text-subtle mt-1">Awaiting client sign-off or assets</div>
        </div>

        <!-- Blocked on You -->
        <div class="clay-stat-tile" style="border-left: 3px solid var(--color-brand);">
            <div class="stat-icon-bubble" style="background: var(--color-brand-subtle); color: var(--color-brand); border-color: var(--color-brand);">
                <?= clay_icon('alert-circle', 18) ?>
            </div>
            <div class="stat-val text-brand"><?= count($blockedSummary['waitingOnYou']) ?></div>
            <div class="stat-label">Blocked on You</div>
            <div class="text-xs text-subtle mt-1">Revisions or next deliverables</div>
        </div>

        <!-- Hours This Week -->
        <div class="clay-stat-tile">
            <div class="stat-icon-bubble">
                <?= clay_icon('clock', 18) ?>
            </div>
            <div class="stat-val"><?= $hoursThisWeek ?>h</div>
            <div class="stat-label">Hours This Week</div>
            <div class="text-xs text-subtle mt-1">Tracked project work</div>
        </div>

        <!-- Unpaid Invoices -->
        <div class="clay-stat-tile">
            <div class="stat-icon-bubble">
                <?= clay_icon('invoice', 18) ?>
            </div>
            <div class="stat-val"><?= format_currency($invoiceStats['unpaid_total']) ?></div>
            <div class="stat-label">Unpaid Invoices</div>
            <div class="text-xs mt-1 <?= $invoiceStats['overdue_count'] > 0 ? 'text-danger font-bold' : 'text-subtle' ?>">
                <?= $invoiceStats['unpaid_count'] ?> pending (<?= $invoiceStats['overdue_count'] ?> overdue)
            </div>
        </div>
    </div>

    <!-- Active Stalled Blockers Section (Signature Accountability Engine) -->
    <div class="clay-card mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
            <div>
                <h2 style="font-size: var(--text-h3); font-weight: var(--weight-semibold); display: flex; align-items: center; gap: var(--space-2); margin-bottom: 2px;">
                    <?= clay_icon('alert-circle', 16, 'text-brand') ?>
                    <span>Tasks Currently Blocked</span>
                </h2>
                <p class="text-xs text-subtle" style="margin-bottom: 0;">Plain accountability: knowing exactly why work stopped and whose turn it is.</p>
            </div>
            <div class="board-summary-strip">
                <span class="flex items-center gap-1" style="color: var(--blocker-feedback-text);">
                    <strong><?= count($blockedSummary['waitingOnClient']) ?></strong> waiting on client
                </span>
                <span style="opacity: 0.3;">|</span>
                <span class="flex items-center gap-1 text-brand">
                    <strong><?= count($blockedSummary['waitingOnYou']) ?></strong> waiting on you
                </span>
            </div>
        </div>

        <?php if (empty($blockedSummary['waitingOnClient']) && empty($blockedSummary['waitingOnYou'])): ?>
            <div class="text-center py-8">
                <div class="empty-icon-bubble mx-auto mb-3" style="color: var(--color-success);">
                    <?= clay_icon('check', 24) ?>
                </div>
                <h3 style="font-size: var(--text-body-lg); font-weight: var(--weight-semibold); margin-bottom: 4px;">No blocked tasks right now!</h3>
                <p class="text-sm text-subtle">All deliverables and milestones are moving forward smoothly across your client boards.</p>
            </div>
        <?php else: ?>
            <div class="waiting-split-grid">
                <!-- Waiting on Client -->
                <div class="panel" style="background: var(--color-bg-surface-sunken);">
                    <h3 style="font-size: var(--text-body); font-weight: var(--weight-semibold); margin-bottom: var(--space-3); display: flex; align-items: center; gap: var(--space-2); color: var(--blocker-feedback-text);">
                        <?= clay_icon('feedback', 14) ?>
                        <span>Waiting on Client (<?= count($blockedSummary['waitingOnClient']) ?>)</span>
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                        <?php foreach ($blockedSummary['waitingOnClient'] as $item): ?>
                            <div class="task-card">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="font-bold text-xs text-brand"><?= $this->e($item['client_company'] ?: $item['client_name']) ?></span>
                                    <?= blocker_badge($item) ?>
                                </div>
                                <h4 style="font-size: var(--text-body); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">
                                    <a href="/boards/<?= $item['board_id'] ?>?task_id=<?= $item['id'] ?>" onclick="openTaskSlideover(<?= $item['id'] ?>); return false;">
                                        <?= $this->e($item['title']) ?>
                                    </a>
                                </h4>
                                <div class="text-xs text-subtle mb-3" style="background: var(--color-bg-surface-sunken); padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                                    <strong>Blocker:</strong> <?= $this->e($item['reason']) ?>
                                </div>
                                <div class="flex items-center justify-end">
                                    <a href="/boards/<?= $item['board_id'] ?>" class="clay-btn clay-btn-ghost clay-btn-sm font-medium">
                                        Open Board <?= clay_icon('chevron-right', 12) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($blockedSummary['waitingOnClient'])): ?>
                            <div class="text-xs text-subtle text-center py-4">No tasks waiting on client input.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Waiting on You -->
                <div class="panel" style="background: var(--color-bg-surface-sunken);">
                    <h3 style="font-size: var(--text-body); font-weight: var(--weight-semibold); margin-bottom: var(--space-3); display: flex; align-items: center; gap: var(--space-2); color: var(--color-brand);">
                        <?= clay_icon('alert-circle', 14) ?>
                        <span>Waiting on You (<?= count($blockedSummary['waitingOnYou']) ?>)</span>
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                        <?php foreach ($blockedSummary['waitingOnYou'] as $item): ?>
                            <div class="task-card">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="font-bold text-xs text-brand"><?= $this->e($item['client_company'] ?: $item['client_name']) ?></span>
                                    <?= blocker_badge($item) ?>
                                </div>
                                <h4 style="font-size: var(--text-body); font-weight: var(--weight-medium); margin-bottom: var(--space-2);">
                                    <a href="/boards/<?= $item['board_id'] ?>?task_id=<?= $item['id'] ?>" onclick="openTaskSlideover(<?= $item['id'] ?>); return false;">
                                        <?= $this->e($item['title']) ?>
                                    </a>
                                </h4>
                                <div class="text-xs text-subtle mb-3" style="background: var(--color-bg-surface-sunken); padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                                    <strong>Action Needed:</strong> <?= $this->e($item['reason']) ?>
                                </div>
                                <div class="flex items-center justify-end">
                                    <a href="/boards/<?= $item['board_id'] ?>" class="clay-btn clay-btn-ghost clay-btn-sm font-medium">
                                        Open Board <?= clay_icon('chevron-right', 12) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($blockedSummary['waitingOnYou'])): ?>
                            <div class="text-xs text-subtle text-center py-4">No tasks waiting on your action.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Two-Column Section: Client Workspaces & Recent Invoices -->
    <div class="dashboard-main-grid">
        <!-- Client Workspaces -->
        <div class="clay-card">
            <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
                <h3 style="font-size: var(--text-h3); font-weight: var(--weight-semibold); display: flex; align-items: center; gap: var(--space-2); margin-bottom: 0;">
                    <?= clay_icon('users', 16, 'text-brand') ?>
                    <span>Client Workspaces</span>
                </h3>
                <a href="/clients" class="text-xs font-semibold text-brand">View All &rarr;</a>
            </div>

            <?php if (empty($recentClients)): ?>
                <?= empty_state('No clients yet', 'Add your first client to generate their dedicated board and portal link.', '/clients/create', 'Add Client', 'users') ?>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                    <?php foreach ($recentClients as $c): ?>
                        <div class="panel flex items-center justify-between flex-wrap gap-3 p-3">
                            <div class="flex items-center gap-3">
                                <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 0.8125rem;">
                                    <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: var(--weight-semibold); font-size: var(--text-body);">
                                        <a href="/clients/<?= $c['id'] ?>"><?= $this->e($c['name']) ?></a>
                                        <?php if (!empty($c['company'])): ?>
                                            <span class="text-xs text-subtle font-normal">(<?= $this->e($c['company']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-subtle mt-1">
                                        <?= status_chip($c['pipeline_stage']) ?>
                                        <span>·</span>
                                        <span><?= $c['billing_type'] === 'hourly' ? format_currency((float)$c['rate']) . '/hr' : format_currency((float)$c['rate']) . ' fixed' ?></span>
                                        <?php if ($c['blocked_on_client_count'] > 0): ?>
                                            <span>·</span>
                                            <span style="color: var(--blocker-feedback-text); font-weight: var(--weight-semibold);"><?= $c['blocked_on_client_count'] ?> waiting on client</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <a href="/boards/<?= $c['board_id'] ?>" class="clay-btn clay-btn-secondary clay-btn-sm font-medium">
                                    <?= clay_icon('kanban', 14) ?>
                                    <span>Board</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Invoices & Billing Summary -->
        <div class="clay-card">
            <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
                <h3 style="font-size: var(--text-h3); font-weight: var(--weight-semibold); display: flex; align-items: center; gap: var(--space-2); margin-bottom: 0;">
                    <?= clay_icon('invoice', 16, 'text-brand') ?>
                    <span>Recent Invoices</span>
                </h3>
                <a href="/invoices" class="text-xs font-semibold text-brand">View All &rarr;</a>
            </div>

            <?php if (empty($recentInvoices)): ?>
                <?= empty_state('No invoices yet', 'Create your first invoice to collect payments via Maya or bank.', '/invoices/create', 'New Invoice', 'invoice') ?>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                    <?php foreach ($recentInvoices as $inv): ?>
                        <div class="panel flex items-center justify-between gap-3 p-3">
                            <div>
                                <div style="font-weight: var(--weight-semibold); font-size: var(--text-body);">
                                    <a href="/invoices/<?= $inv['id'] ?>"><?= $this->e($inv['invoice_number']) ?></a>
                                </div>
                                <div class="text-xs text-subtle">
                                    <?= $this->e($inv['client_name']) ?> · Due <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold mb-1"><?= format_currency((float)$inv['total_amount'], $inv['currency']) ?></div>
                                <?= status_chip($inv['status']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
