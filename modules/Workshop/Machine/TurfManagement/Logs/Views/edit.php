<div class="container-fluid py-4" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
    
    <!-- Custom Style Sheet for Rich Premium Aesthetics -->
    <style>
        .premium-card {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .premium-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
        .gradient-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: #ffffff;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        .gradient-btn-edit {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            color: white;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
        }
        .gradient-btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(245, 158, 11, 0.35);
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: white;
        }
        .gradient-btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            color: white;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
        }
        .gradient-btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.35);
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
        }
        .date-badge {
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            padding: 8px 12px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
        }
        .date-badge:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-1px);
        }
        .stat-card {
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            padding: 16px;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            background-color: #f8fafc !important;
            color: #475569;
        }
        .table td {
            vertical-align: middle;
        }
        .hover-row {
            transition: background-color 0.2s ease;
        }
        .hover-row:hover {
            background-color: #f1f5f9 !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .toast-info {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 12px;
        }
    </style>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h2 mb-1 fw-bold text-dark">✏️ Edit Daily Logs</h1>
            <p class="text-muted mb-0">Select a specific date to correct or delete entries matching official office records.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=list" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 10px;">
                ◀ Back to Dashboard
            </a>
            <a href="?action=showCreateForm" class="btn btn-primary px-4 py-2" style="border-radius: 10px; background: #3b82f6; border: none;">
                ➕ Staging Form
            </a>
        </div>
    </div>

    <!-- Date Picker Selector Card -->
    <div class="card premium-card mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-dark">Which date records would you like to edit?</h5>
            <form method="GET" action="" id="date-select-form" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="editByDate">
                <div class="col-md-5 col-lg-4">
                    <label for="target-date" class="form-label text-muted fw-semibold small">SELECT LOG DATE</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">📅</span>
                        <input type="date" name="date" id="target-date" class="form-control border-start-0 ps-0" 
                               value="<?php echo htmlspecialchars($selectedDate ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px; background: #3b82f6; border: none;">
                        🔍 Fetch Records
                    </button>
                </div>
            </form>

            <?php if (!empty($recentDates)): ?>
                <div class="mt-4">
                    <div class="text-muted fw-semibold small mb-2">QUICK ACCESS RECENT DATES WITH LOGS:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($recentDates as $recentDate): ?>
                            <span class="date-badge fw-bold" onclick="selectQuickDate('<?php echo htmlspecialchars($recentDate); ?>')">
                                📅 <?php echo htmlspecialchars(date('d M Y', strtotime($recentDate))); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Date-Specific Results Section -->
    <?php if ($selectedDate !== null): ?>
        <div class="card premium-card overflow-hidden">
            <div class="card-header gradient-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">📅</span>
                    <h5 class="mb-0 fw-bold">
                        Logs for <?php echo htmlspecialchars(date('l, d F Y', strtotime($selectedDate))); ?>
                    </h5>
                </div>
                <span class="badge bg-light text-dark fw-bold px-3 py-2 rounded-pill font-monospace" style="font-size: 0.9rem;">
                    <?php echo count($logs); ?> Record(s) Found
                </span>
            </div>
            
            <div class="card-body p-4">
                
                <?php if (!empty($logs)): ?>
                    <!-- Daily Summary Stats row -->
                    <?php 
                        $totalPetrol = 0;
                        $totalDiesel = 0;
                        foreach ($logs as $log) {
                            $fuelQty = (float)($log['fuel_consumed_qty'] ?? 0);
                            $fuelDesc = strtolower($log['fuel_desc'] ?? '');
                            if ($fuelQty > 0) {
                                if (strpos($fuelDesc, 'petrol') !== false) {
                                    $totalPetrol += $fuelQty;
                                } elseif (strpos($fuelDesc, 'diesel') !== false) {
                                    $totalDiesel += $fuelQty;
                                }
                            }
                        }
                    ?>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="stat-card text-center" style="border-left: 4px solid #f59e0b;">
                                <div class="text-muted small fw-semibold uppercase mb-1">⛽ ESTIMATED PETROL</div>
                                <div class="fs-3 fw-bold text-warning font-monospace"><?php echo number_format($totalPetrol, 2); ?> L</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="stat-card text-center" style="border-left: 4px solid #475569;">
                                <div class="text-muted small fw-semibold uppercase mb-1">🛢️ ESTIMATED DIESEL / FUEL</div>
                                <div class="fs-3 fw-bold text-secondary font-monospace"><?php echo number_format($totalDiesel, 2); ?> L</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                        <th>Office</th>
                                    <?php endif; ?>
                                    <th>Machine Name</th>
                                    <th>Operator</th>
                                    <th class="text-end">Fuel Consumed</th>
                                    <th class="text-end">Running Hours</th>
                                    <th class="text-center" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover-row">
                                        <td class="font-monospace fw-bold text-muted" style="font-size: 0.85rem;">
                                            #<?php echo htmlspecialchars((string)($log['id'] ?? '')); ?>
                                        </td>
                                        <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                            <td class="fw-semibold text-secondary" style="font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($log['office_name'] ?? ''); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($log['machine_name'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td class="text-secondary" style="font-size: 0.95rem;">
                                            👤 <?php echo htmlspecialchars($log['operator'] ?? '-'); ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            <?php echo ($log['fuel_consumed_qty'] !== null) ? number_format((float)$log['fuel_consumed_qty'], 2) . ' L' : '-'; ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            <?php echo ($log['running_hours'] !== null) ? number_format((float)$log['running_hours'], 1) . ' hr' : '-'; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <?php if (in_array(strtolower($currentUser['role']), ['manager', 'superuser'])): ?>
                                                    <button type="button" class="btn btn-sm px-3 py-1.5 gradient-btn-edit edit-log-btn"
                                                            data-id="<?php echo htmlspecialchars((string)($log['id'] ?? '')); ?>"
                                                            data-office="<?php echo htmlspecialchars($log['officeid'] ?? ''); ?>"
                                                            data-machineid="<?php echo htmlspecialchars($log['machine_id'] ?? ''); ?>"
                                                            data-date="<?php echo htmlspecialchars($log['log_date'] ?? ''); ?>"
                                                            data-operator="<?php echo htmlspecialchars($log['operator'] ?? ''); ?>"
                                                            data-fuel="<?php echo htmlspecialchars($log['fuel_consumed_qty'] ?? ''); ?>"
                                                            data-hours="<?php echo htmlspecialchars($log['running_hours'] ?? ''); ?>">
                                                        Edit
                                                    </button>
                                                <?php endif; ?>

                                                <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                                    <a href="?action=delete&id=<?php echo htmlspecialchars((string)($log['id'] ?? '')); ?>&redirect_to=<?php echo urlencode('?action=editByDate&date=' . $selectedDate); ?>" 
                                                       class="btn btn-sm px-3 py-1.5 gradient-btn-delete" 
                                                       onclick="return confirm('Are you sure you want to delete this daily log entry? This action is recorded in the audit trail.');">
                                                        Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="fs-1 mb-3">📭</div>
                        <h4 class="fw-bold text-secondary">No log entries for this date</h4>
                        <p class="text-muted">There are no machine logs recorded on <?php echo htmlspecialchars(date('d F Y', strtotime($selectedDate))); ?>.</p>
                        <a href="?action=showCreateForm" class="btn btn-primary mt-2 px-4 py-2" style="border-radius: 8px;">
                            Create Logs for this Date
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Premium Edit Modal -->
<div class="modal fade" id="quick-edit-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <form method="POST" action="?action=update">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <!-- Hidden input to redirect back to this date page after saving -->
        <input type="hidden" name="redirect_to" value="?action=editByDate&date=<?php echo htmlspecialchars($selectedDate ?? ''); ?>">
          
          <div class="modal-header bg-dark text-white border-0 py-3 px-4" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <h5 class="modal-title fw-bold">✏️ Edit Log Record</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <div class="modal-body p-4">
            <input type="hidden" name="id" id="edit-log-id">

            <div class="mb-3">
                <label class="form-label text-muted fw-semibold small">MACHINE</label>
                <select name="machine_id" id="edit-log-machine-select" class="form-select py-2" required>
                    <option value="">-- Loading Machines --</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fw-semibold small">LOG DATE</label>
                <input type="date" name="log_date" id="edit-log-date" class="form-control py-2" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fw-semibold small">OPERATOR NAME</label>
                <input type="text" name="operator" id="edit-log-operator" class="form-control py-2" placeholder="e.g. John Doe">
            </div>

            <div class="row g-3">
                <div class="col-6" id="edit-fuel-group">
                    <label class="form-label text-muted fw-semibold small">FUEL QTY (LITRES)</label>
                    <input type="number" name="fuel_consumed_qty" id="edit-log-fuel" class="form-control py-2" step="0.5" placeholder="e.g. 5.5">
                    <div class="form-text text-muted" style="font-size: 0.75rem;">Must be steps of 0.5</div>
                </div>

                <div class="col-6" id="edit-runhours-group">
                    <label class="form-label text-muted fw-semibold small">RUNNING HOURS</label>
                    <input type="number" name="running_hours" id="edit-log-hours" class="form-control py-2" step="0.1" placeholder="e.g. 104.5">
                    <div class="form-text text-muted" style="font-size: 0.75rem;">Max 1 decimal place</div>
                </div>
            </div>
          </div>
          
          <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
            <button type="button" class="btn btn-light px-4 py-2 fw-semibold flex-grow-1" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold flex-grow-1" style="border-radius: 8px; background: #3b82f6; border: none;">Save Corrections</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
