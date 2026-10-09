<div class="pipeline-page content-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/clients">Clients</a>
        <span class="breadcrumb-separator">/</span>
        <span>Pipeline</span>
    </div>

    <!-- Page Header Bar -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary m-0">Client Pipeline</h1>
            <p class="text-sm text-subtle mt-1 mb-0">Track prospective and active clients across your sales and delivery pipeline.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/clients" class="btn btn-secondary">
                <?= clay_icon('users', 14) ?>
                <span>Table View</span>
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

    <!-- Pipeline Columns Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4); align-items: start;">
        <?php
        $stageMeta = [
            'inquiry' => ['label' => 'Inquiry / Lead', 'desc' => 'Prospective contracts'],
            'active' => ['label' => 'Active Contract', 'desc' => 'Current ongoing work'],
            'completed' => ['label' => 'Completed', 'desc' => 'Delivered & finalized'],
            'archived' => ['label' => 'Archived', 'desc' => 'Past records'],
        ];
        ?>

        <?php foreach ($pipeline as $stageKey => $stageClients): ?>
            <?php $meta = $stageMeta[$stageKey] ?? ['label' => ucfirst((string)$stageKey), 'desc' => '']; ?>
            <div class="panel" style="background: var(--color-bg-surface-sunken); padding: var(--space-4);">
                <div class="flex items-center justify-between pb-3 mb-3" style="border-bottom: 1px solid var(--color-border);">
                    <div>
                        <div class="caption-uppercase"><?= $meta['label'] ?></div>
                        <div class="text-xs text-subtle"><?= $meta['desc'] ?></div>
                    </div>
                    <span class="column-count-badge"><?= count($stageClients) ?></span>
                </div>

                <div style="display: flex; flex-direction: column; gap: var(--space-3); min-height: 120px;">
                    <?php if (empty($stageClients)): ?>
                        <div class="text-xs text-subtle text-center py-8 italic">
                            No clients in this stage
                        </div>
                    <?php else: ?>
                        <?php foreach ($stageClients as $c): ?>
                            <div class="task-card" data-client-id="<?= $c['id'] ?>">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div style="font-weight: var(--weight-semibold); font-size: var(--text-body);">
                                        <a href="/clients/<?= $c['id'] ?>"><?= $this->e($c['name']) ?></a>
                                    </div>
                                    <span class="lozenge lozenge-default" style="font-size: 10px;">
                                        <?= ($c['billing_type'] ?? 'hourly') === 'hourly' ? format_currency((float)($c['rate'] ?? $c['hourly_rate'] ?? 0)) . '/h' : format_currency((float)($c['rate'] ?? $c['hourly_rate'] ?? 0)) ?>
                                    </span>
                                </div>

                                <?php if (!empty($c['company'])): ?>
                                    <div class="text-xs text-subtle mb-2"><?= $this->e($c['company']) ?></div>
                                <?php endif; ?>

                                <?php if ($c['blocked_on_client_count'] > 0): ?>
                                    <div class="text-xs mb-3 flex items-center gap-1" style="color: var(--blocker-feedback-text); font-weight: var(--weight-semibold);">
                                        <?= clay_icon('feedback', 12) ?>
                                        <span><?= $c['blocked_on_client_count'] ?> waiting on client</span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between gap-2 pt-2 mt-2" style="border-top: 1px solid var(--color-border);">
                                    <a href="/boards/<?= $c['board_id'] ?>" class="clay-btn clay-btn-ghost clay-btn-sm text-xs font-medium" style="height: 28px; padding: 0 8px;">
                                        <?= clay_icon('kanban', 12) ?> Board
                                    </a>

                                    <!-- Stage Quick Switch Form -->
                                    <form method="POST" action="/clients/<?= $c['id'] ?>/stage" style="margin: 0;">
                                        <?= $this->csrf() ?>
                                        <select name="stage" class="clay-select" style="height: 28px; min-height: 28px; padding: 0 24px 0 8px; font-size: 0.75rem;" onchange="this.form.submit()">
                                            <option value="inquiry" <?= $stageKey === 'inquiry' ? 'selected' : '' ?>>Inquiry</option>
                                            <option value="active" <?= $stageKey === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="completed" <?= $stageKey === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="archived" <?= $stageKey === 'archived' ? 'selected' : '' ?>>Archived</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
