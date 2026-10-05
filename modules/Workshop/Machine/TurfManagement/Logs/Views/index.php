<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Logs</title>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Machine Consumption Logs</h1>
        <div class="d-flex align-items-center gap-3">
            <form method="GET" action="" class="d-flex align-items-center gap-2">
                <input type="hidden" name="action" value="list">
                <label for="month-select" class="text-nowrap mb-0 fw-bold">Filter by Month:</label>
                <select name="month" id="month-select" class="form-select" onchange="this.form.submit()">
                    <option value="trends" <?php echo ($selectedMonth === 'trends') ? 'selected' : ''; ?>>
                        📈 Monthly Consumption Trends (Graphical)
                    </option>
                    <?php foreach ($availableMonths as $month): ?>
                        <option value="<?php echo htmlspecialchars($month); ?>" <?php echo ($month === $selectedMonth) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(date('F Y', strtotime($month . '-01'))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if (in_array(strtolower($currentUser['role']), ['manager', 'superuser'])): ?>
                <a href="?action=editByDate" class="btn btn-warning">✏️ Edit Daily Logs</a>
            <?php endif; ?>
            <a href="?action=showCreateForm" class="btn btn-primary">Add New Log</a>
        </div>
    </div>

    <?php if ($selectedMonth !== 'trends'): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                        <th>Office</th>
                    <?php endif; ?>
                    <th>Date</th>
                    <th>Machine</th>
                    <th>Operator</th>
                    <th>Fuel Consumed</th>
                    <th>Running Hours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($log['id'] ?? '')); ?></td>
                            <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                <td><?php echo htmlspecialchars($log['office_name'] ?? ''); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($log['log_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($log['machine_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($log['operator'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($log['fuel_consumed_qty'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($log['running_hours'] ?? '-'); ?></td>
                            <td>
                                <?php if (in_array(strtolower($currentUser['role']), ['manager', 'superuser'])): ?>
                                    <button type="button" class="btn btn-sm btn-warning edit-log-btn"
                                            data-id="<?php echo htmlspecialchars((string)($log['id'] ?? '')); ?>"
                                            data-office="<?php echo htmlspecialchars($log['officeid'] ?? ''); ?>"
                                            data-machineid="<?php echo htmlspecialchars($log['machine_id'] ?? ''); ?>"
                                            data-date="<?php echo htmlspecialchars($log['log_date'] ?? ''); ?>"
                                            data-operator="<?php echo htmlspecialchars($log['operator'] ?? ''); ?>"
                                            data-fuel="<?php echo htmlspecialchars($log['fuel_consumed_qty'] ?? ''); ?>"
                                            data-hours="<?php echo htmlspecialchars($log['running_hours'] ?? ''); ?>"
                                            >Edit</button>
                                <?php endif; ?>

                                <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                    <a href="?action=delete&id=<?php echo htmlspecialchars((string)($log['id'] ?? '')); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this log?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo (strtolower($currentUser['role']) === 'superuser') ? '8' : '7'; ?>">No logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="card mb-4 border-0 shadow-sm" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-radius: 12px;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="mb-0">📈 Fuel Consumption Trends (Petrol vs. Diesel)</h5>
            </div>
            <div class="card-body" style="position: relative; height: 450px;">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Simple Edit Modal -->
<div class="modal fade" id="quick-edit-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=update">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
          <div class="modal-header">
            <h5 class="modal-title">Edit Log Entry</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit-log-id">

            <div class="mb-3">
                <label class="form-label">Machine</label>
                <!-- This select will be dynamically populated via Javascript based on the office of the log -->
                <select name="machine_id" id="edit-log-machine-select" class="form-select" required>
                    <option value="">-- Loading Machines --</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="log_date" id="edit-log-date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Operator</label>
                <input type="text" name="operator" id="edit-log-operator" class="form-control">
            </div>

            <div class="mb-3" id="edit-fuel-group">
                <label class="form-label">Fuel Consumed</label>
                <input type="number" name="fuel_consumed_qty" id="edit-log-fuel" class="form-control" step="0.01">
            </div>

            <div class="mb-3" id="edit-runhours-group">
                <label class="form-label">Running Hours</label>
                <input type="number" name="running_hours" id="edit-log-hours" class="form-control" step="0.01">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
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

            // 2. Open the modal immediately so it feels responsive
            editModal.show();

            // 3. Populate simple text fields immediately
            idInput.value = this.dataset.id;
            dateInput.value = this.dataset.date;
            operatorInput.value = this.dataset.operator !== '-' ? this.dataset.operator : '';
            fuelInput.value = this.dataset.fuel !== '-' ? this.dataset.fuel : '';
            hoursInput.value = this.dataset.hours !== '-' ? this.dataset.hours : '';

            // 4. Fetch the machines (will use cache after the first click)
            const machines = await fetchMachines();
            const logOfficeId = this.dataset.office;
            const logMachineId = this.dataset.machineid;

            // 5. Build the dropdown options, filtering by the log's office
            machineSelect.innerHTML = '';
            machineSelect.add(new Option('-- Select Machine --', ''));

            machines.forEach(m => {
                // If it's a superuser, they might see logs from multiple offices.
                // We only want to show machines that belong to the SAME office as the log being edited.
                if (String(m.officeid) === String(logOfficeId)) {
                    let opt = new Option(m.name, m.id);
                    opt.dataset.fuel = m.fuel_type_name ? '1' : '0';
                    opt.dataset.runduration = m.runduration ? '1' : '0';

                    // Pre-select the correct machine
                    if (String(m.id) === String(logMachineId)) {
                        opt.selected = true;
                    }
                    machineSelect.add(opt);
                }
            });

            machineSelect.disabled = false;

            // 6. Trigger the toggle to show/hide fuel and hours based on the newly selected machine
            toggleDynamicFields();
        });
    });
});
</script>

<?php if ($selectedMonth === 'trends'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawData = <?php echo json_encode($trendsData); ?>;
    
    const labels = rawData.map(item => {
        const parts = item.log_month.split('-');
        const date = new Date(parts[0], parts[1] - 1, 1);
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    
    const petrolData = rawData.map(item => parseFloat(item.total_petrol || 0));
    const dieselData = rawData.map(item => parseFloat(item.total_diesel || 0));

    const ctx = document.getElementById('trendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Petrol Consumption (Litres)',
                    data: petrolData,
                    backgroundColor: 'rgba(255, 193, 7, 0.65)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1.5,
                    borderRadius: 4
                },
                {
                    label: 'Diesel Consumption (Litres)',
                    data: dieselData,
                    backgroundColor: 'rgba(33, 37, 41, 0.65)',
                    borderColor: 'rgba(33, 37, 41, 1)',
                    borderWidth: 1.5,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantity (Litres)',
                        font: { weight: 'bold' }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month',
                        font: { weight: 'bold' }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ` ${context.dataset.label}: ${context.raw.toFixed(2)} L`;
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>
</body>
</html>
