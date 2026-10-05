<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Machine Repair & Servicing Work</title>
</head>
<body>
<div class="container-fluid py-3" style="max-width: 960px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🛠️ Log Machine Repair & Servicing</h1>
            <p class="text-muted mb-0">Record routine servicing, maintenance, job cards, and spare parts consumption.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=jobCards" class="btn btn-outline-primary">📋 Job Cards Register</a>
            <a href="?action=list" class="btn btn-outline-secondary">← Back to List</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="?action=create" id="repair-create-form">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <input type="hidden" name="items_json" id="items_json" value="[]">

                <div class="row g-3">

                    <!-- Machine Selection -->
                    <div class="col-md-6">
                        <label for="machine_id" class="form-label fw-bold">Select Machine <span class="text-danger">*</span></label>
                        <select name="machine_id" id="machine_id" class="form-select form-select-lg" required>
                            <option value="">-- Choose Machine --</option>
                            <?php foreach ($machines as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($selectedMachineId == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Repair Date -->
                    <div class="col-md-6">
                        <label for="repair_date" class="form-label fw-bold">Date of Work <span class="text-danger">*</span></label>
                        <input type="date" name="repair_date" id="repair_date" class="form-control form-control-lg" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <!-- Live Machine Service Status Banner -->
                    <div class="col-12" id="machine-status-banner" style="display: none;">
                        <div class="alert alert-info py-2 px-3 mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="fw-bold" id="banner-machine-name">Machine</span>: 
                                <span class="me-3">Last Service: <strong id="banner-service-done">0</strong> hrs</span>
                                <span class="me-3">Next Service Due: <strong id="banner-service-due">100</strong> hrs</span>
                                <span class="me-3">Service Interval: <strong id="banner-interval">100</strong> hrs</span>
                                <span class="me-3">Engine Oil Required: <strong id="banner-oil-qty">0.00</strong> Ltr</span>
                            </div>
                            <div id="banner-service-alert"></div>
                        </div>
                    </div>

                    <!-- Work Done Menu Selection -->
                    <div class="col-12 border-top pt-3 mt-3">
                        <label class="form-label fw-bold text-dark fs-6 mb-2">Select Type of Work Performed <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card p-3 border-2 h-100 cursor-pointer work-type-card active" id="card-service-done" onclick="setWorkType('service_done')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="work_type" id="work_type_service" value="service_done" checked>
                                        <label class="form-check-label fw-bold text-primary" for="work_type_service">
                                            🛠️ Service Done (Routine / Scheduled Servicing)
                                        </label>
                                    </div>
                                    <small class="text-muted ps-4 d-block mt-1">
                                        Requires Job Card. <strong>Engine oil is mandatory</strong> along with additional filters/parts. Updates service milestone.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card p-3 border h-100 cursor-pointer work-type-card" id="card-add-repair" onclick="setWorkType('repair')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="work_type" id="work_type_repair" value="repair">
                                        <label class="form-check-label fw-bold text-dark" for="work_type_repair">
                                            ➕ Add New Work (General Repair / Maintenance / Breakdown)
                                        </label>
                                    </div>
                                    <small class="text-muted ps-4 d-block mt-1">
                                        Routine repair (Backlapping, Piston Cleaning, Tightening, Welding, etc.). Spare parts optional.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Work Done Details (Custom / Select) -->
                    <div class="col-md-12" id="work-details-section">
                        <label for="work_done" class="form-label fw-bold">Work Done Details <span class="text-danger">*</span></label>

                        <!-- Repair Specific Quick Tasks Dropdown -->
                        <div id="repair-task-selector-container" style="display: none;" class="mb-2">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-7">
                                    <select id="quick_work_select" class="form-select">
                                        <option value="">-- Choose Quick Work / Previous Task --</option>
                                        <optgroup label="Standard Maintenance Tasks">
                                            <option value="Backlapping">Backlapping</option>
                                            <option value="Blade Sharpening & Balancing">Blade Sharpening & Balancing</option>
                                            <option value="Piston & Carburetor Cleaning">Piston & Carburetor Cleaning</option>
                                            <option value="Screw & Bolt Tightening">Screw & Bolt Tightening</option>
                                            <option value="Belt & Chain Tension Adjustment">Belt & Chain Tension Adjustment</option>
                                            <option value="Greasing & Lubrication">Greasing & Lubrication</option>
                                            <option value="Reel Height Adjustment">Reel Height Adjustment</option>
                                            <option value="Tire Puncture / Pressure Check">Tire Puncture / Pressure Check</option>
                                        </optgroup>
                                        <optgroup label="Saved Tasks for this Machine" id="machine-custom-tasks-optgroup">
                                            <!-- Dynamically filled -->
                                        </optgroup>
                                        <option value="__custom__">➕ Type Custom Task Description...</option>
                                    </select>
                                </div>
                                <div class="col-md-5" id="save-task-checkbox-container" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="save_custom_work" id="save_custom_work" value="1" checked>
                                        <label class="form-check-label small" for="save_custom_work">
                                            💾 Remember this task for this machine in the future
                                        </label>
                                    </div>
                                    <input type="hidden" name="custom_work_title" id="custom_work_title" value="">
                                </div>
                            </div>
                        </div>

                        <textarea name="work_done" id="work_done" rows="2" class="form-control" placeholder="Describe work done or routine service performed..." required>Routine Scheduled Servicing (Oil & Filter Change)</textarea>
                    </div>

                    <!-- Meter Reading (Running Hours) -->
                    <div class="col-md-6">
                        <label for="running_hours" class="form-label fw-bold">
                            Meter Reading (Running Hours) <span class="text-danger" id="hours_required_asterisk">*</span>
                        </label>
                        <input type="number" step="0.1" min="0" name="running_hours" id="running_hours" class="form-control" placeholder="e.g. 250.5" required>
                        <div class="form-text">Current hour meter reading of the machine.</div>
                    </div>

                    <!-- Total Cost -->
                    <div class="col-md-6">
                        <label for="cost" class="form-label fw-bold">Total Work Cost (₹)</label>
                        <input type="number" step="0.01" min="0" name="cost" id="cost" class="form-control" placeholder="0.00" value="0.00">
                    </div>

                    <!-- For General Repair: Option whether spare parts are required -->
                    <div class="col-12" id="repair-parts-toggle-container" style="display: none;">
                        <div class="form-check p-3 bg-light rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="repair_requires_parts" name="repair_requires_parts" value="1">
                            <label class="form-check-label fw-bold text-dark" for="repair_requires_parts">
                                📦 This repair requires spare parts replacement (Job Card mandatory)
                            </label>
                        </div>
                    </div>

                    <!-- Job Card & Spare Parts Container -->
                    <div class="col-12" id="job-card-and-parts-section">
                        <div class="card border border-primary bg-light p-3">
                            
                            <!-- Job Card Number Field -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="job_card_no" class="form-label fw-bold text-primary">
                                        Job ID / Job Card Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="job_card_no" id="job_card_no" class="form-control form-control-lg bg-white" placeholder="e.g. JC-2026-001 or JOB-105" required>
                                    <div class="form-text">Mandatory job identifier for workshop audit, billing, and parts traceability.</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="p-2 border rounded bg-white w-100 text-muted small">
                                        ℹ️ One Job Card can issue multiple spare parts/consumables for this machine. Stock is automatically deducted upon saving.
                                    </div>
                                </div>
                            </div>

                            <!-- Spare Parts List Table -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">⚙️ Spare Parts & Consumables Consumed</h6>
                                <span class="badge bg-warning text-dark" id="oil-must-notice" style="display: none;">🛢️ Engine Oil is mandatory for servicing</span>
                            </div>
                            
                            <div class="table-responsive bg-white rounded border mb-3">
                                <table class="table table-sm table-bordered align-middle mb-0" id="parts-issued-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Part No. & Nomenclature</th>
                                            <th style="width: 130px;" class="text-center">Available Stock</th>
                                            <th style="width: 150px;" class="text-center">Qty to Issue <span class="text-danger">*</span></th>
                                            <th style="width: 70px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="parts-issued-tbody">
                                        <!-- Dynamically populated via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Add Spare Part Selector Row -->
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label for="picker_spare_part_id" class="form-label small fw-bold mb-1">Add More Spares (Filters, Belts, Plugs, etc.)</label>
                                    <select id="picker_spare_part_id" class="form-select form-select-sm">
                                        <option value="">-- Choose Spare Part from Catalog --</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="picker_quantity" class="form-label small fw-bold mb-1">Quantity</label>
                                    <input type="number" step="0.01" min="0.01" id="picker_quantity" class="form-control form-control-sm" placeholder="e.g. 1.0">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-sm btn-success w-100" id="btn-add-part-to-table">
                                        ➕ Add to Job Card
                                    </button>
                                </div>
                            </div>

                            <!-- Save Default Service Kit Option (Only visible for Service Done) -->
                            <div class="mt-3 pt-2 border-top" id="save-default-kit-container">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="save_default_service_kit" id="save_default_service_kit" value="1">
                                    <label class="form-check-label small fw-bold text-dark" for="save_default_service_kit">
                                        💾 Save these items as default Routine Service Kit for this machine
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Technician / Mechanic -->
                    <div class="col-md-6">
                        <label for="repaired_by" class="form-label fw-bold">Technician / Mechanic Name</label>
                        <input type="text" name="repaired_by" id="repaired_by" class="form-control" placeholder="e.g. In-house Mechanic / External Vendor">
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">Work Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Completed" selected>Completed</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Pending Parts">Pending Parts</option>
                        </select>
                    </div>

                    <!-- Remarks -->
                    <div class="col-12">
                        <label for="remarks" class="form-label fw-bold">Additional Remarks / Observations</label>
                        <input type="text" name="remarks" id="remarks" class="form-control" placeholder="Optional notes, condition of oil/blades, etc.">
                    </div>

                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="btn-save-repair">Save Record & Deduct Parts</button>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const machineSelect = document.getElementById('machine_id');
    const runningHoursInput = document.getElementById('running_hours');
    const workDoneTextarea = document.getElementById('work_done');
    const quickWorkSelect = document.getElementById('quick_work_select');
    const repairTaskSelectorContainer = document.getElementById('repair-task-selector-container');
    const saveTaskCheckboxContainer = document.getElementById('save-task-checkbox-container');
    const customWorkTitleInput = document.getElementById('custom_work_title');
    const repairRequiresPartsCheck = document.getElementById('repair_requires_parts');
    const repairPartsToggleContainer = document.getElementById('repair-parts-toggle-container');
    const jobCardSection = document.getElementById('job-card-and-parts-section');
    const jobCardInput = document.getElementById('job_card_no');
    const saveDefaultKitContainer = document.getElementById('save-default-kit-container');
    const oilMustNotice = document.getElementById('oil-must-notice');

    const pickerPartSelect = document.getElementById('picker_spare_part_id');
    const pickerQtyInput = document.getElementById('picker_quantity');
    const btnAddPart = document.getElementById('btn-add-part-to-table');
    const partsTbody = document.getElementById('parts-issued-tbody');
    const itemsJsonInput = document.getElementById('items_json');
    const form = document.getElementById('repair-create-form');

    // Status Banner Elements
    const banner = document.getElementById('machine-status-banner');
    const bannerName = document.getElementById('banner-machine-name');
    const bannerDone = document.getElementById('banner-service-done');
    const bannerDue = document.getElementById('banner-service-due');
    const bannerInterval = document.getElementById('banner-interval');
    const bannerOilQty = document.getElementById('banner-oil-qty');
    const bannerAlert = document.getElementById('banner-service-alert');

    let currentMachineData = null;
    let machinePartsMap = {};
    let currentSelectedParts = [];
    let currentWorkType = 'service_done';

    window.setWorkType = function(type) {
        currentWorkType = type;
        document.getElementById('work_type_service').checked = (type === 'service_done');
        document.getElementById('work_type_repair').checked = (type === 'repair');

        document.getElementById('card-service-done').classList.toggle('border-primary', type === 'service_done');
        document.getElementById('card-service-done').classList.toggle('active', type === 'service_done');
        document.getElementById('card-add-repair').classList.toggle('border-primary', type === 'repair');
        document.getElementById('card-add-repair').classList.toggle('active', type === 'repair');

        if (type === 'service_done') {
            repairTaskSelectorContainer.style.display = 'none';
            repairPartsToggleContainer.style.display = 'none';
            saveDefaultKitContainer.style.display = 'block';
            oilMustNotice.style.display = 'inline-block';
            workDoneTextarea.value = 'Routine Scheduled Servicing (Oil & Filter Change)';

            // Job Card and Parts are mandatory
            jobCardSection.style.display = 'block';
            jobCardInput.required = true;

            // Auto-load machine's default service kit with mandatory Engine Oil
            loadDefaultServiceKit();
        } else {
            repairTaskSelectorContainer.style.display = 'block';
            repairPartsToggleContainer.style.display = 'block';
            saveDefaultKitContainer.style.display = 'none';
            oilMustNotice.style.display = 'none';
            workDoneTextarea.value = '';

            // Update Job Card & Parts visibility based on repair_requires_parts checkbox
            updateRepairPartsVisibility();
        }
    };

    function updateRepairPartsVisibility() {
        if (currentWorkType === 'repair') {
            if (repairRequiresPartsCheck.checked) {
                jobCardSection.style.display = 'block';
                jobCardInput.required = true;
            } else {
                jobCardSection.style.display = 'none';
                jobCardInput.required = false;
                jobCardInput.value = '';
                currentSelectedParts = [];
                renderPartsTable();
            }
        }
    }

    repairRequiresPartsCheck.addEventListener('change', updateRepairPartsVisibility);

    function loadMachineData() {
        const machineId = machineSelect.value;
        if (!machineId) {
            banner.style.display = 'none';
            currentMachineData = null;
            machinePartsMap = {};
            pickerPartSelect.innerHTML = '<option value="">-- Choose Spare Part from Catalog --</option>';
            currentSelectedParts = [];
            renderPartsTable();
            return;
        }

        fetch('?action=getMachineDetails&machine_id=' + machineId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentMachineData = data.machine;
                machinePartsMap = {};
                pickerPartSelect.innerHTML = '<option value="">-- Choose Spare Part from Catalog --</option>';

                // 1. Populate Status Banner
                banner.style.display = 'block';
                bannerName.textContent = data.machine.name;
                bannerDone.textContent = data.machine.service_done;
                bannerDue.textContent = data.machine.service_due;
                bannerInterval.textContent = data.machine.service_interval_hours;
                bannerOilQty.textContent = data.machine.engine_oil_qty ? parseFloat(data.machine.engine_oil_qty).toFixed(2) : '0.00';

                // Prefill running hours if available from consumption log
                if (data.machine.current_meter && !runningHoursInput.value) {
                    runningHoursInput.value = data.machine.current_meter;
                }

                const currentHours = parseFloat(runningHoursInput.value || data.machine.current_meter || 0);
                const dueHours = parseFloat(data.machine.service_due);
                if (currentHours >= dueHours) {
                    bannerAlert.innerHTML = '<span class="badge bg-danger fs-6">⚠️ Servicing Overdue!</span>';
                } else if (dueHours - currentHours <= 20) {
                    bannerAlert.innerHTML = '<span class="badge bg-warning text-dark fs-6">⏳ Servicing Due Soon (' + (dueHours - currentHours).toFixed(1) + ' hrs left)</span>';
                } else {
                    bannerAlert.innerHTML = '<span class="badge bg-success fs-6">✓ Good Standing (' + (dueHours - currentHours).toFixed(1) + ' hrs to due)</span>';
                }

                // 2. Populate Spare Parts
                if (data.parts && data.parts.length > 0) {
                    data.parts.forEach(part => {
                        machinePartsMap[part.id] = part;
                        const opt = document.createElement('option');
                        opt.value = part.id;
                        opt.textContent = (part.part_no ? part.part_no + ' - ' : '') + part.nomenclature + ' (Stock: ' + part.current_stock + ' ' + (part.unit || 'Pcs') + ')';
                        pickerPartSelect.appendChild(opt);
                    });
                }

                // 3. Populate Custom Work Types for this machine
                const customOptgroup = document.getElementById('machine-custom-tasks-optgroup');
                customOptgroup.innerHTML = '';
                if (data.machine.custom_work_types && data.machine.custom_work_types.length > 0) {
                    data.machine.custom_work_types.forEach(cWork => {
                        const opt = document.createElement('option');
                        opt.value = cWork;
                        opt.textContent = cWork;
                        customOptgroup.appendChild(opt);
                    });
                }

                // 4. If current work type is service_done, auto-load service kit with Engine Oil
                if (currentWorkType === 'service_done') {
                    loadDefaultServiceKit();
                }
            }
        })
        .catch(err => console.error('Error fetching machine details:', err));
    }

    function loadDefaultServiceKit() {
        currentSelectedParts = [];

        // 1. Mandatory Engine Oil
        const allParts = Object.values(machinePartsMap);
        const engineOilPart = allParts.find(p => p.nomenclature.toLowerCase() === 'engine oil') || allParts.find(p => p.nomenclature.toLowerCase().includes('engine oil'));
        const defaultOilQty = (currentMachineData && parseFloat(currentMachineData.engine_oil_qty) > 0) 
            ? parseFloat(currentMachineData.engine_oil_qty) 
            : 2.0;

        if (engineOilPart) {
            currentSelectedParts.push({
                spare_part_id: parseInt(engineOilPart.id, 10),
                name: engineOilPart.nomenclature,
                part_no: engineOilPart.part_no,
                unit: engineOilPart.unit || 'Ltr',
                stock: parseFloat(engineOilPart.current_stock),
                quantity: defaultOilQty,
                is_engine_oil: true
            });
        }

        // 2. Add other default service items (filters, etc.)
        if (currentMachineData && currentMachineData.default_service_items && currentMachineData.default_service_items.length > 0) {
            currentMachineData.default_service_items.forEach(item => {
                const part = machinePartsMap[item.spare_part_id];
                if (part && (!engineOilPart || part.id !== engineOilPart.id)) {
                    currentSelectedParts.push({
                        spare_part_id: parseInt(item.spare_part_id, 10),
                        name: part.nomenclature,
                        part_no: part.part_no,
                        unit: part.unit || 'Pcs',
                        stock: parseFloat(part.current_stock),
                        quantity: parseFloat(item.quantity),
                        is_engine_oil: false
                    });
                }
            });
        }
        renderPartsTable();
    }

    function renderPartsTable() {
        partsTbody.innerHTML = '';
        if (currentSelectedParts.length === 0) {
            partsTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No spare parts added to this job card yet.</td></tr>';
        } else {
            currentSelectedParts.forEach((part, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        ${part.is_engine_oil ? '<span class="badge bg-warning text-dark me-1">🛢️ Mandatory</span>' : ''}
                        <strong>${(part.part_no ? part.part_no + ' - ' : '') + part.name}</strong>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">${part.stock} ${part.unit}</span>
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" class="form-control text-center part-qty-input fw-bold" data-index="${index}" value="${part.quantity}">
                            <span class="input-group-text">${part.unit}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        ${part.is_engine_oil && currentWorkType === 'service_done' 
                            ? '<span class="text-muted small">Required</span>' 
                            : `<button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-remove-part" data-index="${index}">&times;</button>`
                        }
                    </td>
                `;
                partsTbody.appendChild(tr);
            });
        }
        itemsJsonInput.value = JSON.stringify(currentSelectedParts);
    }

    // Add Part button
    btnAddPart.addEventListener('click', function() {
        const partId = parseInt(pickerPartSelect.value, 10);
        const qty = parseFloat(pickerQtyInput.value);

        if (!partId) {
            alert('Please select a spare part from the dropdown.');
            return;
        }
        if (isNaN(qty) || qty <= 0) {
            alert('Please enter a valid quantity greater than 0.');
            return;
        }

        const part = machinePartsMap[partId];
        if (!part) {
            alert('Selected part not found.');
            return;
        }

        const isOil = part.nomenclature.toLowerCase().includes('engine oil');
        const existingIdx = currentSelectedParts.findIndex(p => p.spare_part_id === partId);
        if (existingIdx >= 0) {
            currentSelectedParts[existingIdx].quantity = qty;
        } else {
            currentSelectedParts.push({
                spare_part_id: partId,
                name: part.nomenclature,
                part_no: part.part_no,
                unit: part.unit || 'Pcs',
                stock: parseFloat(part.current_stock),
                quantity: qty,
                is_engine_oil: isOil
            });
        }

        pickerPartSelect.value = '';
        pickerQtyInput.value = '';
        renderPartsTable();
    });

    // Remove or edit quantity in table
    partsTbody.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-part')) {
            const idx = parseInt(e.target.getAttribute('data-index'), 10);
            currentSelectedParts.splice(idx, 1);
            renderPartsTable();
        }
    });

    partsTbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('part-qty-input')) {
            const idx = parseInt(e.target.getAttribute('data-index'), 10);
            const val = parseFloat(e.target.value);
            if (!isNaN(val) && val > 0) {
                currentSelectedParts[idx].quantity = val;
                renderPartsTable();
            }
        }
    });

    // Quick Task Selector handling
    quickWorkSelect.addEventListener('change', function() {
        const val = this.value;
        if (val === '__custom__') {
            saveTaskCheckboxContainer.style.display = 'block';
            workDoneTextarea.value = '';
            workDoneTextarea.placeholder = 'Type custom work description (e.g. Hydraulic Line Welding)...';
            workDoneTextarea.focus();
        } else if (val) {
            saveTaskCheckboxContainer.style.display = 'none';
            workDoneTextarea.value = val;
        }
    });

    workDoneTextarea.addEventListener('input', function() {
        customWorkTitleInput.value = this.value.trim();
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        itemsJsonInput.value = JSON.stringify(currentSelectedParts);

        if (currentWorkType === 'service_done') {
            if (!jobCardInput.value.trim()) {
                alert('Job Card Number is mandatory for "Service Done".');
                jobCardInput.focus();
                e.preventDefault();
                return;
            }

            // Ensure Engine Oil is present and has qty > 0
            const hasOil = currentSelectedParts.some(p => p.is_engine_oil && p.quantity > 0);
            if (!hasOil) {
                alert('Engine Oil is a mandatory requirement for "Service Done". Please specify the Engine Oil quantity.');
                e.preventDefault();
                return;
            }
        }

        if (currentWorkType === 'repair' && repairRequiresPartsCheck.checked) {
            if (!jobCardInput.value.trim()) {
                alert('Job Card Number is mandatory when spare parts are used.');
                jobCardInput.focus();
                e.preventDefault();
                return;
            }
            if (currentSelectedParts.length === 0) {
                alert('You indicated this repair requires spare parts. Please add at least one spare part to the list or uncheck the spare parts option.');
                e.preventDefault();
                return;
            }
        }
    });

    machineSelect.addEventListener('change', loadMachineData);
    if (machineSelect.value) {
        loadMachineData();
    }
});
</script>
</body>
</html>
