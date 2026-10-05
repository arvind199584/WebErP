<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Indent Voucher</title>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Create Indent Voucher (Issue)</h1>
        <a href="?action=list" class="btn btn-secondary">Back to List</a>
    </div>

    <!-- Master Data (Voucher Level) -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">1. Voucher Details (Master Data)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($userRole === 'superuser'): ?>
                    <div class="col-md-3">
                        <label for="master_office_id" class="form-label">Office</label>
                        <select id="master_office_id" class="form-select" required>
                            <option value="">-- Choose Office --</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label for="master_voucher_no" class="form-label">Voucher No.</label>
                    <input type="text" id="master_voucher_no" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label for="master_date" class="form-label">Date</label>
                    <input type="date" id="master_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="master_party" class="form-label">Issued To / Machine</label>
                    <input type="text" id="master_party" class="form-control" placeholder="Operator / Machine">
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Data (Item Level Staging) -->
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">2. Add Items to Issue</h5>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="item_select" class="form-label">Inventory Item</label>
                    <select id="item_select" class="form-select">
                        <option value="">-- Select Item --</option>
                        <?php foreach ($inventoryItems as $item): ?>
                            <option value="<?php echo $item['id']; ?>" data-unit="<?php echo htmlspecialchars($item['ac_unit']); ?>">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="item_quantity" class="form-label">Quantity to Issue</label>
                    <div class="input-group">
                        <input type="number" id="item_quantity" class="form-control" step="0.01">
                        <span class="input-group-text" id="item_unit_display">-</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <button type="button" id="stage-item-btn" class="btn btn-warning w-100">Add Item to List ↓</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Staged Items Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">3. Staged Items (Pending Issue)</h5>
            <span class="badge bg-secondary" id="total-items-badge">0 Items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Item Description</th>
                            <th>Quantity Issued</th>
                            <th>Unit</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="staged-items-tbody">
                        <tr><td colspan="5" class="text-center text-muted py-4">No items staged yet. Add items above.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end bg-white py-3">
            <button type="button" id="commit-voucher-btn" class="btn btn-primary btn-lg px-5">Commit Indent Voucher</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userRole = "<?php echo $userRole; ?>";
    const currentUserOfficeId = "<?php echo $currentUser['officeid']; ?>";

    // Master Inputs
    const officeSelect = document.getElementById('master_office_id');
    const voucherNoInput = document.getElementById('master_voucher_no');
    const dateInput = document.getElementById('master_date');
    const partyInput = document.getElementById('master_party');

    // Detail Inputs
    const itemSelect = document.getElementById('item_select');
    const quantityInput = document.getElementById('item_quantity');
    const unitDisplay = document.getElementById('item_unit_display');

    // Buttons & Tables
    const stageBtn = document.getElementById('stage-item-btn');
    const commitBtn = document.getElementById('commit-voucher-btn');
    const tbody = document.getElementById('staged-items-tbody');
    const totalBadge = document.getElementById('total-items-badge');

    let stagedItems = [];

    // Update the unit display when an item is selected
    itemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value !== "") {
            unitDisplay.textContent = selectedOption.dataset.unit;
            quantityInput.focus();
        } else {
            unitDisplay.textContent = '-';
        }
    });

    const renderTable = () => {
        if (stagedItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No items staged yet. Add items above.</td></tr>';
            totalBadge.textContent = '0 Items';
            return;
        }

        tbody.innerHTML = '';
        stagedItems.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td class="fw-bold">${item.description}</td>
                <td>${item.quantity}</td>
                <td>${item.unit}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-btn" data-index="${index}">Drop</button></td>
            `;
            tbody.appendChild(tr);
        });
        totalBadge.textContent = `${stagedItems.length} Items`;
    };

    stageBtn.addEventListener('click', () => {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const itemId = itemSelect.value;
        const qty = parseFloat(quantityInput.value);

        if (!itemId) { alert("Please select an item."); return; }
        if (!qty || qty <= 0) { alert("Please enter a valid quantity greater than 0."); return; }

        // Check if item is already staged in this voucher
        if (stagedItems.some(i => i.item_id === itemId)) {
            alert("This item is already staged. If you need to change the quantity, drop it and add it again.");
            return;
        }

        stagedItems.push({
            item_id: itemId,
            description: selectedOption.text,
            unit: selectedOption.dataset.unit,
            quantity: qty
        });

        renderTable();

        // Reset item fields
        itemSelect.value = '';
        quantityInput.value = '';
        unitDisplay.textContent = '-';
        itemSelect.focus();
    });

    tbody.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-btn')) {
            const index = parseInt(e.target.getAttribute('data-index'), 10);
            stagedItems.splice(index, 1);
            renderTable();
        }
    });

    commitBtn.addEventListener('click', async () => {
        // Validate Master Data
        const voucherNo = voucherNoInput.value.trim();
        const txDate = dateInput.value;
        const officeId = userRole === 'superuser' ? (officeSelect ? officeSelect.value : null) : currentUserOfficeId;

        if (!voucherNo) { alert("Voucher Number is required."); voucherNoInput.focus(); return; }
        if (!txDate) { alert("Date is required."); dateInput.focus(); return; }
        if (userRole === 'superuser' && !officeId) { alert("Office is required."); officeSelect.focus(); return; }

        // Validate Items
        if (stagedItems.length === 0) {
            alert("You must add at least one item to the voucher before committing.");
            return;
        }

        const masterData = {
            office_id: officeId,
            voucher_no: voucherNo,
            transaction_date: txDate,
            party: partyInput.value.trim()
        };

        const originalText = commitBtn.innerText;
        commitBtn.innerText = 'Processing Transaction...';
        commitBtn.disabled = true;

        try {
            const response = await fetch('?action=createBulkIndent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>",
                    masterData: masterData,
                    items: stagedItems
                })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Server error during transaction.');
            }

            alert(result.message);
            window.location.href = '?action=list';

        } catch (error) {
            alert('Transaction Failed: ' + error.message);
            commitBtn.innerText = originalText;
            commitBtn.disabled = false;
        }
    });
});
</script>
</body>
</html>
