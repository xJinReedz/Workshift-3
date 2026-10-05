<div class="content-wrapper" style="max-width: 600px; margin: 0 auto;">
    <div class="clay-card text-center p-8">
        <div class="empty-icon-bubble mx-auto mb-4" style="width: 56px; height: 56px; color: var(--color-success); background: var(--color-success-bg); border-color: var(--color-success-border);">
            <?= clay_icon('check', 28, '', 2) ?>
        </div>
        <h2 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin-bottom: var(--space-2);">Payment Confirmed!</h2>
        <p class="text-sm text-subtle mb-6">Thank you. Your payment of <strong><?= format_currency((float)$invoice['total_amount'], $invoice['currency']) ?></strong> for Invoice <strong><?= $this->e($invoice['invoice_number']) ?></strong> has been received and verified.</p>

        <div class="panel p-4 text-left text-xs mb-6" style="background: var(--color-bg-surface-sunken);">
            <div class="flex items-center justify-between py-1">
                <span class="text-subtle font-medium">Transaction Reference:</span>
                <strong class="tabular-nums"><?= $this->e($referenceNumber) ?></strong>
            </div>
            <div class="flex items-center justify-between py-1">
                <span class="text-subtle font-medium">Payment Gateway:</span>
                <span>Maya Business / QR Ph</span>
            </div>
            <div class="flex items-center justify-between py-1">
                <span class="text-subtle font-medium">Settlement Date:</span>
                <span><?= date('M j, Y g:ia') ?></span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: var(--space-3);">
            <a href="/portal/<?= $token ?>" class="clay-btn clay-btn-primary font-medium" style="width: 100%;">
                Return to Your Client Board
            </a>
            <a href="/invoices/<?= $invoice['id'] ?>/print?portal_token=<?= $token ?>" target="_blank" class="clay-btn clay-btn-secondary font-medium text-center" style="width: 100%;">
                <?= clay_icon('printer', 14) ?>
                <span>Download Official Receipt (PDF)</span>
            </a>
        </div>
    </div>
</div>