// Select quick-access dates instantly
function selectQuickDate(dateStr) {
    const input = document.getElementById('target-date');
    if (input) {
        input.value = dateStr;
        document.getElementById('date-select-form').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-log-btn');
    const editModal = new bootstrap.Modal(document.getElementById('quick-edit-modal'));

    // Modal inputs
    const idInput = document.getElementById('edit-log-id');
    const machineSelect = document.getElementById('edit-log-machine-select');
    const dateInput = document.getElementById('edit-log-date');
    const operatorInput = document.getElementById('edit-log-operator');
    const fuelInput = document.getElementById('edit-log-fuel');
    const hoursInput = document.getElementById('edit-log-hours');

    const fuelGroup = document.getElementById('edit-fuel-group');
    const hoursGroup = document.getElementById('edit-runhours-group');

    let allMachinesCache = null;

    // Format operator name as Title Case on blur
    operatorInput.addEventListener('blur', function() {
        let val = operatorInput.value.trim();
        if (val) {
            val = val.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
            operatorInput.value = val;
        }
    });

    // Function to fetch machines via API if not already cached
    const fetchMachines = async () => {
        if (allMachinesCache !== null) return allMachinesCache;

        try {
            // We use the existing getFormData endpoint to grab the machine list
            const response = await fetch('?action=getFormData');
            const data = await response.json();
            if(data.status === 'success') {
                allMachinesCache = data.machines;
                return allMachinesCache;
            }
        } catch (e) {
            console.error("Failed to load machines for edit form", e);
            return [];
        }
        return [];
    };

    const toggleDynamicFields = () => {
        const selectedMachineId = machineSelect.value;
        const selectedOption = machineSelect.options[machineSelect.selectedIndex];

        if (!selectedMachineId || !selectedOption) {
            fuelGroup.style.display = 'none';
            hoursGroup.style.display = 'none';
            return;
        }

        const hasFuel = selectedOption.dataset.fuel === '1';
        const hasRunHours = selectedOption.dataset.runduration === '1';

        fuelGroup.style.display = hasFuel ? 'block' : 'none';
        hoursGroup.style.display = hasRunHours ? 'block' : 'none';
    };

    machineSelect.addEventListener('change', toggleDynamicFields);

    editButtons.forEach(btn => {
        btn.addEventListener('click', async function() {
            // 1. Show a loading state in the dropdown
            machineSelect.innerHTML = '<option value="">Loading machines...</option>';
            machineSelect.disabled = true;

            // 2. Open the modal immediately
            editModal.show();

            // 3. Populate simple text fields immediately
            idInput.value = this.dataset.id;
            dateInput.value = this.dataset.date;
            operatorInput.value = this.dataset.operator !== '-' ? this.dataset.operator : '';
            fuelInput.value = this.dataset.fuel !== '-' ? this.dataset.fuel : '';
            hoursInput.value = this.dataset.hours !== '-' ? this.dataset.hours : '';

            // 4. Fetch the machines (uses cache after the first click)
            const machines = await fetchMachines();
            const logOfficeId = this.dataset.office;
            const logMachineId = this.dataset.machineid;

            // 5. Build the dropdown options, filtering by the log's office
            machineSelect.innerHTML = '';
            machineSelect.add(new Option('-- Select Machine --', ''));

            machines.forEach(m => {
                if (String(m.officeid) === String(logOfficeId)) {
                    let opt = new Option(m.name, m.id);
                    opt.dataset.fuel = m.fuel_type_name ? '1' : '0';
                    opt.dataset.runduration = m.runduration ? '1' : '0';

                    if (String(m.id) === String(logMachineId)) {
                        opt.selected = true;
                    }
                    machineSelect.add(opt);
                }
            });

            machineSelect.disabled = false;

            // 6. Trigger field display check
            toggleDynamicFields();
        });
    });
});
</script>
