<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Machine Log</title>
</head>
<body>
<div class="container-fluid">
    <h1>Add Machine Log</h1>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <?php if ($userRole === 'superuser'): ?>
                    <div class="col-md-3">
                        <label for="office_id" class="form-label">Office</label>
                        <select id="office_id" class="form-select" required>
                            <option value="">-- Choose Office --</option>
                            <?php if (!empty($offices)): ?>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label for="log_date" class="form-label">Date</label>
                    <input type="date" id="log_date" class="form-control" value="<?php echo htmlspecialchars($defaultDate); ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="machine_search" class="form-label">Machine</label>
                    <div class="position-relative">
                        <input type="text" id="machine_search" class="form-control" placeholder="Type machine or shortcut..." autocomplete="off" required>
                        <div id="machine_dropdown_list" class="dropdown-menu w-100" style="display:none; max-height: 250px; overflow-y: auto; position: absolute; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.15); margin-top: 2px;"></div>
                    </div>
                    <select id="machine_id" class="form-select" required style="display:none;">
                        <option value="">-- Select Office First --</option>
                    </select>
                </div>

                <div class="col-md-3" id="operator_group" style="display:none;">
                    <label for="operator" class="form-label">Operator</label>
                    <input type="text" id="operator" class="form-control">
                </div>

                <div class="col-md-3" id="fuel_group" style="display:none;">
                    <label for="fuel_consumed_qty" class="form-label">Fuel Consumed</label>
                    <input type="number" id="fuel_consumed_qty" class="form-control" step="0.01">
                </div>

                <div class="col-md-3" id="runhours_group" style="display:none;">
                    <label for="running_hours" class="form-label">Running Hours</label>
                    <input type="number" id="running_hours" class="form-control" step="0.01">
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="button" id="stage-log-btn" class="btn btn-primary">Add and Continue</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Staged Entries</h2>
            <div id="staged-totals-container" class="d-flex flex-wrap gap-2 justify-content-end">
                <!-- Daily totals populated dynamically -->
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Sl. No.</th>
                            <th>Date</th>
                            <th>Machine</th>
                            <th>Operator</th>
                            <th>Fuel Type</th>
                            <th>Fuel Qty</th>
                            <th>Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staged-logs-tbody">
                        <!-- Rows will be populated by JS -->
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                <button type="button" id="insert-all-btn" class="btn btn-success">Insert All Staged Records</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing Staged Entry -->
<div class="modal fade" id="edit-staged-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Staged Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-staged-index">
        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" id="edit-staged-date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Machine</label>
            <input type="text" id="edit-staged-machine-name" class="form-control" readonly disabled>
        </div>
        <div class="mb-3">
            <label for="edit-staged-operator" class="form-label">Operator</label>
            <input type="text" id="edit-staged-operator" class="form-control">
        </div>
        <div class="mb-3" id="edit-staged-fuel-group">
            <label id="edit-staged-fuel-label" for="edit-staged-fuel" class="form-label">Fuel Consumed</label>
            <input type="number" id="edit-staged-fuel" class="form-control" step="0.01">
        </div>
        <div class="mb-3" id="edit-staged-runhours-group">
            <label for="edit-staged-runhours" class="form-label">Running Hours</label>
            <input type="number" id="edit-staged-runhours" class="form-control" step="0.01">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-staged-edit-btn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded. Initializing form...');

    const allMachines = <?php echo json_encode($machines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
    const userRole = "<?php echo $userRole; ?>";
    const currentUserOfficeId = "<?php echo $currentUser['officeid']; ?>";
    const existingLogsMap = <?php echo json_encode($existingLogsMap ?? new stdClass()); ?>;

    let stagedLogs = [];

    // UI Elements - Main Form
    const officeSelect = document.getElementById('office_id');
    const dateInput = document.getElementById('log_date');
    const machineSelect = document.getElementById('machine_id');
    const machineSearchInput = document.getElementById('machine_search');
    const machineDropdownList = document.getElementById('machine_dropdown_list');
    const operatorInput = document.getElementById('operator');
    const fuelInput = document.getElementById('fuel_consumed_qty');
    const runhoursInput = document.getElementById('running_hours');

    // UI Elements - Groups
    const operatorGroup = document.getElementById('operator_group');
    const fuelGroup = document.getElementById('fuel_group');
    const runhoursGroup = document.getElementById('runhours_group');

    // UI Elements - Table & Buttons
    const stageLogBtn = document.getElementById('stage-log-btn');
    const insertAllBtn = document.getElementById('insert-all-btn');
    const stagedLogsTbody = document.getElementById('staged-logs-tbody');
    const stagedTotalsContainer = document.getElementById('staged-totals-container');

    // UI Elements - Edit Modal
    const editModal = new bootstrap.Modal(document.getElementById('edit-staged-modal'));
    const editIndexInput = document.getElementById('edit-staged-index');
    const editDateInput = document.getElementById('edit-staged-date');
    const editMachineNameInput = document.getElementById('edit-staged-machine-name');
    const editOperatorInput = document.getElementById('edit-staged-operator');
    const editFuelInput = document.getElementById('edit-staged-fuel');
    const editFuelLabel = document.getElementById('edit-staged-fuel-label');
    const editRunhoursInput = document.getElementById('edit-staged-runhours');
    const editFuelGroup = document.getElementById('edit-staged-fuel-group');
    const editRunhoursGroup = document.getElementById('edit-staged-runhours-group');
    const saveStagedEditBtn = document.getElementById('save-staged-edit-btn');

    // Helper to generate shortcut (e.g. Brush Cutter 3 -> bc3)
    // Helper to generate shortcut (uses db shortname, falls back to dynamic shortcut)
    function getMachineShortcut(machine) {
        if (!machine) return '';
        if (machine.shortname && machine.shortname.trim()) {
            return machine.shortname.toLowerCase().trim();
        }
        const name = machine.name;
        if (!name) return '';
        const words = name.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim().split(/\s+/);
        let shortcut = '';
        words.forEach(w => {
            if (/^\d+$/.test(w)) {
                shortcut += w;
            } else if (w.length > 0) {
                shortcut += w.charAt(0);
            }
        });
        return shortcut;
    }

    // Function to render dropdown items based on search query
    function updateMachineDropdown() {
        const query = machineSearchInput.value.toLowerCase().trim();
        let selectedOfficeId = null;

        if (userRole === 'superuser') {
            selectedOfficeId = officeSelect ? officeSelect.value : null;
        } else {
            selectedOfficeId = currentUserOfficeId;
        }

        if (!selectedOfficeId) {
            machineDropdownList.style.display = 'none';
            return;
        }

        machineDropdownList.innerHTML = '';
        
        // Filter machines by office AND search query
        const filtered = allMachines.filter(machine => {
            if (String(machine.officeid) !== String(selectedOfficeId)) return false;
            
            const name = machine.name.toLowerCase();
            const shortcut = getMachineShortcut(machine);
            
            return name.includes(query) || shortcut.includes(query);
        });

        if (filtered.length === 0) {
            const noMatch = document.createElement('div');
            noMatch.className = 'dropdown-item text-muted disabled';
            noMatch.textContent = 'No matching machines';
            machineDropdownList.appendChild(noMatch);
        } else {
            filtered.forEach(machine => {
                const shortcut = getMachineShortcut(machine);
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'dropdown-item d-flex justify-content-between align-items-center';
                item.innerHTML = `<span>${machine.name}</span> <span class="badge bg-secondary">${shortcut}</span>`;
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    selectMachine(machine);
                });
                machineDropdownList.appendChild(item);
            });
        }
        machineDropdownList.style.display = 'block';
    }

    // Select machine helper
    function selectMachine(machine) {
        machineSearchInput.value = machine.name;
        machineSelect.value = machine.id;
        
        // Trigger change event to fire toggleDynamicFields
        const event = new Event('change');
        machineSelect.dispatchEvent(event);
        
        machineDropdownList.style.display = 'none';
        
        // Advance to Operator input field
        setTimeout(() => {
            operatorInput.focus();
        }, 50);
    }

    // Auto-select on Blur or Tab/Enter if there is a unique shortcut match or exact name match
    function handleBlurOrConfirm() {
        const query = machineSearchInput.value.toLowerCase().trim();
        if (!query) {
            machineSelect.value = '';
            const event = new Event('change');
            machineSelect.dispatchEvent(event);
            return;
        }

        let selectedOfficeId = null;
        if (userRole === 'superuser') {
            selectedOfficeId = officeSelect ? officeSelect.value : null;
        } else {
            selectedOfficeId = currentUserOfficeId;
        }

        if (!selectedOfficeId) return;

        // Find matches (exact)
        const matches = allMachines.filter(machine => {
            if (String(machine.officeid) !== String(selectedOfficeId)) return false;
            const name = machine.name.toLowerCase();
            const shortcut = getMachineShortcut(machine);
            return name === query || shortcut === query;
        });

        if (matches.length > 0) {
            selectMachine(matches[0]);
        } else {
            // Partial matches
            const partialMatches = allMachines.filter(machine => {
                if (String(machine.officeid) !== String(selectedOfficeId)) return false;
                const name = machine.name.toLowerCase();
                const shortcut = getMachineShortcut(machine);
                return name.includes(query) || shortcut.includes(query);
            });
            if (partialMatches.length === 1) {
                selectMachine(partialMatches[0]);
            }
        }
    }

    // Bind event listeners for searchable select
    machineSearchInput.addEventListener('focus', () => {
        updateMachineDropdown();
    });

    machineSearchInput.addEventListener('input', () => {
        updateMachineDropdown();
    });

    machineSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleBlurOrConfirm();
        } else if (e.key === 'Tab') {
            handleBlurOrConfirm();
        }
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!machineSearchInput.contains(e.target) && !machineDropdownList.contains(e.target)) {
            machineDropdownList.style.display = 'none';
        }
    });

    // Format operator name as Title Case on blur
    operatorInput.addEventListener('blur', () => {
        let val = operatorInput.value.trim();
        if (val) {
            val = val.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
            operatorInput.value = val;
        }
    });

    if (editOperatorInput) {
        editOperatorInput.addEventListener('blur', () => {
            let val = editOperatorInput.value.trim();
            if (val) {
                val = val.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                editOperatorInput.value = val;
            }
        });
    }

    const filterMachines = () => {
        let selectedOfficeId = null;

        if (userRole === 'superuser') {
            selectedOfficeId = officeSelect ? officeSelect.value : null;
        } else {
            selectedOfficeId = currentUserOfficeId;
        }

        machineSelect.innerHTML = '';
        let placeholderText = selectedOfficeId ? '-- Select Machine --' : '-- Select Office First --';
        machineSelect.add(new Option(placeholderText, ''));

        // Reset and update searchable select field state
        if (machineSearchInput) {
            machineSearchInput.value = '';
            if (selectedOfficeId) {
                machineSearchInput.disabled = false;
                machineSearchInput.placeholder = "Type machine or shortcut...";
            } else {
                machineSearchInput.disabled = true;
                machineSearchInput.placeholder = "-- Select Office First --";
            }
        }

        if (selectedOfficeId) {
            allMachines.forEach(machine => {
                if (String(machine.officeid) === String(selectedOfficeId)) {
                    let option = new Option(machine.name, machine.id);
                    option.dataset.runduration = machine.runduration ? '1' : '0';
                    option.dataset.fuel = machine.fuel_type_name || '';
                    machineSelect.add(option);
                }
            });
        }

        toggleDynamicFields();
    };

    // Show/hide fields depending on which machine is selected
    const toggleDynamicFields = () => {
        const selectedMachineId = machineSelect.value;
        const isMachineSelected = selectedMachineId !== '';

        operatorGroup.style.display = isMachineSelected ? 'block' : 'none';

        if (!isMachineSelected) {
            fuelGroup.style.display = 'none';
            runhoursGroup.style.display = 'none';
            return;
        }

        // Find the selected machine details
        const machine = allMachines.find(m => String(m.id) === String(selectedMachineId));

        fuelGroup.style.display = (machine && machine.fuel_type_name) ? 'block' : 'none';
        runhoursGroup.style.display = (machine && machine.runduration) ? 'block' : 'none';
    };

    // Attach Event Listeners
    machineSelect.addEventListener('change', toggleDynamicFields);

    if (userRole === 'superuser' && officeSelect) {
        officeSelect.addEventListener('change', filterMachines);
    }
    filterMachines();

    const calculateTotals = () => {
        if (!stagedTotalsContainer) return;
        stagedTotalsContainer.innerHTML = '';

        // Group by date
        const dailyData = {};
        stagedLogs.forEach(log => {
            const date = log.log_date;
            if (!dailyData[date]) {
                dailyData[date] = { petrol: 0, diesel: 0 };
            }

            if (log.fuel_consumed_qty && log._fuel_type) {
                const fuelType = log._fuel_type.toLowerCase();
                const qty = parseFloat(log.fuel_consumed_qty);

                if (fuelType.includes('petrol')) {
                    dailyData[date].petrol += qty;
                } else if (fuelType.includes('diesel')) {
                    dailyData[date].diesel += qty;
                }
            }
        });

        // Sort dates chronologically
        const sortedDates = Object.keys(dailyData).sort();

        // Render badges
        sortedDates.forEach(date => {
            const dayPetrol = dailyData[date].petrol;
            const dayDiesel = dailyData[date].diesel;
            const parts = [];

            if (dayPetrol > 0) {
                parts.push(`Petrol: <strong>${dayPetrol.toFixed(2)}</strong>`);
            }
            if (dayDiesel > 0) {
                parts.push(`Diesel: <strong>${dayDiesel.toFixed(2)}</strong>`);
            }

            if (parts.length > 0) {
                const badge = document.createElement('div');
                badge.className = 'badge bg-light text-dark border p-2 font-monospace';
                badge.style.fontSize = '12px';
                badge.innerHTML = `📅 <strong>${date}:</strong> ${parts.join(' | ')}`;
                stagedTotalsContainer.appendChild(badge);
            }
        });
    };

    const renderStagedLogs = () => {
        stagedLogsTbody.innerHTML = '';
        if (stagedLogs.length === 0) {
            calculateTotals();
            return;
        }

        // Group logs by date
        const grouped = {};
        stagedLogs.forEach((log, index) => {
            const logWithIndex = { ...log, originalIndex: index };
            if (!grouped[log.log_date]) {
                grouped[log.log_date] = [];
            }
            grouped[log.log_date].push(logWithIndex);
        });

        // Sort dates chronologically
        const sortedDates = Object.keys(grouped).sort();

        // Render each date's logs and then a summary boundary row
        let displaySlNo = 1;
        sortedDates.forEach((date, dayIdx) => {
            const logsForDay = grouped[date];
            let dayPetrol = 0;
            let dayDiesel = 0;
            const count = logsForDay.length;

            logsForDay.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${displaySlNo++}</td>
                    <td>${log.log_date}</td>
                    <td>${log.machine_name}</td>
                    <td>${log.operator}</td>
                    <td>${log._fuel_type || '-'}</td>
                    <td>${log.fuel_consumed_qty || '-'}</td>
                    <td>${log.running_hours || '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning edit-staged-btn" data-index="${log.originalIndex}">Edit</button>
                        <button type="button" class="btn btn-sm btn-danger remove-btn" data-index="${log.originalIndex}">Del</button>
                    </td>
                `;
                stagedLogsTbody.appendChild(row);

                // Sum up fuel for the day
                if (log.fuel_consumed_qty && log._fuel_type) {
                    const fuelType = log._fuel_type.toLowerCase();
                    const qty = parseFloat(log.fuel_consumed_qty);
                    if (fuelType.includes('petrol')) {
                        dayPetrol += qty;
                    } else if (fuelType.includes('diesel')) {
                        dayDiesel += qty;
                    }
                }
            });

            // Render boundary / summary row
            const summaryRow = document.createElement('tr');
            summaryRow.className = 'table-light border-top border-bottom';
            summaryRow.style.fontWeight = 'bold';
            summaryRow.style.backgroundColor = '#f8fafc';
            
            const petrolStr = dayPetrol > 0 ? `Petrol: <strong>${dayPetrol.toFixed(2)}</strong>` : 'Petrol: 0.00';
            const dieselStr = dayDiesel > 0 ? `Diesel: <strong>${dayDiesel.toFixed(2)}</strong>` : 'Diesel: 0.00';

            summaryRow.innerHTML = `
                <td colspan="8" class="py-2 px-3 text-end text-muted font-monospace" style="font-size: 13px;">
                    📊 <strong>Daily Summary (${date}):</strong> Entries: <strong>${count}</strong> | ${petrolStr} | ${dieselStr}
                </td>
            `;
            stagedLogsTbody.appendChild(summaryRow);

            // Add a clean blank row as spacing/boundary between days (except for the last day)
            if (dayIdx < sortedDates.length - 1) {
                const blankRow = document.createElement('tr');
                blankRow.style.height = '18px';
                blankRow.innerHTML = `<td colspan="8" style="background-color: transparent; border: none; padding: 0;"></td>`;
                stagedLogsTbody.appendChild(blankRow);
            }
        });

        calculateTotals();
    };

    const resetMachineFields = () => {
        machineSelect.value = '';
        if (machineSearchInput) machineSearchInput.value = '';
        operatorInput.value = '';
        fuelInput.value = '';
        runhoursInput.value = '';
        toggleDynamicFields();
        if (machineSearchInput) machineSearchInput.focus();
    };

    // Add entry to Javascript Array
    stageLogBtn.addEventListener('click', () => {
        const selectedMachineOption = machineSelect.options[machineSelect.selectedIndex];

        if (!dateInput.value || !machineSelect.value || !operatorInput.value) {
            alert('Date, Machine, and Operator are required fields.');
            return;
        }

        // Validate Fuel Step (increments of 0.5)
        if (fuelInput.value !== '') {
            const fuel = parseFloat(fuelInput.value);
            if (isNaN(fuel) || fuel < 0) {
                alert('Fuel quantity must be a positive number.');
                return;
            }
            if ((fuel * 2) % 1 !== 0) {
                alert('Fuel quantity must be in steps of 0.5 (e.g., 0.5, 1.0, 1.5, etc.).');
                return;
            }
        }

        // Validate Hours Precision (at most 1 decimal place)
        if (runhoursInput.value !== '') {
            const hours = parseFloat(runhoursInput.value);
            if (isNaN(hours) || hours < 0) {
                alert('Running hours must be a positive number.');
                return;
            }
            if (Math.round(hours * 10) !== hours * 10) {
                alert('Running hours can have at most 1 decimal place (e.g., 100.1).');
                return;
            }
        }

        const machineId = machineSelect.value;
        const date = dateInput.value;

        // --- 1. Check for duplicates in the current staged array ---
        const isDuplicateStaged = stagedLogs.some(log => String(log.machine_id) === String(machineId) && log.log_date === date);
        if (isDuplicateStaged) {
            alert('This machine has already been staged for this date. You cannot add it twice.');
            return;
        }

        // --- 2. Check for duplicates in the pre-fetched database records ---
        const dbKey = machineId + '_' + date;
        if (existingLogsMap[dbKey]) {
            alert('This machine already has a log entry for ' + date + ' in the database.');
            return;
        }

        const machine = allMachines.find(m => String(m.id) === String(machineSelect.value));

        const log = {
            log_date: dateInput.value,
            machine_id: machineSelect.value,
            machine_name: selectedMachineOption ? selectedMachineOption.text : '',
            operator: operatorInput.value,
            fuel_consumed_qty: fuelInput.value || null,
            running_hours: runhoursInput.value || null,
            _has_fuel: machine && machine.fuel_type_name ? true : false,
            _fuel_type: machine ? machine.fuel_type_name : '', // Store fuel type for calculation
            _has_runhours: machine && machine.runduration ? true : false
        };

        // Instantly push and render
        stagedLogs.push(log);
        renderStagedLogs();
        resetMachineFields();
    });

    // Delegation for Edit and Delete buttons in the table
    stagedLogsTbody.addEventListener('click', (e) => {
        const target = e.target;
        if (target.classList.contains('remove-btn')) {
            const index = target.getAttribute('data-index');
            stagedLogs.splice(index, 1);
            renderStagedLogs();
        } else if (target.classList.contains('edit-staged-btn')) {
            const index = target.getAttribute('data-index');
            const log = stagedLogs[index];

            // Populate Modal
            editIndexInput.value = index;
            editDateInput.value = log.log_date; // Allow editing the date
            editMachineNameInput.value = log.machine_name;
            editOperatorInput.value = log.operator;
            editFuelInput.value = log.fuel_consumed_qty || '';
            editRunhoursInput.value = log.running_hours || '';

            // Customize Label and Show/hide fields in modal based on machine type
            if (log._has_fuel) {
                editFuelLabel.innerText = `${log._fuel_type} Consumed`;
                editFuelGroup.style.display = 'block';
            } else {
                editFuelGroup.style.display = 'none';
            }

            editRunhoursGroup.style.display = log._has_runhours ? 'block' : 'none';

            editModal.show();
        }
    });

    // Save changes from the edit modal
    saveStagedEditBtn.addEventListener('click', () => {
        const index = editIndexInput.value;
        if (index === '') return;

        if (!editOperatorInput.value) {
            alert("Operator is required.");
            return;
        }

        if (!editDateInput.value) {
            alert("Date is required.");
            return;
        }

        // Validate Fuel Step (increments of 0.5)
        if (editFuelInput.value !== '') {
            const fuel = parseFloat(editFuelInput.value);
            if (isNaN(fuel) || fuel < 0) {
                alert('Fuel quantity must be a positive number.');
                return;
            }
            if ((fuel * 2) % 1 !== 0) {
                alert('Fuel quantity must be in steps of 0.5 (e.g., 0.5, 1.0, 1.5, etc.).');
                return;
            }
        }

        // Validate Hours Precision (at most 1 decimal place)
        if (editRunhoursInput.value !== '') {
            const hours = parseFloat(editRunhoursInput.value);
            if (isNaN(hours) || hours < 0) {
                alert('Running hours must be a positive number.');
                return;
            }
            if (Math.round(hours * 10) !== hours * 10) {
                alert('Running hours can have at most 1 decimal place (e.g., 100.1).');
                return;
            }
        }

        const newDate = editDateInput.value;
        const currentMachineId = stagedLogs[index].machine_id;

        // Check staged list conflict if date changed
        if (newDate !== stagedLogs[index].log_date) {
            const isDuplicateStaged = stagedLogs.some((log, i) => String(i) !== String(index) && String(log.machine_id) === String(currentMachineId) && log.log_date === newDate);
            if (isDuplicateStaged) {
                alert('This machine has already been staged for this new date. You cannot add it twice.');
                return;
            }

            // Check pre-fetched DB records conflict
            const dbKey = currentMachineId + '_' + newDate;
            if (existingLogsMap[dbKey]) {
                alert('This machine already has a log entry for ' + newDate + ' in the database.');
                return;
            }
        }

        // Update the array
        stagedLogs[index].log_date = newDate;
        stagedLogs[index].operator = editOperatorInput.value;
        stagedLogs[index].fuel_consumed_qty = editFuelInput.value || null;
        stagedLogs[index].running_hours = editRunhoursInput.value || null;

        // Re-render and hide modal
        renderStagedLogs();
        editModal.hide();
    });

    insertAllBtn.addEventListener('click', async () => {
        if (stagedLogs.length === 0) {
            alert('No logs to insert. Please add entries to the list first.');
            return;
        }

        let officeId = userRole === 'superuser' ? officeSelect.value : currentUserOfficeId;
        if (userRole === 'superuser' && !officeId) {
            alert('Please select an office.');
            return;
        }

        const originalText = insertAllBtn.innerText;
        insertAllBtn.innerText = 'Saving...';
        insertAllBtn.disabled = true;

        try {
            const response = await fetch('?action=createLogs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>", logs: stagedLogs, office_id: officeId })
            });

            const result = await response.json();

            if (!response.ok) {
                // If it's a database constraint error, it will be caught here
                throw new Error(result.error || 'An unknown error occurred on the server.');
            }

            alert(result.message);
            window.location.href = '?action=list';

        } catch (error) {
            alert('Error: ' + error.message);
            insertAllBtn.innerText = originalText;
            insertAllBtn.disabled = false;
        }
    });
});
</script>
</body>
</html>
