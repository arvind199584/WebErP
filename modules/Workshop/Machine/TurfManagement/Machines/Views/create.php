<form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
    <div class="modal-header">
        <h5 class="modal-title">Add New Machine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <?php if ($userRole === 'superuser'): ?>
            <div class="mb-3">
                <label for="office_id" class="form-label">Office</label>
                <select id="office_id" name="office_id" class="form-select" required>
                    <option value="">-- Select Office --</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?php echo $office['officeid']; ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="name" class="form-label">Machine Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="mb-3">
            <label for="make" class="form-label">Make / Brand</label>
            <input type="text" class="form-control" id="make" name="make">
        </div>

        <div class="mb-3">
            <label for="fuel_item_id" class="form-label">Fuel Type</label>
            <select id="fuel_item_id" name="fuel_item_id" class="form-select">
                <option value="">None</option>
                <?php foreach ($inventoryItems as $item): ?>
                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['description']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Routine Servicing Milestones & Interval -->
        <div class="card p-3 border mb-3 bg-light-subtle">
            <h6 class="fw-bold text-primary mb-2">⏱️ Routine Servicing Milestones & Interval</h6>
            <div class="row g-2">
                <div class="col-md-4">
                    <label for="service_interval_hours" class="form-label fw-bold small mb-1">Service Interval (Hours) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-sm fw-bold" id="service_interval_hours" name="service_interval_hours" value="100" min="1" step="1" required>
                    <div class="form-text small">Interval between routines (e.g. 100 hrs).</div>
                </div>
                <div class="col-md-4">
                    <label for="create_service_done" class="form-label fw-bold small text-success mb-1">Initial Service Done (Hours)</label>
                    <input type="number" class="form-control form-control-sm fw-bold border-success" id="create_service_done" name="service_done" value="0" min="0" step="0.1">
                    <div class="form-text small">Initial reading if already serviced.</div>
                </div>
                <div class="col-md-4">
                    <label for="create_service_due" class="form-label fw-bold small text-primary mb-1">Next Service Due (Hours)</label>
                    <input type="number" class="form-control form-control-sm fw-bold border-primary" id="create_service_due" name="service_due" value="100" min="0" step="0.1">
                    <div class="form-text small">Auto-calculated: Done + Interval.</div>
                </div>
            </div>
        </div>

        <!-- Routine Servicing Requirements / Default Service Kit -->
        <div class="border rounded p-3 bg-light mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold text-primary">⚙️ Routine Service Kit Requirements</h6>
                <span class="badge bg-secondary" id="create_service_items_count_badge">0 Spares Configured</span>
            </div>
            <p class="text-muted small mb-3">
                Configure parts and lubricants automatically required when this machine undergoes routine servicing. You can add one or more spare parts (filters, plugs, belts, etc.) in addition to mandatory engine oil.
            </p>

            <!-- Mandatory Engine Oil Requirement Field -->
            <div class="mb-3 p-3 bg-white border rounded">
                <label for="create_engine_oil_qty" class="form-label fw-bold text-primary mb-1">
                    🛢️ Mandatory Engine Oil Requirement (Liters)
                </label>
                <div class="input-group">
                    <input type="number" class="form-control form-control-lg fw-bold" id="create_engine_oil_qty" name="engine_oil_qty" 
                           value="0.00" min="0" step="0.01" placeholder="e.g. 2.50">
                    <span class="input-group-text fw-bold">Liters</span>
                </div>
                <div class="form-text text-dark">
                    Engine oil volume required for routine servicing of this machine (e.g. 2.5 Liters). Set 0 if not applicable.
                </div>
            </div>

            <!-- Additional Spares (Filters, Belts, Plugs, etc.) -->
            <h6 class="fw-bold small text-dark mb-2">Additional Spares Required (Filters, Belts, Plugs, Blades, etc.):</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white mb-2" id="create-service-items-table">
                    <thead class="table-light">
                        <tr>
                            <th>Spare Part / Consumable</th>
                            <th style="width: 140px;">Qty Required</th>
                            <th style="width: 80px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="create-service-items-body">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div class="card p-2 bg-white border">
                <label class="form-label small fw-bold text-dark mb-1">➕ Add Spare Part to Default Service Requirements:</label>
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <select id="create_add_service_part_select" class="form-select form-select-sm">
                            <option value="">-- Choose Spare Part from Workshop Catalog --</option>
                            <?php if (!empty($spareParts)): ?>
                                <?php foreach ($spareParts as $sp): ?>
                                    <?php
                                        $displayName = ($sp['part_no'] ? $sp['part_no'] . ' - ' : '') . $sp['nomenclature'];
                                        $suffix = ' (' . ($sp['unit'] ?? 'Pcs') . ')';
                                        if (!empty($sp['machine_name'])) {
                                            $suffix .= ' [' . $sp['machine_name'] . ']';
                                        }
                                    ?>
                                    <option value="<?php echo $sp['id']; ?>" data-name="<?php echo htmlspecialchars($displayName); ?>" data-unit="<?php echo htmlspecialchars($sp['unit'] ?? 'Pcs'); ?>">
                                        <?php echo htmlspecialchars($displayName . $suffix); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No spare parts found in inventory</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <input type="number" id="create_add_service_part_qty" class="form-control" value="1.0" min="0.01" step="0.01" placeholder="Qty">
                            <span class="input-group-text" id="create_add_service_part_unit_label">Qty</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-success w-100 fw-bold" id="create_btn_add_service_item">
                            ➕ Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hidden input to store JSON array of service kit items -->
            <input type="hidden" name="default_service_items" id="create_service_items_json" value="[]">
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="runduration" name="runduration">
            <label class="form-check-label" for="runduration">
                Tracks Run Duration (Hours)
            </label>
        </div>

        <!-- Smart Auto-Backfill Options -->
        <div id="create-auto-backfill-options" style="display: none;">
            <div class="form-check mb-3 ms-3">
                <input class="form-check-input" type="checkbox" value="1" id="create_daily_run" name="daily_run">
                <label class="form-check-label" for="create_daily_run">
                    This machine runs daily (Enable Auto-Backfill)
                </label>
            </div>

            <div id="create-rest-day-options" class="ms-3" style="display: none;">
                <div class="form-check mb-3 ms-3">
                    <input class="form-check-input" type="checkbox" value="1" id="create_has_rest_day" name="has_rest_day">
                    <label class="form-check-label" for="create_has_rest_day">
                        Has a scheduled weekly rest day
                    </label>
                </div>

                <div id="create-rest-day-select-container" class="mb-3 ms-5" style="display: none;">
                    <label for="create_rest_day" class="form-label">Rest Day</label>
                    <select id="create_rest_day" name="rest_day" class="form-select form-select-sm">
                        <option value="-1" selected>No Rest Day</option>
                        <option value="0">Sunday</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="Working" selected>Working</option>
                <option value="OffRoad">Off-Road</option>
                <option value="Condemned">Condemned</option>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Machine</button>
    </div>
