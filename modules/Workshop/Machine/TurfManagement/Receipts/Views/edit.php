<?php 
require_once __DIR__ . '/../../../../../../core/CSRFManager.php'; 
$csrfToken = \App\Core\CSRFManager::generateToken();
$itemsList = !empty($voucher['items']) ? (is_array($voucher['items']) ? $voucher['items'] : json_decode($voucher['items'], true)) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Receipt Voucher <?php echo htmlspecialchars((string)($voucher['voucher_no'] ?? '')); ?> - RV Control System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .table-qty-input {
            width: 130px;
        }
        .table-rate-input {
            width: 120px;
        }
        .item-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">

    <!-- Top Navigation Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark fs-6 px-3 py-2 fw-bold">EDIT MODE</span>
                <h2 class="h3 fw-bold text-dark mb-0">
                    <i class="bi bi-pencil-square text-warning me-2"></i>Edit Receipt Voucher: <span class="text-primary"><?php echo htmlspecialchars((string)($voucher['voucher_no'] ?? '')); ?></span>
                </h2>
            </div>
            <small class="text-muted">Modify voucher header details, reconcile inward stock, and update perpetual inventory ledger</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=list" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Receipts List
            </a>
            <a href="?action=printVoucher&id=<?php echo urlencode($voucher['id']); ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i> Print Current
            </a>
        </div>
    </div>

    <!-- Feedback Container -->
    <div id="alert-container"></div>

    <form id="edit-voucher-form">
        <input type="hidden" id="voucher_id" value="<?php echo (int)$voucher['id']; ?>">
        <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <!-- Master Voucher Information Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-receipt me-2 text-primary"></i>Voucher Header Information
                </h5>
                <span class="badge bg-light text-dark border">Office: <?php echo htmlspecialchars((string)($voucher['office_name'] ?? 'Current Office')); ?></span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="voucher_no" class="form-label fw-bold">Voucher Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fw-bold text-primary" id="voucher_no" name="voucher_no"
                               value="<?php echo htmlspecialchars((string)($voucher['voucher_no'] ?? '')); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="voucher_date" class="form-label fw-bold">Voucher Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="voucher_date" name="voucher_date"
                               value="<?php echo htmlspecialchars((string)($voucher['voucher_date'] ?? '')); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="store_category" class="form-label fw-bold">Store Category <span class="text-danger">*</span></label>
                        <select class="form-select text-uppercase" id="store_category" name="store_category" required>
                            <?php 
                                $currCat = strtolower($voucher['store_category'] ?? 'general');
                                $availCats = ['fuel' => 'Fuel', 'agronomy' => 'Agronomy', 'spares' => 'Spares', 'tools' => 'Tools & Equipments', 'general' => 'General'];
                                foreach ($availCats as $cCode => $cLabel):
                            ?>
                                <option value="<?php echo $cCode; ?>" <?php echo ($currCat === $cCode) ? 'selected' : ''; ?>>
                                    <?php echo $cLabel; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="supply_status" class="form-label fw-bold">Supply Status</label>
                        <select class="form-select" id="supply_status" name="supply_status">
                            <option value="COMPLETE" <?php echo (($voucher['supply_status'] ?? '') === 'COMPLETE') ? 'selected' : ''; ?>>COMPLETE (Final Consignment)</option>
                            <option value="PARTIAL" <?php echo (($voucher['supply_status'] ?? '') === 'PARTIAL') ? 'selected' : ''; ?>>PARTIAL (More Lots Pending)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="party" class="form-label fw-bold">Received From / Vendor / Party <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="party" name="party"
                               value="<?php echo htmlspecialchars((string)($voucher['party'] ?? '')); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="source_type" class="form-label fw-bold">Procurement Source</label>
                        <select class="form-select" id="source_type" name="source_type">
                            <?php 
                                $currSrc = strtoupper($voucher['source_type'] ?? 'MANUAL');
                            ?>
                            <option value="MANUAL" <?php echo ($currSrc === 'MANUAL') ? 'selected' : ''; ?>>Manual / Retail Cash Purchase</option>
                            <option value="AGREEMENT" <?php echo ($currSrc === 'AGREEMENT') ? 'selected' : ''; ?>>Contract Agreement</option>
                            <option value="SUPPLY_ORDER" <?php echo ($currSrc === 'SUPPLY_ORDER') ? 'selected' : ''; ?>>Supply Order (SO)</option>
                            <option value="WORK_ORDER" <?php echo ($currSrc === 'WORK_ORDER') ? 'selected' : ''; ?>>Work Order (WO)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="source_ref_no" class="form-label fw-bold">Source Reference / Order No.</label>
                        <input type="text" class="form-control" id="source_ref_no" name="source_ref_no"
                               placeholder="e.g. AG/2026/04 or SO/12"
                               value="<?php echo htmlspecialchars((string)($voucher['source_ref_no'] ?? '')); ?>">
                    </div>

                    <div class="col-12">
                        <label for="remarks" class="form-label fw-bold">Master Remarks / Delivery Notes</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Optional notes regarding this receipt voucher..."><?php echo htmlspecialchars((string)($voucher['remarks'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-list-check me-2 text-success"></i>Voucher Line Items
                    </h5>
                    <small class="text-muted">Edit, add, or remove items. All quantities will be re-synchronized in the inventory ledger.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary fw-bold" id="btn-add-item">
                    <i class="bi bi-plus-circle me-1"></i> Add Line Item
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Item Description / Nomenclature <span class="text-danger">*</span></th>
                                <th style="width: 120px;">Unit</th>
                                <th style="width: 140px;" class="text-end">Quantity <span class="text-danger">*</span></th>
                                <th style="width: 130px;" class="text-end">Unit Rate (₹)</th>
                                <th style="width: 140px;" class="text-end">Total Amount (₹)</th>
                                <th>Line Remarks</th>
                                <th style="width: 60px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody">
                            <!-- Populated via JS -->
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">Voucher Totals:</td>
                                <td class="text-end text-primary" id="footer-total-qty">0</td>
                                <td></td>
                                <td class="text-end text-success fs-6" id="footer-total-amt">₹0.00</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit & Control Bar -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <a href="?action=list" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-x-circle me-1"></i> Cancel & Return
                </a>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm" id="btn-submit">
                        <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Voucher
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Datalist for existing catalog items for quick autocomplete -->
<datalist id="inventory-items-datalist">
    <?php if (!empty($inventoryItems)): ?>
        <?php foreach ($inventoryItems as $inv): ?>
            <option value="<?php echo htmlspecialchars($inv['description'] ?? ''); ?>" data-unit="<?php echo htmlspecialchars($inv['ac_unit'] ?? 'Pcs'); ?>" data-id="<?php echo (int)($inv['id'] ?? 0); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($spareParts)): ?>
        <?php foreach ($spareParts as $sp): ?>
            <option value="<?php echo htmlspecialchars($sp['nomenclature'] ?? ''); ?>" data-unit="<?php echo htmlspecialchars($sp['unit'] ?? 'Pcs'); ?>" data-sp-id="<?php echo (int)($sp['id'] ?? 0); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</datalist>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initial data passed from PHP
    const initialItems = <?php echo json_encode($itemsList ?: []); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('items-tbody');

        if (initialItems && initialItems.length > 0) {
            initialItems.forEach(it => addItemRow(it));
        } else {
            addItemRow({});
        }

        document.getElementById('btn-add-item').addEventListener('click', function() {
            addItemRow({});
        });

        document.getElementById('edit-voucher-form').addEventListener('submit', handleFormSubmit);
    });

    function addItemRow(data = {}) {
        const tbody = document.getElementById('items-tbody');
        const row = document.createElement('tr');
        row.className = 'item-row';

        const desc = data.description || '';
        const unit = data.unit || 'Pcs';
        const qty = (data.quantity !== undefined && data.quantity !== null) ? data.quantity : '';
        const rate = (data.rate !== undefined && data.rate !== null) ? data.rate : '';
        const amount = (data.amount !== undefined && data.amount !== null) ? data.amount : '';
        const remarks = data.remarks || '';
        const itemId = data.item_id || '';
        const sparePartId = data.spare_part_id || '';
        const isSparePart = data.is_spare_part ? '1' : '0';

        row.innerHTML = `
            <td class="text-center row-index fw-semibold text-muted"></td>
            <td>
                <input type="text" class="form-control item-desc" list="inventory-items-datalist" 
                       value="${escapeHtml(desc)}" placeholder="Item description / name" required>
                <input type="hidden" class="item-id" value="${itemId}">
                <input type="hidden" class="spare-part-id" value="${sparePartId}">
                <input type="hidden" class="is-spare-part" value="${isSparePart}">
            </td>
            <td>
                <input type="text" class="form-control item-unit" value="${escapeHtml(unit)}" placeholder="Unit">
            </td>
            <td>
                <input type="number" step="any" min="0.001" class="form-control text-end item-qty" 
                       value="${qty}" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" step="any" min="0" class="form-control text-end item-rate" 
                       value="${rate}" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="any" readonly class="form-control text-end item-amt bg-light fw-bold text-success" 
                       value="${amount}" placeholder="0.00">
            </td>
            <td>
                <input type="text" class="form-control item-remarks" value="${escapeHtml(remarks)}" placeholder="Optional notes">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-row" title="Delete Item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);

        // Bind events
        const qtyInput = row.querySelector('.item-qty');
        const rateInput = row.querySelector('.item-rate');
        const amtInput = row.querySelector('.item-amt');
        const descInput = row.querySelector('.item-desc');
        const unitInput = row.querySelector('.item-unit');
        const deleteBtn = row.querySelector('.btn-delete-row');

        const calcRowAmount = () => {
            const q = parseFloat(qtyInput.value) || 0;
            const r = parseFloat(rateInput.value) || 0;
            const total = q * r;
            amtInput.value = total > 0 ? total.toFixed(2) : '0.00';
            updateTotals();
        };

        qtyInput.addEventListener('input', calcRowAmount);
        rateInput.addEventListener('input', calcRowAmount);

        // When description changes, check if option exists in datalist to auto-fill unit
        descInput.addEventListener('change', function() {
            const val = this.value.trim().toLowerCase();
            const datalist = document.getElementById('inventory-items-datalist');
            const options = datalist.querySelectorAll('option');
            for (let opt of options) {
                if (opt.value.trim().toLowerCase() === val) {
                    if (opt.dataset.unit && !unitInput.value) {
                        unitInput.value = opt.dataset.unit;
                    }
                    if (opt.dataset.id) {
                        row.querySelector('.item-id').value = opt.dataset.id;
                    }
                    if (opt.dataset.spId) {
                        row.querySelector('.spare-part-id').value = opt.dataset.spId;
                        row.querySelector('.is-spare-part').value = '1';
                    }
                    break;
                }
            }
        });

        deleteBtn.addEventListener('click', function() {
            if (tbody.querySelectorAll('.item-row').length <= 1) {
                alert('A Receipt Voucher must contain at least one line item.');
                return;
            }
            if (confirm('Remove this item from the voucher?')) {
                row.remove();
                reindexRows();
                updateTotals();
            }
        });

        calcRowAmount();
        reindexRows();
    }

    function reindexRows() {
        const rows = document.querySelectorAll('#items-tbody .item-row');
        rows.forEach((r, idx) => {
            r.querySelector('.row-index').textContent = idx + 1;
        });
    }

    function updateTotals() {
        let totalQty = 0;
        let totalAmt = 0;

        document.querySelectorAll('#items-tbody .item-row').forEach(row => {
            const q = parseFloat(row.querySelector('.item-qty').value) || 0;
            const a = parseFloat(row.querySelector('.item-amt').value) || 0;
            totalQty += q;
            totalAmt += a;
        });

        document.getElementById('footer-total-qty').textContent = totalQty.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 3});
        document.getElementById('footer-total-amt').textContent = '₹' + totalAmt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function handleFormSubmit(e) {
        e.preventDefault();

        const btnSubmit = document.getElementById('btn-submit');
        const alertBox = document.getElementById('alert-container');
        alertBox.innerHTML = '';

        const voucherId = document.getElementById('voucher_id').value;
        const csrfToken = document.getElementById('csrf_token').value;

        // Build master payload
        const masterData = {
            voucher_no: document.getElementById('voucher_no').value.trim(),
            voucher_date: document.getElementById('voucher_date').value,
            store_category: document.getElementById('store_category').value,
            supply_status: document.getElementById('supply_status').value,
            party: document.getElementById('party').value.trim(),
            source_type: document.getElementById('source_type').value,
            source_ref_no: document.getElementById('source_ref_no').value.trim(),
            remarks: document.getElementById('remarks').value.trim()
        };

        if (!masterData.voucher_no) {
            showAlert('danger', 'Voucher Number is required.');
            return;
        }
        if (!masterData.voucher_date) {
            showAlert('danger', 'Voucher Date is required.');
            return;
        }
        if (!masterData.party) {
            showAlert('danger', 'Received From / Vendor name is required.');
            return;
        }

        // Build items array
        const items = [];
        const rows = document.querySelectorAll('#items-tbody .item-row');

        if (rows.length === 0) {
            showAlert('danger', 'Please add at least one line item to the voucher.');
            return;
        }

        for (let i = 0; i < rows.length; i++) {
            const r = rows[i];
            const desc = r.querySelector('.item-desc').value.trim();
            const qty = parseFloat(r.querySelector('.item-qty').value) || 0;
            const rate = parseFloat(r.querySelector('.item-rate').value) || 0;
            const amount = parseFloat(r.querySelector('.item-amt').value) || (qty * rate);
            const unit = r.querySelector('.item-unit').value.trim() || 'Pcs';
            const remarks = r.querySelector('.item-remarks').value.trim();
            const itemId = parseInt(r.querySelector('.item-id').value) || null;
            const sparePartId = parseInt(r.querySelector('.spare-part-id').value) || null;
            const isSparePart = r.querySelector('.is-spare-part').value === '1';

            if (!desc) {
                showAlert('danger', `Item #${i + 1} requires a description/name.`);
                return;
            }
            if (qty <= 0) {
                showAlert('danger', `Item #${i + 1} ('${desc}') must have a quantity greater than zero.`);
                return;
            }

            items.push({
                description: desc,
                quantity: qty,
                unit: unit,
                rate: rate,
                amount: amount,
                remarks: remarks,
                item_id: itemId,
                spare_part_id: sparePartId,
                is_spare_part: isSparePart
            });
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...`;

        const payload = {
            id: voucherId,
            csrf_token: csrfToken,
            masterData: masterData,
            items: items
        };

        fetch(`?action=update&id=${encodeURIComponent(voucherId)}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Save Changes & Update Voucher`;

            if (data.status === 'success') {
                showAlert('success', data.message || 'Voucher updated successfully! Redirecting...');
                setTimeout(() => {
                    window.location.href = '?action=list';
                }, 900);
            } else {
                showAlert('danger', data.message || 'Failed to update voucher.');
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Save Changes & Update Voucher`;
            showAlert('danger', 'An error occurred during submission: ' + err.message);
        });
    }

    function showAlert(type, msg) {
        const alertBox = document.getElementById('alert-container');
        alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${escapeHtml(msg)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
</body>
</html>
