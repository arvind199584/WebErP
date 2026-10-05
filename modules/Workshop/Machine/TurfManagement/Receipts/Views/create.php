<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Receipt Voucher</title>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Create Receipt Voucher</h1>
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
                    <label for="master_party" class="form-label">Received From (Party)</label>
                    <input type="text" id="master_party" class="form-control" placeholder="Vendor / Source">
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Data (Item Level Staging) -->
    <div class="card mb-4 border-success">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">2. Select Sub-Store Category & Add Items</h5>
            <span class="badge bg-light text-dark">One Store Division Per Voucher</span>
        </div>
        <div class="card-body bg-light">

            <!-- Step A: Select Single Store Division Category -->
            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <label for="master_store_category" class="form-label fw-bold text-dark">Sub-Store Division Category</label>
                    <select id="master_store_category" class="form-select border-primary fw-bold text-primary fs-6">
                        <option value="">-- Select Store Category for RV --</option>
                        <option value="fuel">⛽ Fuel / Oil</option>
                        <option value="workshop">🛠️ Workshop Store (Spare Parts)</option>
                        <option value="agronomy">🌱 Agronomy Store</option>
                        <option value="housekeeping">🧹 Housekeeping Items</option>
                        <option value="stationery">✏️ Stationery</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <div class="alert alert-info py-2 mb-0 small" id="rv-category-hint">
                        ℹ️ Select a sub-store division above. Items will be filtered exclusively for that category.
                    </div>
                </div>
            </div>

            <hr>

            <!-- Step B: Item Selection Form (Visible after Category Selection) -->
            <div id="item-selection-container" style="display: none;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="item_select" class="form-label fw-bold">Select Item</label>
                        <select id="item_select" class="form-select fw-bold">
                            <option value="">-- Choose Item --</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="item_quantity" class="form-label fw-bold">Quantity Received</label>
                        <div class="input-group">
                            <input type="number" id="item_quantity" class="form-control" step="0.01" placeholder="Qty">
                            <span class="input-group-text" id="item_unit_display">Pcs</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <button type="button" id="stage-item-btn" class="btn btn-success w-100 fw-bold">Add Item to List ↓</button>
                    </div>
                </div>

                <!-- On-The-Fly New Item Creation Box (Shows when "+ Add New Item..." is selected) -->
                <div id="new-item-extra-fields" class="mt-3 p-3 border border-success rounded bg-white" style="display: none;">
                    <h6 class="fw-bold text-success mb-2">✨ Create Brand New Item in <span id="new-item-category-label" class="text-uppercase"></span> Category</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="new_item_description" class="form-label fw-bold">Item Name / Nomenclature</label>
                            <input type="text" id="new_item_description" class="form-control" placeholder="Enter Full Item Description">
                        </div>

                        <!-- Specific to Workshop Spare Parts -->
                        <div class="col-md-3" id="field-new-partno" style="display: none;">
                            <label for="new_part_no" class="form-label fw-bold">Part Number (Optional)</label>
                            <input type="text" id="new_part_no" class="form-control" placeholder="e.g. JD-90812">
                        </div>

                        <div class="col-md-3" id="field-new-machine" style="display: none;">
                            <label for="new_machine_id" class="form-label fw-bold">Associated Machine</label>
                            <select id="new_machine_id" class="form-select">
                                <option value="">-- Select Machine --</option>
                                <?php foreach ($machines as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="new_item_unit" class="form-label fw-bold">Unit of Measurement</label>
                            <input type="text" id="new_item_unit" class="form-control" value="Pcs" placeholder="Pcs / Kg / Ltr">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Staged Items Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">3. Staged Items (Pending Save)</h5>
            <span class="badge bg-secondary" id="total-items-badge">0 Items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Item Description</th>
                            <th>Quantity</th>
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
            <button type="button" id="commit-voucher-btn" class="btn btn-primary btn-lg px-5">Commit Receipt Voucher</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userRole = "<?php echo $userRole; ?>";
    const currentUserOfficeId = "<?php echo $currentUser['officeid']; ?>";

    const allInventoryItems = <?php echo json_encode($inventoryItems); ?>;
    const allSpareParts = <?php echo json_encode($spareParts); ?>;

    // Elements
    const storeCategorySelect = document.getElementById('master_store_category');
    const categoryHint = document.getElementById('rv-category-hint');
    const selectionContainer = document.getElementById('item-selection-container');
    const itemSelect = document.getElementById('item_select');
    const quantityInput = document.getElementById('item_quantity');
    const unitDisplay = document.getElementById('item_unit_display');

    const newItemExtraFields = document.getElementById('new-item-extra-fields');
    const newItemCatLabel = document.getElementById('new-item-category-label');
    const fieldPartNo = document.getElementById('field-new-partno');
    const fieldMachine = document.getElementById('field-new-machine');

    const stageItemBtn = document.getElementById('stage-item-btn');
    const commitBtn = document.getElementById('commit-voucher-btn');
    const tbody = document.getElementById('staged-items-tbody');
    const totalBadge = document.getElementById('total-items-badge');

    let stagedItems = [];
    let selectedCategoryCode = '';

    // Handle Sub-Store Category Change
    storeCategorySelect.addEventListener('change', function() {
        selectedCategoryCode = this.value;
        newItemExtraFields.style.display = 'none';

        if (!selectedCategoryCode) {
            selectionContainer.style.display = 'none';
            categoryHint.innerHTML = "ℹ️ Select a sub-store division above. Items will be filtered exclusively for that category.";
            return;
        }

        // Lock category select if items are already staged
        if (stagedItems.length > 0) {
            alert("This Voucher is locked to '" + selectedCategoryCode.toUpperCase() + "' division. Drop staged items first to switch categories.");
            return;
        }

        selectionContainer.style.display = 'block';
        categoryHint.innerHTML = `✅ <strong>Category Active:</strong> Items staged under this RV will update the <strong>${selectedCategoryCode.toUpperCase()}</strong> store.`;

        // Populate dropdown filtered exclusively by selected category
        itemSelect.innerHTML = '<option value="">-- Choose Item --</option>';

        if (selectedCategoryCode === 'workshop') {
            // Populate Workshop Spare Parts
            allSpareParts.forEach(sp => {
                const opt = document.createElement('option');
                opt.value = 'sp_' + sp.id;
                opt.dataset.type = 'existing_spare';
                opt.dataset.id = sp.id;
                opt.dataset.unit = sp.unit || 'Pcs';
                opt.dataset.desc = (sp.machine_name ? '[' + sp.machine_name + '] ' : '') + sp.nomenclature + (sp.part_no ? ' (' + sp.part_no + ')' : '');
                opt.textContent = opt.dataset.desc;
                itemSelect.appendChild(opt);
            });
        } else {
            // Populate General Store Items matching division
            allInventoryItems.forEach(item => {
                const cat = (item.category_name || '').toLowerCase();
                let isMatch = false;

                if (selectedCategoryCode === 'fuel' && (cat.includes('fuel') || cat.includes('lubricant') || cat.includes('oil'))) isMatch = true;
                else if (selectedCategoryCode === 'housekeeping' && (cat.includes('sanitary') || cat.includes('housekeeping'))) isMatch = true;
                else if (selectedCategoryCode === 'stationery' && cat.includes('station')) isMatch = true;
                else if (selectedCategoryCode === 'agronomy' && !cat.includes('fuel') && !cat.includes('spare') && !cat.includes('sanitary') && !cat.includes('housekeeping') && !cat.includes('station')) isMatch = true;

                if (isMatch) {
                    const opt = document.createElement('option');
                    opt.value = 'item_' + item.id;
                    opt.dataset.type = 'existing_item';
                    opt.dataset.id = item.id;
                    opt.dataset.unit = item.ac_unit || 'Pcs';
                    opt.dataset.desc = item.description;
                    opt.textContent = item.description;
                    itemSelect.appendChild(opt);
                }
            });
        }

        // ALWAYS ADD LAST OPTION: "+ Add New Item..."
        const newOpt = document.createElement('option');
        newOpt.value = 'ADD_NEW_ITEM';
        newOpt.dataset.type = 'add_new';
        newOpt.classList.add('fw-bold', 'text-success');
        newOpt.textContent = '✨ + Add New Item / Part Definition...';
        itemSelect.appendChild(newOpt);
    });

    // Handle Item Selection Change (Trigger unit & on-the-fly fields)
    itemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption || selectedOption.value === "") {
            newItemExtraFields.style.display = 'none';
            unitDisplay.textContent = 'Pcs';
            return;
        }

        if (selectedOption.value === 'ADD_NEW_ITEM') {
            newItemExtraFields.style.display = 'block';
            newItemCatLabel.textContent = selectedCategoryCode;

            if (selectedCategoryCode === 'workshop') {
                fieldPartNo.style.display = 'block';
                fieldMachine.style.display = 'block';
            } else {
                fieldPartNo.style.display = 'none';
                fieldMachine.style.display = 'none';
            }
            document.getElementById('new_item_description').focus();
        } else {
            newItemExtraFields.style.display = 'none';
            unitDisplay.textContent = selectedOption.dataset.unit || 'Pcs';
            quantityInput.focus();
        }
    });

    const renderTable = () => {
        if (stagedItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No items staged yet. Add items above.</td></tr>';
            totalBadge.textContent = '0 Items';
            storeCategorySelect.disabled = false;
            return;
        }

        // Lock category select once at least 1 item is staged
        storeCategorySelect.disabled = true;

        tbody.innerHTML = '';
        stagedItems.forEach((item, index) => {
            const tr = document.createElement('tr');
            let badgeTag = item.is_spare_part ? '<span class="badge bg-warning text-dark me-2">Spare Part</span>' : '<span class="badge bg-primary me-2">Store Item</span>';
            if (item.is_new_item) badgeTag += '<span class="badge bg-success ms-1">New Entry</span>';

            tr.innerHTML = `
                <td>${index + 1}</td>
                <td class="fw-bold">${badgeTag} ${item.description}</td>
                <td>${item.quantity}</td>
                <td>${item.unit}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-btn" data-index="${index}">Drop</button></td>
            `;
            tbody.appendChild(tr);
        });
        totalBadge.textContent = `${stagedItems.length} Items`;
    };

    // Stage Item Button Event
    stageItemBtn.addEventListener('click', () => {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const val = itemSelect.value;
        const qty = parseFloat(quantityInput.value);

        if (!val) { alert("Please select an item or choose '+ Add New Item...'"); return; }
        if (!qty || qty <= 0) { alert("Please enter a valid quantity."); return; }

        if (val === 'ADD_NEW_ITEM') {
            // Stage Brand New Item
            const desc = document.getElementById('new_item_description').value.trim();
            const unit = document.getElementById('new_item_unit').value.trim() || 'Pcs';

            if (!desc) { alert("Please enter the new item description / nomenclature."); return; }

            if (selectedCategoryCode === 'workshop') {
                const mId = document.getElementById('new_machine_id').value;
                const pNo = document.getElementById('new_part_no').value.trim();

                if (!mId) { alert("Please select an associated machine for this new spare part."); return; }

                stagedItems.push({
                    description: desc + (pNo ? ` (${pNo})` : ''),
                    unit: unit,
                    quantity: qty,
                    is_spare_part: true,
                    is_new_item: true,
                    machine_id: mId,
                    part_no: pNo
                });
            } else {
                stagedItems.push({
                    description: desc,
                    unit: unit,
                    quantity: qty,
                    is_spare_part: false,
                    is_new_item: true,
                    category_code: selectedCategoryCode
                });
            }

            // Reset new item fields
            document.getElementById('new_item_description').value = '';
            document.getElementById('new_part_no').value = '';
            newItemExtraFields.style.display = 'none';
        } else if (selectedOption.dataset.type === 'existing_spare') {
            // Stage Existing Spare Part
            stagedItems.push({
                spare_part_id: selectedOption.dataset.id,
                description: selectedOption.dataset.desc,
                unit: selectedOption.dataset.unit,
                quantity: qty,
                is_spare_part: true,
                is_new_item: false
            });
        } else {
            // Stage Existing Store Item
            stagedItems.push({
                item_id: selectedOption.dataset.id,
                description: selectedOption.dataset.desc,
                unit: selectedOption.dataset.unit,
                quantity: qty,
                is_spare_part: false,
                is_new_item: false
            });
        }

        renderTable();

        // Reset inputs
        itemSelect.value = '';
        quantityInput.value = '';
        unitDisplay.textContent = 'Pcs';
    });

    tbody.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-btn')) {
            const index = parseInt(e.target.getAttribute('data-index'), 10);
            stagedItems.splice(index, 1);
            renderTable();
        }
    });

    // Commit Receipt Voucher Event
    commitBtn.addEventListener('click', async () => {
        const officeSelect = document.getElementById('master_office_id');
        const voucherNoInput = document.getElementById('master_voucher_no');
        const dateInput = document.getElementById('master_date');
        const partyInput = document.getElementById('master_party');

        const voucherNo = voucherNoInput.value.trim();
        const txDate = dateInput.value;
        const officeId = userRole === 'superuser' ? (officeSelect ? officeSelect.value : null) : currentUserOfficeId;

        if (!selectedCategoryCode) { alert("Please select a Sub-Store Category for this RV."); return; }
        if (!voucherNo) { alert("Voucher Number is required."); voucherNoInput.focus(); return; }
        if (!txDate) { alert("Date is required."); dateInput.focus(); return; }
        if (userRole === 'superuser' && !officeId) { alert("Office is required."); officeSelect.focus(); return; }
        if (stagedItems.length === 0) { alert("You must add at least one item to the voucher before committing."); return; }

        const masterData = {
            office_id: officeId,
            voucher_no: voucherNo,
            transaction_date: txDate,
            party: partyInput.value.trim(),
            category_code: selectedCategoryCode
        };

        const originalText = commitBtn.innerText;
        commitBtn.innerText = 'Processing Transaction...';
        commitBtn.disabled = true;

        try {
            const response = await fetch('?action=createBulkReceipt', {
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
            window.location.href = '/modules/Workshop/Machine/TurfManagement/InventoryItems/Controller/InventoryItemController.php?action=list';

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