</form>

<script>
    (function() {
        const rundurationCheck = document.getElementById('runduration');
        const backfillOptions = document.getElementById('create-auto-backfill-options');
        const dailyRunCheck = document.getElementById('create_daily_run');
        const restDayOptions = document.getElementById('create-rest-day-options');
        const hasRestDayCheck = document.getElementById('create_has_rest_day');
        const restDaySelectContainer = document.getElementById('create-rest-day-select-container');

        const serviceIntervalInput = document.getElementById('service_interval_hours');
        const serviceDoneInput = document.getElementById('create_service_done');
        const serviceDueInput = document.getElementById('create_service_due');

        const updateServiceDue = () => {
            if (!serviceIntervalInput || !serviceDoneInput || !serviceDueInput) return;
            const interval = parseFloat(serviceIntervalInput.value) || 100;
            const done = parseFloat(serviceDoneInput.value) || 0;
            serviceDueInput.value = (done + interval).toFixed(1).replace(/\.0$/, '');
        };

        if (serviceDoneInput) serviceDoneInput.addEventListener('input', updateServiceDue);
        if (serviceIntervalInput) serviceIntervalInput.addEventListener('input', updateServiceDue);

        const updateVisibility = () => {
            if(rundurationCheck) backfillOptions.style.display = rundurationCheck.checked ? 'block' : 'none';
            if(dailyRunCheck) restDayOptions.style.display = dailyRunCheck.checked ? 'block' : 'none';
            if(hasRestDayCheck) restDaySelectContainer.style.display = hasRestDayCheck.checked ? 'block' : 'none';
        };

        if(rundurationCheck) {
            rundurationCheck.addEventListener('change', () => {
                if (!rundurationCheck.checked) {
                    if(dailyRunCheck) dailyRunCheck.checked = false;
                    if(hasRestDayCheck) hasRestDayCheck.checked = false;
                }
                updateVisibility();
            });
        }
        if(dailyRunCheck) {
            dailyRunCheck.addEventListener('change', () => {
                if (!dailyRunCheck.checked) {
                    if(hasRestDayCheck) hasRestDayCheck.checked = false;
                }
                updateVisibility();
            });
        }
        if(hasRestDayCheck) {
            hasRestDayCheck.addEventListener('change', () => {
                updateVisibility();
            });
        }

        updateVisibility();

        // Routine Service Kit items management for Create form
        const sparePartsMap = <?php
            $spMap = [];
            if (!empty($spareParts)) {
                foreach ($spareParts as $sp) {
                    $spMap[$sp['id']] = [
                        'name' => ($sp['part_no'] ? $sp['part_no'] . ' - ' : '') . $sp['nomenclature'],
                        'unit' => $sp['unit'] ?? 'Pcs'
                    ];
                }
            }
            echo json_encode($spMap);
        ?>;

        let serviceItems = [];
        const tableBody = document.getElementById('create-service-items-body');
        const partSelect = document.getElementById('create_add_service_part_select');
        const partQtyInput = document.getElementById('create_add_service_part_qty');
        const unitLabel = document.getElementById('create_add_service_part_unit_label');
        const countBadge = document.getElementById('create_service_items_count_badge');
        const btnAdd = document.getElementById('create_btn_add_service_item');
        const hiddenInput = document.getElementById('create_service_items_json');

        if (partSelect && unitLabel) {
            partSelect.addEventListener('change', function() {
                const selectedOpt = partSelect.options[partSelect.selectedIndex];
                const unit = selectedOpt ? selectedOpt.getAttribute('data-unit') : '';
                unitLabel.textContent = unit || 'Qty';
            });
        }

        function renderServiceItems() {
            if (!tableBody) return;
            tableBody.innerHTML = '';
            if (!serviceItems || serviceItems.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-2">No additional spare parts configured yet. Select a spare part below and click ➕ Add.</td></tr>';
            } else {
                serviceItems.forEach((item, index) => {
                    const spInfo = sparePartsMap[item.spare_part_id] || { name: 'Part #' + item.spare_part_id, unit: 'Pcs' };
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="align-middle fw-semibold">${spInfo.name}</td>
                        <td class="align-middle">
                            <span class="badge bg-light text-dark border px-2 py-1 fs-6">${item.quantity} ${spInfo.unit}</span>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-del-item" data-index="${index}" title="Remove Part">&times; Remove</button>
                        </td>
                    `;
                    tableBody.appendChild(tr);
                });
            }
            if (countBadge) {
                const count = serviceItems ? serviceItems.length : 0;
                countBadge.textContent = count + (count === 1 ? ' Spare Configured' : ' Spares Configured');
                countBadge.className = 'badge ' + (count > 0 ? 'bg-primary' : 'bg-secondary');
            }
            if (hiddenInput) {
                hiddenInput.value = JSON.stringify(serviceItems);
            }
        }

        if (tableBody) {
            tableBody.addEventListener('click', function(e) {
                const delBtn = e.target.closest('.btn-del-item');
                if (delBtn) {
                    const idx = parseInt(delBtn.getAttribute('data-index'), 10);
                    serviceItems.splice(idx, 1);
                    renderServiceItems();
                }
            });
        }

        if (btnAdd) {
            btnAdd.addEventListener('click', function(e) {
                if (e) e.preventDefault();
                const partId = parseInt(partSelect.value, 10);
                const qty = parseFloat(partQtyInput.value);

                if (!partId) {
                    alert('Please select a spare part from the dropdown.');
                    partSelect.focus();
                    return;
                }
                if (isNaN(qty) || qty <= 0) {
                    alert('Please enter a valid quantity greater than 0.');
                    partQtyInput.focus();
                    return;
                }

                const existingIdx = serviceItems.findIndex(i => parseInt(i.spare_part_id, 10) === partId);
                if (existingIdx >= 0) {
                    serviceItems[existingIdx].quantity = qty;
                } else {
                    serviceItems.push({
                        spare_part_id: partId,
                        quantity: qty
                    });
                }

                partSelect.value = '';
                if (unitLabel) unitLabel.textContent = 'Qty';
                partQtyInput.value = '1.0';
                renderServiceItems();
            });
        }

        renderServiceItems();
    })();
</script>