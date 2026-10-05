<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Repair & Maintenance Work Log</title>
</head>
<body>
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">🛠️ Machine Repair & Maintenance Work Log</h1>
            <p class="text-muted mb-0">Record daily machine repairs, spare parts consumption, breakdown work, and maintenance costs.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=jobCards" class="btn btn-outline-primary shadow-sm">
                📋 Job Cards Register
            </a>
            <a href="?action=showCreateForm" class="btn btn-primary shadow-sm">
                ➕ Log Repair / Service
            </a>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Repairs Logged</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($totalRepairs); ?></h2>
                    </div>
                    <div class="display-6">⚙️</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Repair Expenditure</h6>
                        <h2 class="mb-0 fw-bold">₹<?php echo number_format((float)$totalCost, 2); ?></h2>
                    </div>
                    <div class="display-6">💸</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Machinery On Record</h6>
                        <h2 class="mb-0 fw-bold"><?php echo count($machines); ?> Machines</h2>
                    </div>
                    <div class="display-6">🚜</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-center">
                <input type="hidden" name="action" value="list">
                <div class="col-md-6">
                    <label for="machine_id" class="form-label fw-bold">Filter by Machine</label>
                    <select name="machine_id" id="machine_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- All Machines --</option>
                        <?php foreach ($machines as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($selectedMachine == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <?php if ($selectedMachine): ?>
                        <a href="?action=list" class="btn btn-outline-secondary">Clear Filter</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Repair Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 font-monospace">📋 Logged Repair & Maintenance Work</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Job Card / Date</th>
                            <th class="text-start">Machine Name</th>
                            <th>Type</th>
                            <th>Hours Meter</th>
                            <th class="text-start">Work Done / Details</th>
                            <th class="text-start">Spare Parts Used</th>
                            <th>Cost (₹)</th>
                            <th>Technician</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($repairs)): ?>
                            <tr><td colspan="10" class="text-muted py-4">No machine repair work logged yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($repairs as $r): ?>
                                <tr>
                                    <td class="text-start">
                                        <?php if (!empty($r['job_card_no'])): ?>
                                            <span class="badge bg-dark font-monospace mb-1 d-inline-block">
                                                <?php echo htmlspecialchars($r['job_card_no']); ?>
                                            </span>
                                            <br>
                                        <?php endif; ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($r['repair_date']); ?></small>
                                    </td>
                                    <td class="text-start fw-bold"><?php echo htmlspecialchars($r['machine_name']); ?></td>
                                    <td>
                                        <?php if (($r['work_type'] ?? '') === 'service_done'): ?>
                                            <span class="badge bg-primary">🛠️ Service</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">➕ Repair</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $r['running_hours'] !== null ? number_format((float)$r['running_hours'], 1) . ' hrs' : '-'; ?></td>
                                    <td class="text-start" style="max-width: 250px;"><?php echo nl2br(htmlspecialchars($r['work_done'])); ?></td>
                                    <td class="text-start" style="max-width: 220px;">
                                        <?php if (!empty($r['items_count']) && $r['items_count'] > 0): ?>
                                            <span class="badge bg-info text-dark mb-1"><?php echo $r['items_count']; ?> part(s) issued</span><br>
                                        <?php endif; ?>
                                        <?php echo $r['spare_parts_used'] ? '<small class="text-muted">' . nl2br(htmlspecialchars($r['spare_parts_used'])) . '</small>' : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td class="fw-bold">₹<?php echo number_format((float)$r['cost'], 2); ?></td>
                                    <td><?php echo $r['repaired_by'] ? htmlspecialchars($r['repaired_by']) : '<span class="text-muted">-</span>'; ?></td>
                                    <td>
                                        <?php 
                                            $status = $r['status'] ?? 'Completed';
                                            $badgeClass = ($status === 'Completed') ? 'bg-success' : (($status === 'In Progress') ? 'bg-warning text-dark' : 'bg-secondary');
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td>
                                        <a href="?action=showEditForm&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-warning me-1">Edit</a>
                                        <a href="?action=delete&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this repair record? Any deducted spare parts stock will be restored.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>
