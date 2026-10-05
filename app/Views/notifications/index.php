<div class="notifications-page content-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/dashboard">WorkShift</a>
        <span class="breadcrumb-separator">/</span>
        <span>Notifications</span>
    </div>

    <!-- Page Header Bar -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary m-0">Notifications</h1>
            <p class="text-sm text-subtle mt-1 mb-0">Client approvals, deliverable uploads, payments, and blocker reminders.</p>
        </div>
        <div>
            <form method="POST" action="/notifications/mark-all-read" style="margin: 0;">
                <?= $this->csrf() ?>
                <button type="submit" class="btn btn-secondary btn-sm font-medium">
                    <?= clay_icon('check', 12) ?>
                    <span>Mark All as Read</span>
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <?= empty_state('No notifications yet', 'You will be alerted here when clients approve deliverables, request changes, upload assets, or make payments.', null, null, 'bell') ?>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: var(--space-2);">
            <?php foreach ($notifications as $n): ?>
                <div class="panel flex items-start justify-between gap-4 p-4 <?= !$n['is_read'] ? 'border-primary' : '' ?>" style="background: <?= !$n['is_read'] ? 'var(--color-brand-subtle)' : 'var(--color-bg-surface)' ?>;">
                    <div class="flex items-start gap-3">
                        <div class="stat-icon-bubble" style="width: 32px; height: 32px; margin-bottom: 0; flex-shrink: 0; background: <?= match($n['type']) {
                            'client_approved' => 'var(--color-success-bg); color: var(--color-success); border-color: var(--color-success-border);',
                            'client_changes' => 'var(--blocker-feedback-bg); color: var(--blocker-feedback-text); border-color: var(--blocker-feedback-border);',
                            'client_payment' => 'var(--color-info-bg); color: var(--color-info); border-color: var(--color-info-border);',
                            'client_upload' => 'var(--color-discovery-bg); color: var(--color-discovery-text); border-color: rgba(159, 143, 239, 0.3);',
                            default => 'var(--color-bg-surface-sunken); color: var(--color-brand); border-color: var(--color-border);'
                        } ?>">
                            <?php if ($n['type'] === 'client_approved'): ?>
                                <?= clay_icon('check', 14) ?>
                            <?php elseif ($n['type'] === 'client_changes'): ?>
                                <?= clay_icon('feedback', 14) ?>
                            <?php elseif ($n['type'] === 'client_payment'): ?>
                                <?= clay_icon('payment', 14) ?>
                            <?php elseif ($n['type'] === 'client_upload'): ?>
                                <?= clay_icon('paperclip', 14) ?>
                            <?php else: ?>
                                <?= clay_icon('bell', 14) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-size: var(--text-body); font-weight: var(--weight-semibold); color: <?= !$n['is_read'] ? 'var(--color-brand)' : 'var(--color-text)' ?>;">
                                <?= $this->e($n['title']) ?>
                            </div>
                            <div class="text-xs text-subtle mt-1"><?= $this->e($n['message']) ?></div>
                            <div class="text-xs text-subtlest mt-2"><?= date('M j, Y g:ia', strtotime($n['created_at'])) ?></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <?php if (!empty($n['link_url'])): ?>
                            <a href="<?= $this->e($n['link_url']) ?>" class="clay-btn clay-btn-primary clay-btn-sm font-medium">View</a>
                        <?php endif; ?>
                        <?php if (!$n['is_read']): ?>
                            <form method="POST" action="/notifications/<?= $n['id'] ?>/read" style="margin: 0;">
                                <?= $this->csrf() ?>
                                <button type="submit" class="clay-btn clay-btn-ghost clay-btn-icon-sm" title="Mark as Read" aria-label="Mark as Read">
                                    <?= clay_icon('check', 12) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
