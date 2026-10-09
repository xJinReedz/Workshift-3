<div class="clients-page content-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/dashboard">WorkShift</a>
        <span class="breadcrumb-separator">/</span>
        <span>Clients</span>
    </div>

    <!-- Page Header Bar -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary m-0">Clients CRM</h1>
            <p class="text-sm text-subtle mt-1 mb-0">Manage your client relationships, contract rates, and dedicated boards.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/clients/pipeline" class="btn btn-secondary">
                <?= clay_icon('pipeline', 14) ?>
                <span>Pipeline View</span>
            </a>
            <?php if ($canAdd): ?>
                <a href="/clients/create" class="btn btn-primary">
                    <?= clay_icon('plus', 14) ?>
                    <span>Add Client</span>
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-primary" onclick="WS.modal.warning({ title: 'Client Limit Reached', message: 'You have reached the limit of 3 active clients on the Basic plan.<br><br>Upgrade to WorkShift Pro (₱499/mo) for unlimited clients, or archive a completed client to free up a slot.<br><div class=\'mt-4\'><a href=\'/settings?upgrade=1\' class=\'btn btn-primary btn-sm\'>Upgrade to Pro (₱499/mo)</a></div>', buttonText: 'Close' })">
                    <?= clay_icon('plus', 14) ?>
                    <span>Add Client</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toolbar Row: Filters & Search (12px gap, 16px margin below) -->
    <div class="toolbar">
        <div class="toolbar-left">
            <a href="/clients" class="lozenge <?= empty($currentStage) ? 'lozenge-info' : 'lozenge-default' ?>" style="text-decoration: none;">
                All Clients
            </a>
            <a href="/clients?stage=active" class="lozenge <?= $currentStage === 'active' ? 'lozenge-info' : 'lozenge-default' ?>" style="text-decoration: none;">
                Active (<?= $pipelineCounts['active'] ?? 0 ?>)
            </a>
            <a href="/clients?stage=inquiry" class="lozenge <?= $currentStage === 'inquiry' ? 'lozenge-info' : 'lozenge-default' ?>" style="text-decoration: none;">
                Inquiry (<?= $pipelineCounts['inquiry'] ?? 0 ?>)
            </a>
            <a href="/clients?stage=completed" class="lozenge <?= $currentStage === 'completed' ? 'lozenge-info' : 'lozenge-default' ?>" style="text-decoration: none;">
                Completed (<?= $pipelineCounts['completed'] ?? 0 ?>)
            </a>
            <a href="/clients?stage=archived" class="lozenge <?= $currentStage === 'archived' ? 'lozenge-info' : 'lozenge-default' ?>" style="text-decoration: none;">
                Archived (<?= $pipelineCounts['archived'] ?? 0 ?>)
            </a>
        </div>

        <div class="toolbar-right">
            <form method="GET" action="/clients" class="flex items-center gap-2">
                <?php if (!empty($currentStage)): ?>
                    <input type="hidden" name="stage" value="<?= $this->e($currentStage) ?>">
                <?php endif; ?>
                <div style="position: relative; min-width: 220px;">
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--color-text-subtlest);"><?= clay_icon('search', 14) ?></span>
                    <input type="text" name="search" class="clay-input" style="padding-left: 32px; height: 36px; min-height: 36px; font-size: 0.8125rem;" placeholder="Search clients..." value="<?= $this->e($search ?? '') ?>">
                </div>
                <?php if (!empty($search)): ?>
                    <a href="/clients<?= !empty($currentStage) ? '?stage=' . $currentStage : '' ?>" class="clay-btn clay-btn-ghost clay-btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Clients Table (Table sits inside card with 0 padding and 24px margin below) -->
    <?php if (empty($clients)): ?>
        <?= empty_state(
            'No clients found',
            !empty($search) ? 'No clients match your search query.' : 'You have no clients in this stage yet.',
            ($canAdd && empty($search)) ? '/clients/create' : null,
            ($canAdd && empty($search)) ? 'Add Your First Client' : null,
            'users'
        ) ?>
    <?php else: ?>
        <div class="clay-table-wrap">
            <table class="clay-table clay-table-responsive">
                <thead>
                    <tr>
                        <th style="width: 28%;">Client / Company</th>
                        <th style="width: 14%;">Stage</th>
                        <th style="width: 16%;">Billing</th>
                        <th style="width: 18%;">Board Status</th>
                        <th style="width: 12%;">Portal Link</th>
                        <th style="width: 12%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <td data-label="Client">
                                <div class="flex items-center gap-3">
                                    <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 0.8125rem;">
                                        <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <a href="/clients/<?= $c['id'] ?>" style="font-weight: var(--weight-semibold); color: var(--color-text);">
                                            <?= $this->e($c['name']) ?>
                                        </a>
                                        <?php if (!empty($c['company'])): ?>
                                            <div class="text-xs text-subtle"><?= $this->e($c['company']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Stage">
                                <?= status_chip($c['pipeline_stage']) ?>
                            </td>
                            <td data-label="Billing">
                                <div class="text-sm">
                                    <strong><?= ($c['billing_type'] ?? 'hourly') === 'hourly' ? format_currency((float)($c['rate'] ?? $c['hourly_rate'] ?? 0)) . '/hr' : format_currency((float)($c['rate'] ?? $c['hourly_rate'] ?? 0)) ?></strong>
                                    <span class="text-subtle text-xs block"><?= ucfirst((string)($c['billing_type'] ?? 'hourly')) ?></span>
                                </div>
                            </td>
                            <td data-label="Board Status">
                                <div class="text-xs">
                                    <span><?= $c['total_tasks'] ?> tasks</span>
                                    <?php if ($c['blocked_on_client_count'] > 0): ?>
                                        <div class="mt-1" style="color: var(--blocker-feedback-text); font-weight: var(--weight-semibold); display: flex; align-items: center; gap: 4px;">
                                            <?= clay_icon('feedback', 12) ?>
                                            <span><?= $c['blocked_on_client_count'] ?> waiting on client</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Portal Link">
                                <?php if (!empty($c['portal_token'])): ?>
                                    <button type="button" class="clay-btn clay-btn-secondary clay-btn-sm"
                                            onclick="copyPortalLink('<?= $appConfig['app']['url'] ?>/portal/<?= $c['portal_token'] ?>', this)">
                                        <?= clay_icon('link', 12) ?>
                                        <span>Copy</span>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions" style="text-align: right;">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/boards/<?= $c['board_id'] ?>" class="clay-btn clay-btn-primary clay-btn-sm" title="Open Client Board">
                                        <?= clay_icon('kanban', 12) ?>
                                        <span>Board</span>
                                    </a>
                                    <a href="/clients/<?= $c['id'] ?>" class="clay-btn clay-btn-ghost clay-btn-sm" title="View Profile">
                                        <span>Profile</span>
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
