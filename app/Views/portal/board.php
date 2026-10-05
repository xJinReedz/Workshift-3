<div class="portal-board-wrapper" data-portal-token="<?= $token ?>" data-board-id="<?= $board['id'] ?>">
    <!-- Friendly "Here's what's waiting on you" Section -->
    <div class="panel mb-6" style="background: var(--color-bg-surface); padding: var(--space-5);">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin-bottom: 2px;">
                    Welcome, <?= $this->e($client['name']) ?>
                </h1>
                <p class="text-sm text-subtle" style="margin-bottom: 0;">
                    Your project workspace with <strong><?= $this->e($client['freelancer_company'] ?: $client['freelancer_name']) ?></strong>.
                </p>
            </div>
            <div class="board-summary-strip">
                <span class="flex items-center gap-1" style="color: var(--blocker-feedback-text); font-weight: var(--weight-semibold);">
                    <?= clay_icon('feedback', 12) ?>
                    <strong><?= $waitingOnClientCount ?></strong> waiting on your input
                </span>
                <span style="opacity: 0.3;">|</span>
                <span class="flex items-center gap-1 text-brand font-medium">
                    <?= clay_icon('clock', 12) ?>
                    <strong><?= $waitingOnYouCount ?></strong> in progress with freelancer
                </span>
            </div>
        </div>

        <?php if ($waitingOnClientCount > 0): ?>
            <div style="margin-top: var(--space-4); padding-top: var(--space-3); border-top: 1px solid var(--color-border);" class="flex items-center justify-between flex-wrap gap-3">
                <div class="text-xs text-subtle">
                    <strong style="color: var(--blocker-feedback-text);">Action Required:</strong> Review deliverables or provide feedback so the next milestone can proceed.
                </div>
                <?php if (!empty($schedulingLink)): ?>
                    <a href="<?= $this->e($schedulingLink) ?>" target="_blank" rel="noopener" class="clay-btn clay-btn-primary clay-btn-sm">
                        <?= clay_icon('scheduling', 12) ?>
                        <span>Schedule Review Call</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Portal Navigation Tabs (Underlined Jira-style) -->
    <div class="tab-nav mb-6">
        <button type="button" class="tab-item active portal-tab-btn" onclick="switchPortalTab(event, 'board')">
            <?= clay_icon('kanban', 14) ?>
            <span>Project Board</span>
        </button>
        <?php if (!empty($timeEntries)): ?>
            <button type="button" class="tab-item portal-tab-btn" onclick="switchPortalTab(event, 'timelog')">
                <?= clay_icon('clock', 14) ?>
                <span>Time Log (<?= count($timeEntries) ?>)</span>
            </button>
        <?php endif; ?>
        <?php if (!empty($invoices)): ?>
            <button type="button" class="tab-item portal-tab-btn" onclick="switchPortalTab(event, 'invoices')">
                <?= clay_icon('invoice', 14) ?>
                <span>Invoices (<?= count($invoices) ?>)</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- TAB 1: Board Canvas -->
    <div id="portal-tab-board" class="portal-tab-panel">
        <div class="board-canvas" style="padding: 0; min-height: 480px;">
            <?php foreach ($stages as $stage): ?>
                <?php
                $stageTasks = $tasksByStage[$stage['id']] ?? [];
                $isReview = (bool)$stage['is_review_stage'];
                $isDone = (bool)$stage['is_done_stage'];
                ?>
                <div class="board-column" style="flex: 0 0 310px; width: 310px;">
                    <div class="board-column-header">
                        <div class="column-title">
                            <span><?= $this->e($stage['title']) ?></span>
                            <span class="column-count-badge"><?= count($stageTasks) ?></span>
                        </div>
                        <?php if ($isReview): ?>
                            <span class="lozenge lozenge-warning">Sign-off</span>
                        <?php elseif ($isDone): ?>
                            <span class="lozenge lozenge-success">Done</span>
                        <?php endif; ?>
                    </div>

                    <div class="board-task-list">
                        <?php if (empty($stageTasks)): ?>
                            <div class="text-xs text-subtle p-4 text-center italic">No tasks in this stage.</div>
                        <?php else: ?>
                            <?php foreach ($stageTasks as $task): ?>
                                <div class="task-card"
                                     onclick="openTaskModal(<?= $task['id'] ?>, '<?= $token ?>')">
                                    <div class="task-card-header">
                                        <h4 class="task-card-title"><?= $this->e($task['title']) ?></h4>
                                    </div>

                                    <!-- Blocker Lozenge -->
                                    <?php if (!empty($task['blocker_type'])): ?>
                                        <div class="mb-2">
                                            <?= blocker_badge([
                                                'type' => $task['blocker_type'],
                                                'reason' => $task['blocker_reason'],
                                                'waiting_on' => $task['blocker_waiting_on'],
                                                'waiting_since' => $task['blocker_waiting_since'],
                                            ]) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Review Status Tag -->
                                    <?php if ($task['review_status'] === 'approved'): ?>
                                        <div class="mb-2">
                                            <span class="lozenge lozenge-success">✓ Approved</span>
                                        </div>
                                    <?php elseif ($task['review_status'] === 'changes_requested'): ?>
                                        <div class="mb-2">
                                            <span class="lozenge lozenge-danger">↻ Revisions Requested</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="task-card-footer">
                                        <div>
                                            <?php if (!empty($task['due_date'])): ?>
                                                <span class="text-xs text-subtle flex items-center gap-1">
                                                    <?= clay_icon('scheduling', 12) ?>
                                                    <span><?= date('M j', strtotime($task['due_date'])) ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="task-card-meta">
                                            <?php if ((int)$task['file_count'] > 0): ?>
                                                <span class="flex items-center gap-1 text-xs text-subtle">
                                                    <?= clay_icon('paperclip', 12) ?>
                                                    <span><?= $task['file_count'] ?></span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ((int)$task['comment_count'] > 0): ?>
                                                <span class="flex items-center gap-1 text-xs text-subtle">
                                                    <?= clay_icon('message-square', 12) ?>
                                                    <span><?= $task['comment_count'] ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Action Button if in review stage -->
                                    <?php if ($isReview && $task['review_status'] !== 'approved'): ?>
                                        <div style="margin-top: var(--space-3); padding-top: var(--space-2); border-top: 1px solid var(--color-border);" onclick="event.stopPropagation()">
                                            <button type="button" class="clay-btn clay-btn-success clay-btn-sm w-full font-medium" style="width: 100%;" onclick="portalReviewTask(<?= $task['id'] ?>, 'approve')">
                                                <?= clay_icon('check', 12) ?>
                                                <span>Approve Deliverable</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB 2: Time Log -->
    <?php if (!empty($timeEntries)): ?>
        <div id="portal-tab-timelog" class="portal-tab-panel hidden">
            <div class="clay-card">
                <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
                    <h3 style="font-size: var(--text-body); font-weight: var(--weight-semibold); margin-bottom: 0;">Project Time Entries</h3>
                    <span class="text-xs text-subtle">Transparent log of hours dedicated to your project</span>
                </div>

                <div class="clay-table-wrap">
                    <table class="clay-table clay-table-responsive">
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
                                    <td data-label="Task" style="font-weight: var(--weight-semibold);"><?= $this->e($te['task_title']) ?></td>
                                    <td data-label="Notes" class="text-subtle text-xs"><?= $this->e($te['notes'] ?: 'Development & design work') ?></td>
                                    <td data-label="Time" style="text-align: right;" class="font-semibold"><?= format_duration((int)$te['duration_minutes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 3: Invoices -->
    <?php if (!empty($invoices)): ?>
        <div id="portal-tab-invoices" class="portal-tab-panel hidden">
            <div class="clay-card">
                <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--color-border);">
                    <h3 style="font-size: var(--text-body); font-weight: var(--weight-semibold); margin-bottom: 0;">Invoices & Receipts</h3>
                    <span class="text-xs text-subtle">Settled and outstanding project balances</span>
                </div>

                <div class="clay-table-wrap">
                    <table class="clay-table clay-table-responsive">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td data-label="Invoice #" style="font-weight: var(--weight-semibold); color: var(--color-brand);"><?= $this->e($inv['invoice_number']) ?></td>
                                    <td data-label="Issue Date" class="text-xs text-subtle"><?= date('M j, Y', strtotime($inv['issue_date'])) ?></td>
                                    <td data-label="Due Date" class="text-xs text-subtle"><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                                    <td data-label="Total" class="font-semibold"><?= format_currency((float)$inv['total_amount'], $inv['currency']) ?></td>
                                    <td data-label="Status">
                                        <?= status_chip($inv['status']) ?>
                                    </td>
                                    <td data-label="Actions" style="text-align: right;">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="/invoices/<?= $inv['id'] ?>/print?portal_token=<?= $token ?>" target="_blank" class="clay-btn clay-btn-secondary clay-btn-sm font-medium">
                                                <?= clay_icon('printer', 12) ?>
                                                <span>Print</span>
                                            </a>
                                            <?php if ($inv['status'] !== 'paid' && $canPayOnline): ?>
                                                <a href="/portal/<?= $token ?>/pay/<?= $inv['id'] ?>" class="clay-btn clay-btn-primary clay-btn-sm font-medium">
                                                    <?= clay_icon('credit-card', 12) ?>
                                                    <span>Pay Online</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function switchPortalTab(event, tabName) {
    document.querySelectorAll('.portal-tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.portal-tab-btn').forEach(b => b.classList.remove('active'));

    const activePanel = document.getElementById('portal-tab-' + tabName);
    if (activePanel) activePanel.classList.remove('hidden');

    event.currentTarget.classList.add('active');
}
</script>
