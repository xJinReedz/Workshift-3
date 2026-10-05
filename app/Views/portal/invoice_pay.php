<div class="content-wrapper" style="max-width: 600px; margin: 0 auto;">
    <div class="clay-card">
        <div class="text-center pb-4 mb-4" style="border-bottom: 1px solid var(--color-border);">
            <span class="lozenge lozenge-info mb-2">Secure Checkout</span>
            <h2 style="font-size: var(--text-h2); font-weight: var(--weight-semibold); margin: var(--space-2) 0;">Pay Invoice <?= $this->e($invoice['invoice_number']) ?></h2>
            <div class="text-brand font-heading text-3xl font-bold my-2 tabular-nums">
                <?= format_currency((float)$invoice['total_amount'], $invoice['currency']) ?>
            </div>
            <p class="text-xs text-subtle" style="margin-bottom: 0;">Payment to <strong><?= $this->e($client['freelancer_company'] ?: $client['freelancer_name']) ?></strong></p>
        </div>

        <form method="POST" action="/portal/<?= $token ?>/pay/<?= $invoice['id'] ?>/process">
            <?= $this->csrf() ?>
            <input type="hidden" name="reference" value="<?= $this->e($referenceNumber) ?>">

            <div class="mb-4" style="display: flex; flex-direction: column; gap: var(--space-3);">
                <label class="panel flex items-center gap-3 p-3 cursor-pointer" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-brand);">
                    <input type="radio" name="payment_method" value="maya_qr" checked style="accent-color: var(--color-brand); width: 16px; height: 16px;">
                    <div>
                        <strong class="text-sm font-semibold block">Maya Business / QR Ph</strong>
                        <span class="text-xs text-subtle">Scan to pay from Maya, GCash, or mobile banking apps</span>
                    </div>
                </label>

                <label class="panel flex items-center gap-3 p-3 cursor-pointer" style="background: var(--color-bg-surface);">
                    <input type="radio" name="payment_method" value="card" style="accent-color: var(--color-brand); width: 16px; height: 16px;">
                    <div>
                        <strong class="text-sm font-semibold block">Credit / Debit Card</strong>
                        <span class="text-xs text-subtle">Visa, Mastercard, JCB</span>
                    </div>
                </label>
            </div>

            <!-- Simulated QR Ph Visual box -->
            <div class="panel text-center p-6 mb-6" style="background: var(--color-bg-surface-sunken);">
                <div style="display: inline-block; padding: 12px; background: #FFFFFF; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                    <svg width="120" height="120" viewBox="0 0 100 100" fill="none">
                        <rect width="100" height="100" fill="#f8fafc" rx="4"/>
                        <rect x="10" y="10" width="30" height="30" fill="#1D2125" rx="2"/>
                        <rect x="15" y="15" width="20" height="20" fill="#ffffff" rx="1"/>
                        <rect x="20" y="20" width="10" height="10" fill="#1D2125"/>

                        <rect x="60" y="10" width="30" height="30" fill="#1D2125" rx="2"/>
                        <rect x="65" y="15" width="20" height="20" fill="#ffffff" rx="1"/>
                        <rect x="70" y="20" width="10" height="10" fill="#1D2125"/>

                        <rect x="10" y="60" width="30" height="30" fill="#1D2125" rx="2"/>
                        <rect x="15" y="65" width="20" height="20" fill="#ffffff" rx="1"/>
                        <rect x="20" y="70" width="10" height="10" fill="#1D2125"/>

                        <circle cx="50" cy="50" r="12" fill="#0C66E4"/>
                        <path d="M46 50L49 53L55 47" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="text-xs text-subtle mt-3">
                    Reference: <code style="background: var(--color-bg-surface); padding: 2px 6px; border-radius: 3px; border: 1px solid var(--color-border);"><?= $this->e($referenceNumber) ?></code>
                </div>
                <div class="text-xs font-semibold mt-2" style="color: var(--color-success);">
                    ✓ QR Ph Instant Settlement Verified
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                <button type="submit" class="clay-btn clay-btn-primary font-medium" style="width: 100%;">
                    Confirm & Complete Payment (<?= format_currency((float)$invoice['total_amount'], $invoice['currency']) ?>)
                </button>
                <a href="/portal/<?= $token ?>" class="clay-btn clay-btn-ghost font-medium text-center text-sm">
                    &larr; Return to Project Board
                </a>
            </div>
        </form>
    </div>
</div>
