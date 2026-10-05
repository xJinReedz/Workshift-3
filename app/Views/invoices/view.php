<div class="content-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/invoices">Invoices</a>
        <span class="breadcrumb-separator">/</span>
        <span><?= $this->e($invoice['invoice_number']) ?></span>
    </div>

    <!-- Header with Action Buttons -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold flex items-center gap-3">
                <span><?= $this->e($invoice['invoice_number']) ?></span>
                <?= status_chip($invoice['status']) ?>
            </h1>
            <p class="text-xs text-subtle font-medium mt-1">
                Issued for <strong><?= $this->e($invoice['client_name']) ?></strong> on <?= date('M j, Y', strtotime($invoice['issue_date'])) ?>
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <?php if ($invoice['status'] !== 'paid'): ?>
                <form method="POST" action="/invoices/<?= $invoice['id'] ?>/status" style="margin: 0;">
                    <?= $this->csrf() ?>
                    <input type="hidden" name="status" value="paid">
                    <button type="submit" 
                            class="clay-btn clay-btn-success"
                            data-confirm-title="Mark Invoice Paid"
                            data-confirm-message="Are you sure you want to mark invoice <?= $this->e($invoice['invoice_number']) ?> as Paid in full? This records payment and clears outstanding balance."
                            data-confirm-text="Mark as Paid"
                            data-confirm-variant="success">
                        <?= clay_icon('check', 14) ?>
                        <span>Mark as Paid</span>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($invoice['status'] === 'draft'): ?>
                <form method="POST" action="/invoices/<?= $invoice['id'] ?>/status" style="margin: 0;">
                    <?= $this->csrf() ?>
                    <input type="hidden" name="status" value="sent">
                    <button type="submit" 
                            class="clay-btn clay-btn-primary"
                            data-confirm-title="Send Invoice"
                            data-confirm-message="Mark invoice <?= $this->e($invoice['invoice_number']) ?> as Sent to <?= $this->e($invoice['client_name']) ?>?"
                            data-confirm-text="Send Invoice"
                            data-confirm-variant="primary">
                        <span>Send to Client</span>
                    </button>
                </form>
            <?php endif; ?>

            <a href="/invoices/<?= $invoice['id'] ?>/print" target="_blank" class="clay-btn clay-btn-secondary">
                <?= clay_icon('printer', 14) ?>
                <span>Print / PDF</span>
            </a>
        </div>
    </div>

    <!-- Client Online Payment Link Box (Pro feature) -->
    <?php if ($invoice['freelancer_plan'] === 'pro' && !empty($invoice['portal_token'])): ?>
        <div class="panel mb-6" style="background: var(--color-brand-subtle); border-color: var(--color-border); padding: var(--space-4);">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <span class="stat-icon-bubble" style="width: 32px; height: 32px; margin-bottom: 0; background: var(--color-bg-surface); color: var(--color-brand);">
                        <?= clay_icon('credit-card', 16) ?>
                    </span>
                    <div>
                        <div style="font-weight: var(--weight-semibold); font-size: var(--text-body);">Online Checkout Link (Maya QR Ph & Cards)</div>
                        <div class="text-xs text-subtle">Clients can settle this invoice directly via Maya or debit/credit cards:</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <input type="text" readonly id="pay-link-input" class="clay-input" value="<?= $this->e($portalPayUrl) ?>" style="height: 32px; min-height: 32px; font-size: 0.8125rem; width: 260px; max-width: 100%;">
                    <button type="button" class="clay-btn clay-btn-secondary clay-btn-sm" onclick="copyPortalLink('<?= $this->e($portalPayUrl) ?>', this)">
                        <?= clay_icon('link', 12) ?>
                        <span>Copy</span>
                    </button>
                    <a href="<?= $this->e($portalPayUrl) ?>" target="_blank" class="clay-btn clay-btn-ghost clay-btn-sm">
                        <?= clay_icon('external-link', 12) ?>
                        <span>Checkout &nearr;</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Printable Invoice Sheet -->
    <div class="clay-card p-8 mb-8" style="background: var(--color-bg-surface);">
        <div class="flex items-start justify-between flex-wrap gap-6 pb-6 mb-6" style="border-bottom: 1px solid var(--color-border);">
            <div>
                <?= logo_svg(36) ?>
                <h2 class="font-heading text-xl font-bold mt-2"><?= $this->e($invoice['freelancer_company'] ?: $invoice['freelancer_name']) ?></h2>
                <div class="text-sm text-subtle"><?= $this->e($invoice['freelancer_email']) ?></div>
            </div>

            <div class="text-right">
                <div class="caption-uppercase" style="font-size: 14px; color: var(--color-brand);">INVOICE</div>
                <div class="text-xs mt-1"><strong>Invoice #:</strong> <span class="tabular-nums font-semibold"><?= $this->e($invoice['invoice_number']) ?></span></div>
                <div class="text-xs mt-1"><strong>Issue Date:</strong> <?= date('F j, Y', strtotime($invoice['issue_date'])) ?></div>
                <div class="text-xs mt-1"><strong>Due Date:</strong> <?= date('F j, Y', strtotime($invoice['due_date'])) ?></div>
                <div class="mt-2"><?= status_chip($invoice['status']) ?></div>
            </div>
        </div>

        <div class="mb-6">
            <div class="caption-uppercase mb-1">Billed To:</div>
            <div style="font-weight: var(--weight-semibold); font-size: var(--text-body-lg);"><?= $this->e($invoice['client_name']) ?></div>
            <?php if (!empty($invoice['client_company'])): ?>
                <div class="text-sm text-subtle"><?= $this->e($invoice['client_company']) ?></div>
            <?php endif; ?>
            <div class="text-xs text-subtle"><?= $this->e($invoice['client_email']) ?></div>
            <?php if (!empty($invoice['client_phone'])): ?>
                <div class="text-xs text-subtle"><?= $this->e($invoice['client_phone']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Line Items Table -->
        <div class="clay-table-wrap mb-6">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th style="width: 55%;">Item & Scope</th>
                        <th style="width: 15%; text-align: center;">Qty / Hours</th>
                        <th style="width: 15%; text-align: right;">Rate</th>
                        <th style="width: 15%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td><strong><?= $this->e($item['description']) ?></strong></td>
                            <td style="text-align: center;" class="tabular-nums"><?= number_format((float)$item['quantity'], 2) ?></td>
                            <td style="text-align: right;" class="tabular-nums"><?= format_currency((float)$item['unit_price'], $invoice['currency']) ?></td>
                            <td style="text-align: right;" class="tabular-nums font-semibold"><?= format_currency((float)$item['total'], $invoice['currency']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals & Notes -->
        <div class="flex items-start justify-between flex-wrap gap-6 pt-4">
            <div class="max-w-md">
                <?php if (!empty($invoice['notes'])): ?>
                    <div class="caption-uppercase mb-1">Payment Instructions:</div>
                    <p class="text-xs text-subtle" style="background: var(--color-bg-surface-sunken); padding: var(--space-3); border-radius: var(--radius-sm); border: 1px solid var(--color-border);"><?= nl2br($this->e($invoice['notes'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="panel p-4 ml-auto" style="min-width: 260px; background: var(--color-bg-surface-sunken);">
                <div class="flex items-center justify-between mb-2 text-sm">
                    <span class="text-subtle">Subtotal:</span>
                    <span class="tabular-nums font-semibold"><?= format_currency((float)$invoice['subtotal'], $invoice['currency']) ?></span>
                </div>
                <?php if ((float)$invoice['tax_amount'] > 0): ?>
                    <div class="flex items-center justify-between mb-2 text-sm">
                        <span class="text-subtle">Tax (<?= $invoice['tax_rate'] ?>%):</span>
                        <span class="tabular-nums font-semibold"><?= format_currency((float)$invoice['tax_amount'], $invoice['currency']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center justify-between pt-3 text-base font-semibold" style="border-top: 1px solid var(--color-border);">
                    <span>Total Due:</span>
                    <span class="text-brand tabular-nums font-heading text-lg"><?= format_currency((float)$invoice['total_amount'], $invoice['currency']) ?></span>
                </div>
            </div>
        </div>

        <?php if ($invoice['status'] === 'paid' && !empty($invoice['paid_at'])): ?>
            <div class="mt-8 p-3 text-center font-semibold text-sm" style="background: var(--color-success-bg); color: var(--color-success-text); border-radius: var(--radius-md); border: 1px solid var(--color-success-border);">
                ✓ PAID IN FULL (<?= date('M j, Y', strtotime($invoice['paid_at'])) ?>)
            </div>
        <?php endif; ?>
    </div>
</div>
