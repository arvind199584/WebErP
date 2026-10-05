<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Machine Servicing</title>
</head>
<body>
<div class="container-fluid py-3" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🛠️ Log Machine Servicing</h1>
            <p class="text-muted mb-0">Record routine servicing, oil changes, repairs, and technician notes for a machine.</p>
        </div>
        <a href="?action=list" class="btn btn-outline-secondary">← Back to List</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="?action=create">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="machine_id" class="form-label fw-bold">Select Machine <span class="text-danger">*</span></label>
                        <select name="machine_id" id="machine_id" class="form-select" required>
                            <option value="">-- Choose Machine --</option>
                            <?php foreach ($machines as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($selectedMachineId == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="service_date" class="form-label fw-bold">Service Date <span class="text-danger">*</span></label>
                        <input type="date" name="service_date" id="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="service_type" class="form-label fw-bold mb-0">Service Type</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btn-add-service-type" style="display:none;" data-bs-toggle="modal" data-bs-target="#newServiceTypeModal">
                                ➕ Add Custom Type
                            </button>
                        </div>
                        <select name="service_type" id="service_type" class="form-select">
                            <option value="Routine Maintenance">Routine Maintenance</option>
                            <option value="Initial Baseline Reading">Initial Baseline Reading (Legacy Setup)</option>
                            <option value="Oil & Filter Change">Oil & Filter Change</option>
                            <option value="Blade Sharpening">Blade Sharpening</option>
                            <option value="Engine Overhaul">Engine Overhaul</option>
                            <option value="Hydraulic System Service">Hydraulic System Service</option>
                            <option value="Tire & Belt Repair">Tire & Belt Repair</option>
                            <option value="Breakdown Repair">Breakdown Repair</option>
                            <option value="Cleaning & Bolt Tightening">Cleaning & Bolt Tightening</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="hours_field_container">
                        <label for="hours_at_service" class="form-label fw-bold">Machine Hours at Service <span class="text-danger" id="hours_required_asterisk">*</span></label>
                        <input type="number" step="0.1" name="hours_at_service" id="hours_at_service" class="form-control" placeholder="e.g. 1050.5">
                    </div>

                    <div class="col-md-6">
                        <label for="cost" class="form-label fw-bold">Servicing Cost (₹)</label>
                        <input type="number" step="0.01" name="cost" id="cost" class="form-control" placeholder="0.00">
                    </div>

                    <!-- Inventory Items Checkbox & Job Card Field -->
                    <div class="col-12 border-top pt-3 mt-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="uses_inventory" name="uses_inventory" value="1">
                            <label class="form-check-label fw-bold" for="uses_inventory">
                                📦 This servicing used items/spare parts from Store Inventory
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12" id="job_card_container" style="display: none;">
                        <div class="p-3 bg-light border rounded">
                            <label for="job_card_no" class="form-label fw-bold text-primary">Job Card / Indent Voucher No. <span class="text-danger">*</span></label>
                            <input type="text" name="job_card_no" id="job_card_no" class="form-control" placeholder="e.g. JC-2026-088 or INDENT-101">
                            <div class="form-text text-dark">
                                Store items depletion requires a valid Job Card / Indent Voucher. 
                                <a href="/modules/Workshop/Machine/TurfManagement/Indents/Controller/IndentController.php?action=showCreateForm" target="_blank" class="fw-bold text-decoration-none">
                                    📋 Create Store Indent Voucher (Issue Store Items) ↗
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="remarks" class="form-label fw-bold">Service Details / Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Describe work done, cleaning, bolt tightening, or parts replaced..."></textarea>
                    </div>

                </div>

                <div class="mt-4 text-end">
                    <a href="?action=list" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">Save Servicing Record</button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Modal for Adding Custom Service Type for Selected Machine -->
<div class="modal fade" id="newServiceTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">➕ Add Custom Service Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new_custom_type_input" class="form-label fw-bold">Service Type Name</label>
                    <input type="text" id="new_custom_type_input" class="form-control" placeholder="e.g. Reel Sharpening / Battery Health Check">
                </div>
                <div id="modal_error_msg" class="text-danger small mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-save-custom-type">Save & Select</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const machineSelect = document.getElementById('machine_id');
    const hoursContainer = document.getElementById('hours_field_container');
    const hoursInput = document.getElementById('hours_at_service');
    const hoursAsterisk = document.getElementById('hours_required_asterisk');
    const usesInventoryCheck = document.getElementById('uses_inventory');
    const jobCardContainer = document.getElementById('job_card_container');
    const jobCardInput = document.getElementById('job_card_no');
    const serviceTypeSelect = document.getElementById('service_type');
    const btnAddServiceType = document.getElementById('btn-add-service-type');
    const btnSaveCustomType = document.getElementById('btn-save-custom-type');
    const newCustomTypeInput = document.getElementById('new_custom_type_input');
    const modalErrorMsg = document.getElementById('modal_error_msg');

    const defaultServiceTypes = [
        "Routine Maintenance",
        "Initial Baseline Reading",
        "Oil & Filter Change",
        "Blade Sharpening",
        "Engine Overhaul",
        "Hydraulic System Service",
        "Tire & Belt Repair",
        "Breakdown Repair",
        "Cleaning & Bolt Tightening",
        "Other"
    ];

    // Store machine metadata from PHP
    const machinesMap = <?php 
        $map = [];
        foreach ($machines as $m) {
            $map[$m['id']] = [
                'id' => $m['id'],
                'name' => $m['name'],
                'runduration' => (bool)$m['runduration'],
                'custom_types' => json_decode($m['custom_service_types'] ?? '[]', true) ?: []
            ];
        }
        echo json_encode($map);
    ?>;

    function updateMachineFormState() {
        const machineId = machineSelect.value;
        const selectedMachine = machinesMap[machineId];

        // Reset & build service types dropdown
        const currentSelected = serviceTypeSelect.value;
        serviceTypeSelect.innerHTML = '';

        defaultServiceTypes.forEach(type => {
            const opt = document.createElement('option');
            opt.value = type;
            opt.textContent = type;
            serviceTypeSelect.appendChild(opt);
        });

        if (selectedMachine) {
            btnAddServiceType.style.display = 'inline';

            // Populate custom service types
            if (selectedMachine.custom_types && selectedMachine.custom_types.length > 0) {
                const group = document.createElement('optgroup');
                group.label = 'Custom Types for ' + selectedMachine.name;
                selectedMachine.custom_types.forEach(cType => {
                    const opt = document.createElement('option');
                    opt.value = cType;
                    opt.textContent = cType;
                    group.appendChild(opt);
                });
                serviceTypeSelect.appendChild(group);
            }

            if (selectedMachine.runduration) {
                hoursContainer.style.display = 'block';
                hoursInput.required = true;
                hoursAsterisk.style.display = 'inline';
            } else {
                hoursContainer.style.display = 'none';
                hoursInput.required = false;
                hoursInput.value = '';
                hoursAsterisk.style.display = 'none';
            }
        } else {
            btnAddServiceType.style.display = 'none';
            hoursContainer.style.display = 'none';
            hoursInput.required = false;
            hoursInput.value = '';
            hoursAsterisk.style.display = 'none';
        }

        if (currentSelected) {
            serviceTypeSelect.value = currentSelected;
        }
    }

    function updateJobCardState() {
        if (usesInventoryCheck.checked) {
            jobCardContainer.style.display = 'block';
            jobCardInput.required = true;
        } else {
            jobCardContainer.style.display = 'none';
            jobCardInput.required = false;
            jobCardInput.value = '';
        }
    }

    btnSaveCustomType.addEventListener('click', function() {
        const machineId = machineSelect.value;
        const newType = newCustomTypeInput.value.trim();

        if (!machineId) {
            modalErrorMsg.textContent = 'Please select a machine first.';
            modalErrorMsg.style.display = 'block';
            return;
        }
        if (!newType) {
            modalErrorMsg.textContent = 'Please enter a custom service type name.';
            modalErrorMsg.style.display = 'block';
            return;
        }

        const formData = new FormData();
        formData.append('machine_id', machineId);
        formData.append('new_service_type', newType);

        fetch('?action=addCustomServiceType', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update local map
                machinesMap[machineId].custom_types = data.types;
                updateMachineFormState();
                serviceTypeSelect.value = newType;
                
                // Close modal
                const modalEl = document.getElementById('newServiceTypeModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance.hide();
                newCustomTypeInput.value = '';
                modalErrorMsg.style.display = 'none';
            } else {
                modalErrorMsg.textContent = data.message || 'Error saving custom type.';
                modalErrorMsg.style.display = 'block';
            }
        })
        .catch(err => {
            modalErrorMsg.textContent = 'Network error occurred.';
            modalErrorMsg.style.display = 'block';
        });
    });

    machineSelect.addEventListener('change', updateMachineFormState);
    usesInventoryCheck.addEventListener('change', updateJobCardState);

    // Initial state on page load
    updateMachineFormState();
    updateJobCardState();
});
</script>
</body>
</html>
