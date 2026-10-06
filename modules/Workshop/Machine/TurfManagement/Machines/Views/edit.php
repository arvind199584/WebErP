<form method="POST" action="?action=update" id="edit-machine-form">
    <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$machine['id']); ?>">
    <input type="hidden" name="service_items_json" id="edit_service_items_json" value="">

    <div class="modal-header">
        <h5 class="modal-title">Edit Machine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <?php if ($userRole === 'superuser'): ?>
            <div class="mb-3">
                <label for="edit_office_id" class="form-label">Office</label>
                <select id="edit_office_id" name="office_id" class="form-select" required>
                    <option value="">-- Select Office --</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>" <?php echo ($machine['officeid'] == $office['officeid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($office['officename']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="edit_name" class="form-label">Machine Name</label>
            <input type="text" class="form-control" id="edit_name" name="name" value="<?php echo htmlspecialchars($machine['name']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="edit_make" class="form-label">Make / Brand</label>
            <input type="text" class="form-control" id="edit_make" name="make" value="<?php echo htmlspecialchars($machine['make'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="edit_fuel_item_id" class="form-label">Fuel Type</label>
            <select id="edit_fuel_item_id" name="fuel_item_id" class="form-select">
                <option value="">None</option>
                <?php foreach ($inventoryItems as $item): ?>
                    <option value="<?php echo htmlspecialchars((string)$item['id']); ?>" <?php echo ($machine['fuel_item_id'] == $item['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($item['description']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Routine Servicing Milestones & Interval -->
        <div class="card p-3 border mb-3 bg-light-subtle">
            <h6 class="fw-bold text-primary mb-2">⏱️ Routine Servicing Milestones & Interval</h6>
            <div class="row g-2">
                <div class="col-md-4">
                    <label for="edit_service_interval_hours" class="form-label fw-bold small mb-1">Service Interval (Hours) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-sm fw-bold" id="edit_service_interval_hours" name="service_interval_hours" value="<?php echo htmlspecialchars((string)($machine['service_interval_hours'] ?? 100)); ?>" min="1" step="1" required>
                    <div class="form-text small">Interval between routines (e.g. 100 hrs).</div>
                </div>
                <div class="col-md-4">
                    <label for="edit_service_done" class="form-label fw-bold small text-success mb-1">Last Service Done (Meter Hours)</label>
                    <input type="number" class="form-control form-control-sm fw-bold border-success" id="edit_service_done" name="service_done" value="<?php echo htmlspecialchars((string)($machine['service_done'] ?? 0)); ?>" min="0" step="0.1">
                    <div class="form-text small">Milestone reading at last service.</div>
                </div>
                <div class="col-md-4">
                    <label for="edit_service_due" class="form-label fw-bold small text-primary mb-1">Next Service Due (Meter Hours)</label>
                    <input type="number" class="form-control form-control-sm fw-bold border-primary" id="edit_service_due" name="service_due" value="<?php echo htmlspecialchars((string)($machine['service_due'] ?? (($machine['service_done'] ?? 0) + ($machine['service_interval_hours'] ?? 100)))); ?>" min="0" step="0.1">
                    <div class="form-text small">Auto-calculated: Done + Interval.</div>
                </div>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="edit_runduration" name="runduration" <?php echo ($machine['runduration'] ?? false) ? 'checked' : ''; ?>>
            <label class="form-check-label fw-bold" for="edit_runduration">
                Tracks Run Duration (Hours)
            </label>
        </div>

        <!-- Smart Auto-Backfill Options -->
        <div id="edit-auto-backfill-options" style="display: <?php echo ($machine['runduration'] ?? false) ? 'block' : 'none'; ?>;">
            <div class="form-check mb-3 ms-3">
                <input class="form-check-input" type="checkbox" value="1" id="edit_daily_run" name="daily_run" <?php echo ($machine['daily_run'] ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="edit_daily_run">
                    This machine runs daily (Enable Auto-Backfill)
                </label>
            </div>

            <div id="edit-rest-day-options" class="ms-3" style="display: <?php echo (($machine['runduration'] ?? false) && ($machine['daily_run'] ?? false)) ? 'block' : 'none'; ?>;">
                <div class="form-check mb-3 ms-3">
                    <input class="form-check-input" type="checkbox" value="1" id="edit_has_rest_day" name="has_rest_day" <?php echo ($machine['has_rest_day'] ?? false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="edit_has_rest_day">
                        Has a scheduled weekly rest day
                    </label>
                </div>

                <div id="edit-rest-day-select-container" class="mb-3 ms-5" style="display: <?php echo (($machine['runduration'] ?? false) && ($machine['daily_run'] ?? false) && ($machine['has_rest_day'] ?? false)) ? 'block' : 'none'; ?>;">
                    <label for="edit_rest_day" class="form-label">Rest Day</label>
                    <select id="edit_rest_day" name="rest_day" class="form-select form-select-sm">
                        <option value="-1" <?php echo (($machine['rest_day'] ?? -1) == -1) ? 'selected' : ''; ?>>No Rest Day</option>
                        <option value="0" <?php echo (($machine['rest_day'] ?? -1) == 0) ? 'selected' : ''; ?>>Sunday</option>
                        <option value="1" <?php echo (($machine['rest_day'] ?? -1) == 1) ? 'selected' : ''; ?>>Monday</option>
                        <option value="2" <?php echo (($machine['rest_day'] ?? -1) == 2) ? 'selected' : ''; ?>>Tuesday</option>
                        <option value="3" <?php echo (($machine['rest_day'] ?? -1) == 3) ? 'selected' : ''; ?>>Wednesday</option>
                        <option value="4" <?php echo (($machine['rest_day'] ?? -1) == 4) ? 'selected' : ''; ?>>Thursday</option>
                        <option value="5" <?php echo (($machine['rest_day'] ?? -1) == 5) ? 'selected' : ''; ?>>Friday</option>
                        <option value="6" <?php echo (($machine['rest_day'] ?? -1) == 6) ? 'selected' : ''; ?>>Saturday</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="edit_status" class="form-label">Status</label>
            <select id="edit_status" name="status" class="form-select">
                <option value="Working" <?php echo ($machine['status'] === 'Working') ? 'selected' : ''; ?>>Working</option>
                <option value="OffRoad" <?php echo ($machine['status'] === 'OffRoad') ? 'selected' : ''; ?>>Off-Road</option>
                <option value="Condemned" <?php echo ($machine['status'] === 'Condemned') ? 'selected' : ''; ?>>Condemned</option>
            </select>
        </div>

        <!-- Routine Servicing Requirements / Default Service Kit -->
        <div class="border rounded p-3 bg-light mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold text-primary">⚙️ Routine Service Kit Requirements</h6>
                <span class="badge bg-secondary" id="service_items_count_badge">0 Spares Configured</span>
            </div>
            <p class="text-muted small mb-3">
                Configure parts and lubricants automatically required when this machine undergoes routine servicing. You can add one or more spare parts (filters, plugs, belts, etc.) in addition to mandatory engine oil.
            </p>

            <!-- Mandatory Engine Oil Requirement Field -->
            <div class="mb-3 p-3 bg-white border rounded">
                <label for="edit_engine_oil_qty" class="form-label fw-bold text-primary mb-1">
                    🛢️ Mandatory Engine Oil Requirement (Liters) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control form-control-lg fw-bold" id="edit_engine_oil_qty" name="engine_oil_qty" 
                           value="<?php echo htmlspecialchars((string)($machine['engine_oil_qty'] ?? '0.00')); ?>" 
                           min="0" step="0.01" placeholder="e.g. 2.50">
                    <span class="input-group-text fw-bold">Liters</span>
                </div>
                <div class="form-text text-dark">
                    Engine oil volume required for routine servicing of this machine (e.g. 2.5 Liters). Set 0 if not applicable.
                </div>
            </div>

            <!-- Additional Spares (Filters, Belts, Plugs, etc.) -->
            <h6 class="fw-bold small text-dark mb-2">Additional Spares Required (Filters, Belts, Plugs, Blades, etc.):</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white mb-2" id="edit-service-items-table">
                    <thead class="table-light">
                        <tr>
                            <th>Spare Part / Consumable</th>
                            <th style="width: 140px;">Qty Required</th>
                            <th style="width: 80px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="edit-service-items-body">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div class="card p-2 bg-white border">
                <label class="form-label small fw-bold text-dark mb-1">➕ Add Spare Part to Default Service Requirements:</label>
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <select id="add_service_part_select" class="form-select form-select-sm">
                            <option value="">-- Choose Spare Part from Workshop Catalog --</option>
                            <?php if (!empty($spareParts)): ?>
                                <?php foreach ($spareParts as $sp): ?>
                                    <?php
                                        $displayName = ($sp['part_no'] ? $sp['part_no'] . ' - ' : '') . $sp['nomenclature'];
                                        $suffix = ' (' . ($sp['unit'] ?? 'Pcs') . ')';
                                        if ($sp['machine_id'] == $machine['id']) {
                                            $suffix .= ' ⭐ [This Machine]';
                                        } elseif (!empty($sp['machine_name'])) {
                                            $suffix .= ' [' . $sp['machine_name'] . ']';
                                        }
                                    ?>
                                    <option value="<?php echo $sp['id']; ?>" data-name="<?php echo htmlspecialchars($displayName); ?>" data-unit="<?php echo htmlspecialchars($sp['unit'] ?? 'Pcs'); ?>">
                                        <?php echo htmlspecialchars($displayName . $suffix); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" id="add_service_part_qty" class="form-control form-control-sm" value="1.0" placeholder="Qty">
                            <span class="input-group-text" id="add_service_part_unit_label">Qty</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-success w-100 fw-bold" id="btn_add_service_item">➕ Add</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>

<script>
    (function() {
        const rundurationCheck = document.getElementById('edit_runduration');
        const backfillOptions = document.getElementById('edit-auto-backfill-options');
        const dailyRunCheck = document.getElementById('edit_daily_run');
        const restDayOptions = document.getElementById('edit-rest-day-options');
        const hasRestDayCheck = document.getElementById('edit_has_rest_day');
        const restDaySelectContainer = document.getElementById('edit-rest-day-select-container');

        const serviceIntervalInput = document.getElementById('edit_service_interval_hours');
        const serviceDoneInput = document.getElementById('edit_service_done');
        const serviceDueInput = document.getElementById('edit_service_due');

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

        // Service Kit Items management
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

        let serviceItems = <?php
            $rawItems = is_array($machine['default_service_items'] ?? null) 
                ? $machine['default_service_items'] 
                : (json_decode($machine['default_service_items'] ?? '[]', true) ?: []);
            echo json_encode(array_values($rawItems));
        ?>;

        const tableBody = document.getElementById('edit-service-items-body');
        const partSelect = document.getElementById('add_service_part_select');
        const partQtyInput = document.getElementById('add_service_part_qty');
        const unitLabel = document.getElementById('add_service_part_unit_label');
        const countBadge = document.getElementById('service_items_count_badge');
        const btnAdd = document.getElementById('btn_add_service_item');
        const hiddenInput = document.getElementById('edit_service_items_json');
        const editForm = document.getElementById('edit-machine-form');

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

        if (partQtyInput && btnAdd) {
            partQtyInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnAdd.click();
                }
            });
        }

        if (editForm) {
            editForm.addEventListener('submit', function() {
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(serviceItems);
                }
            });
        }

        renderServiceItems();
    })();
</script>
