<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Machine Logs</title>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Audit Running Hours</h1>
        <a href="?action=list" class="btn btn-secondary">Back to Logs</a>
    </div>

    <!-- Scanner Controls -->
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-dark">
            <h5 class="mb-0">1. Select Machine to Audit</h5>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3 align-items-end">
                <?php if ($userRole === 'superuser'): ?>
                    <div class="col-md-3">
                        <label for="audit_office_id" class="form-label">Office</label>
                        <select id="audit_office_id" class="form-select">
                            <option value="">-- Choose Office --</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label for="audit_machine_id" class="form-label">Machine</label>
                    <select id="audit_machine_id" class="form-select">
                        <option value="">-- Select Office First --</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="button" id="run-audit-btn" class="btn btn-primary w-100">Scan for Anomalies</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Area -->
    <div class="card shadow-sm" id="audit-results-card" style="display:none;">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">2. Anomalies Detected</h5>
            <span class="badge bg-danger fs-6" id="anomaly-count-badge">0 Found</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Error Type</th>
                            <th>Previous Reading<br><small class="text-muted">(Day Before)</small></th>
                            <th class="bg-danger text-white">Current Erroneous Reading</th>
                            <th>Next Reading<br><small class="text-muted">(Day After)</small></th>
                            <th class="bg-success text-white">Proposed Correction<br><small>(Midpoint Average)</small></th>
                        </tr>
                    </thead>
                    <tbody id="audit-results-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-end py-3">
            <button type="button" id="apply-fixes-btn" class="btn btn-success btn-lg px-4">Apply All Proposed Corrections</button>
        </div>
    </div>

    <!-- Empty State Message -->
    <div id="no-anomalies-message" class="alert alert-success mt-4" style="display:none;">
        <h4 class="alert-heading">Machine is Clean!</h4>
        <p>No mathematical anomalies (impossible drops or spikes) were found in the running hours history for this machine.</p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userRole = "<?php echo $userRole; ?>";
    const allMachines = <?php echo json_encode($machines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
    const currentUserOfficeId = "<?php echo $currentUser['officeid']; ?>";

    const officeSelect = document.getElementById('audit_office_id');
    const machineSelect = document.getElementById('audit_machine_id');
    const runAuditBtn = document.getElementById('run-audit-btn');

    const resultsCard = document.getElementById('audit-results-card');
    const tbody = document.getElementById('audit-results-tbody');
    const badge = document.getElementById('anomaly-count-badge');
    const applyBtn = document.getElementById('apply-fixes-btn');
    const noAnomaliesMsg = document.getElementById('no-anomalies-message');

    let currentAnomalies = [];

    // --- Dropdown Logic ---
    const filterMachines = () => {
        let selectedOfficeId = userRole === 'superuser' ? (officeSelect ? officeSelect.value : null) : currentUserOfficeId;

        machineSelect.innerHTML = '';
        machineSelect.add(new Option(selectedOfficeId ? '-- Select Machine --' : '-- Select Office First --', ''));

        if (selectedOfficeId) {
            allMachines.forEach(machine => {
                if (String(machine.officeid) === String(selectedOfficeId) && machine.runduration) {
                    machineSelect.add(new Option(machine.name, machine.id));
                }
            });
        }
    };

    if (userRole === 'superuser' && officeSelect) {
        officeSelect.addEventListener('change', filterMachines);
    }
    filterMachines();

    // --- Auditing Logic ---
    runAuditBtn.addEventListener('click', async () => {
        const machineId = machineSelect.value;
        if (!machineId) {
            alert("Please select a machine to audit.");
            return;
        }

        runAuditBtn.innerText = "Scanning...";
        runAuditBtn.disabled = true;
        resultsCard.style.display = 'none';
        noAnomaliesMsg.style.display = 'none';

        try {
            const response = await fetch(`?action=auditLogs&machine_id=${machineId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || 'Audit failed');
            }

            currentAnomalies = result.data;

            if (currentAnomalies.length === 0) {
                noAnomaliesMsg.style.display = 'block';
            } else {
                renderAnomalies(currentAnomalies);
                resultsCard.style.display = 'block';
            }

        } catch (error) {
            alert('Error running audit: ' + error.message);
        } finally {
            runAuditBtn.innerText = "Scan for Anomalies";
            runAuditBtn.disabled = false;
        }
    });

    const renderAnomalies = (anomalies) => {
        tbody.innerHTML = '';
        badge.innerText = `${anomalies.length} Found`;

        anomalies.forEach(anomaly => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${anomaly.log_date}</td>
                <td class="text-danger fw-bold">${anomaly.type}</td>
                <td class="text-muted">${anomaly.prev_val}</td>
                <td class="bg-danger text-white fw-bold fs-5">${anomaly.curr_val}</td>
                <td class="text-muted">${anomaly.next_val}</td>
                <td class="bg-success text-white fw-bold fs-5">${anomaly.proposed_val}</td>
            `;
            tbody.appendChild(tr);
        });
    };

    // --- Apply Fixes Logic ---
    applyBtn.addEventListener('click', async () => {
        if (currentAnomalies.length === 0) return;

        if (!confirm(`Are you sure you want to apply all ${currentAnomalies.length} mathematical corrections to the database permanently?`)) {
            return;
        }

        applyBtn.innerText = "Applying Fixes...";
        applyBtn.disabled = true;

        // Prepare the payload (just the ID and the new calculated value)
        const fixesPayload = currentAnomalies.map(a => ({
            id: a.id,
            new_hours: a.proposed_val
        }));

        try {
            const response = await fetch(`?action=applyAuditFixes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>", fixes: fixesPayload })
            });
            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || 'Failed to apply fixes');
            }

            alert(result.message);
            // Re-run the scan to verify the table is now clean
            runAuditBtn.click();

        } catch (error) {
            alert('Error applying fixes: ' + error.message);
        } finally {
            applyBtn.innerText = "Apply All Proposed Corrections";
            applyBtn.disabled = false;
        }
    });
});
</script>
</body>
</html>
