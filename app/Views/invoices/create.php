<div class="content-wrapper">
    <!-- Breadcrumbs -->
    <div class="breadcrumb">
        <a href="/invoices">Invoices</a>
        <span class="breadcrumb-separator">/</span>
        <span>Create</span>
    </div>

    <!-- Title Row -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary m-0">Create New Invoice</h1>
            <p class="text-sm text-subtle mt-1 mb-0">Generate an itemized invoice from tracked time or fixed project milestones.</p>
        </div>
    </div>

    <!-- Two-Column Form Layout (Left: 8 cols, Right: 4 cols sticky aside) -->
    <form method="POST" action="/invoices" id="invoice-form" class="dirty-check">
        <?= $this->csrf() ?>

        <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: var(--space-6); align-items: start;">
            <!-- Left Column: Form Fields & Line Items (8 cols) -->
            <div style="grid-column: span 8; display: flex; flex-direction: column; gap: var(--space-6);" class="invoice-form-main-col">
                
                <!-- Client & Dates Panel -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h3 class="caption-uppercase mb-4 text-primary font-bold">Invoice Details</h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);" class="invoice-grid-2col">
                        <div class="form-group mb-4">
                            <label for="client_id" class="form-label">Client *</label>
                            <select id="client_id" name="client_id" class="form-select w-full" required onchange="onClientSelect(this.value)">
                                <option value="">-- Choose Client --</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                            data-rate="<?= $c['rate'] ?>"
                                            data-billing="<?= $this->e($c['billing_type'] ?? 'hourly') ?>"
                                            <?= ($selectedClientId == $c['id']) ? 'selected' : '' ?>>
                                        <?= $this->e($c['name']) ?> <?= !empty($c['company']) ? '(' . $this->e($c['company']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label for="invoice_number" class="form-label">Invoice Number *</label>
                            <input type="text" id="invoice_number" name="invoice_number" class="form-input w-full" value="<?= $this->e($nextInvoiceNumber) ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);" class="invoice-grid-2col">
                        <div class="form-group mb-0">
                            <label for="issue_date" class="form-label">Issue Date</label>
                            <input type="date" id="issue_date" name="issue_date" class="form-input w-full" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group mb-0">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" id="due_date" name="due_date" class="form-input w-full" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Line Items Table Panel -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="caption-uppercase m-0 text-primary font-bold">Line Items</h3>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addLineItem()">
                            <?= clay_icon('plus', 12) ?>
                            <span>Add Item</span>
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table class="table" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Description</th>
                                    <th style="width: 15%;">Qty / Hours</th>
                                    <th style="width: 18%;">Unit Rate (₱)</th>
                                    <th style="width: 17%; text-align: right;">Total (₱)</th>
                                    <th style="width: 36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr>
                                    <td>
                                        <input type="text" name="item_description[]" class="form-input w-full" placeholder="e.g. Website layout development..." required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.25" min="0.1" name="item_quantity[]" class="form-input w-full item-qty" value="1" oninput="calcTotals()" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-input w-full item-price" value="750.00" oninput="calcTotals()" required>
                                    </td>
                                    <td style="text-align: right;">
                                        <input type="text" readonly class="form-input w-full item-row-total tabular-nums font-bold" style="text-align: right; background: var(--color-bg-surface-sunken);" value="750.00">
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-ghost btn-sm text-danger py-1 px-2" onclick="removeLineItem(this)">&times;</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Notes & Instructions -->
                <div class="panel p-6" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h3 class="caption-uppercase mb-2 text-primary font-bold">Payment Instructions & Notes</h3>
                    <p class="text-xs text-subtle mb-3">Bank transfer details, GCash, Maya number, or payment terms displayed on the bottom of the invoice.</p>
                    <textarea id="notes" name="notes" rows="4" class="form-textarea w-full" placeholder="Maya / GCash: 0917-123-4567&#10;BDO Savings: 1234-5678-9012&#10;Payment due within 14 calendar days."><?= $this->e($user['payment_instructions'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Right Column: Sticky Summary Aside (4 cols) -->
            <div style="grid-column: span 4; display: flex; flex-direction: column; gap: var(--space-6); position: sticky; top: calc(var(--topbar-height) + var(--space-6));" class="invoice-form-aside-col">
                
                <!-- Primary Action Card (Always visible at top of aside) -->
                <div class="panel p-5" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <button type="submit" class="btn btn-primary w-full font-semibold mb-2" style="height: 42px;">
                        Save & Issue Invoice
                    </button>
                    <a href="/invoices" class="btn btn-secondary w-full text-center font-medium">
                        Cancel
                    </a>
                </div>

                <!-- Totals Calculation Box -->
                <div class="panel p-5" style="background: var(--color-bg-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
                    <h4 class="font-bold text-sm text-primary mb-3 pb-2" style="border-bottom: 1px solid var(--color-border);">Invoice Summary</h4>
                    
                    <div class="flex items-center justify-between text-xs text-subtle py-1.5">
                        <span>Subtotal</span>
                        <strong class="text-primary tabular-nums" id="summary-subtotal">₱750.00</strong>
                    </div>

                    <div class="flex items-center justify-between text-xs text-subtle py-1.5" style="border-bottom: 1px solid var(--color-border);">
                        <span>Tax Rate</span>
                        <div class="flex items-center gap-1">
                            <input type="number" step="0.5" min="0" max="100" id="tax_rate" name="tax_rate" class="form-input" style="width: 60px; height: 28px; padding: 2px 6px; font-size: 0.75rem;" value="0" oninput="calcTotals()">
                            <span>%</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-base font-bold text-primary pt-3">
                        <span>Total Due</span>
                        <span class="tabular-nums font-heading" id="summary-total">₱750.00</span>
                    </div>

                    <div class="mt-4 pt-3 text-xs text-subtle" style="border-top: 1px solid var(--color-border);">
                        <span>Currency: <strong class="text-primary">Philippine Peso (PHP ₱)</strong></span>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<style>
@media (max-width: 1023px) {
    .invoice-form-main-col {
        grid-column: span 12 !important;
    }
    .invoice-form-aside-col {
        grid-column: span 12 !important;
        position: static !important;
    }
}
@media (max-width: 639px) {
    .invoice-grid-2col {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function onClientSelect(clientId) {
    if (!clientId) return;
    const opt = document.querySelector(`#client_id option[value="${clientId}"]`);
    if (opt) {
        const rate = opt.dataset.rate || '750.00';
        const priceInput = document.querySelector('.item-price');
        if (priceInput && (!priceInput.value || priceInput.value === '750.00' || priceInput.value === '500.00')) {
            priceInput.value = rate;
            calcTotals();
        }
    }
}

function addLineItem() {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="item_description[]" class="form-input w-full" placeholder="Service description..." required></td>
        <td><input type="number" step="0.25" min="0.1" name="item_quantity[]" class="form-input w-full item-qty" value="1" oninput="calcTotals()" required></td>
        <td><input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-input w-full item-price" value="750.00" oninput="calcTotals()" required></td>
        <td style="text-align: right;"><input type="text" readonly class="form-input w-full item-row-total tabular-nums font-bold" style="text-align: right; background: var(--color-bg-surface-sunken);" value="750.00"></td>
        <td style="text-align: center;"><button type="button" class="btn btn-ghost btn-sm text-danger py-1 px-2" onclick="removeLineItem(this)">&times;</button></td>
    `;
    tbody.appendChild(tr);
    calcTotals();
}

function removeLineItem(btn) {
    const rows = document.querySelectorAll('#items-body tr');
    if (rows.length > 1) {
        btn.closest('tr').remove();
        calcTotals();
    } else {
        window.WS.modal.warning({
            title: 'Line Item Required',
            message: 'An invoice must have at least one line item.',
            buttonText: 'OK'
        });
    }
}

function calcTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('#items-body tr');
    rows.forEach(r => {
        const qty = parseFloat(r.querySelector('.item-qty').value) || 0;
        const price = parseFloat(r.querySelector('.item-price').value) || 0;
        const total = qty * price;
        r.querySelector('.item-row-total').value = total.toFixed(2);
        subtotal += total;
    });

    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const taxAmount = subtotal * (taxRate / 100);
    const grandTotal = subtotal + taxAmount;

    document.getElementById('summary-subtotal').innerText = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summary-total').innerText = '₱' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
