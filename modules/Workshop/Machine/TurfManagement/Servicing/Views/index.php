<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Servicing Log</title>
</head>
<body>
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🛠️ Machine Servicing & Maintenance Log</h1>
            <p class="text-muted mb-0">Track machine servicing schedules, technicians, costs, and upcoming due intervals.</p>
        </div>
        <div>
            <a href="?action=showCreateForm" class="btn btn-primary shadow-sm">
                ➕ Log New Servicing
            </a>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Services Logged</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($totalServices); ?></h2>
                    </div>
                    <div class="display-6">🔧</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Servicing Cost</h6>
                        <h2 class="mb-0 fw-bold">₹<?php echo number_format((float)$totalCost, 2); ?></h2>
                    </div>
                    <div class="display-6">💰</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Active Machinery</h6>
                        <h2 class="mb-0 fw-bold"><?php echo count($machines); ?> Machines</h2>
                    </div>
                    <div class="display-6">🚜</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Predictive Machine Service Due Status Widget -->
    <?php
        $redMachines = [];
        $yellowMachines = [];
        $greenMachines = [];
        $noDataMachines = [];

        foreach ($dueStatuses as $status) {
            $interval = (float)($status['service_interval_hours'] ?: 100);
            $lastServiceHours = $status['last_service_hours'] !== null ? (float)$status['last_service_hours'] : 0;
            $currentHours = $status['current_running_hours'] !== null ? (float)$status['current_running_hours'] : 0;
            $dueHours = $lastServiceHours + $interval;
            $remaining = $dueHours - $currentHours;

            $status['calc_interval'] = $interval;
            $status['calc_last_service_hours'] = $lastServiceHours;
            $status['calc_current_hours'] = $currentHours;
            $status['calc_due_hours'] = $dueHours;
            $status['calc_remaining'] = $remaining;

            if ($lastServiceHours == 0 && $currentHours == 0) {
                $noDataMachines[] = $status;
            } elseif ($remaining <= 0) {
                $redMachines[] = $status;
            } elseif ($remaining <= 20) {
                $yellowMachines[] = $status;
            } else {
                $greenMachines[] = $status;
            }
        }

        // Helper renderer function for status tables
        function renderStatusTable(array $items, string $category) {
            if (empty($items)) {
                echo '<div class="p-3 text-muted text-center">No machines in this category.</div>';
                return;
            }
            echo '<div class="table-responsive"><table class="table table-hover align-middle mb-0 text-center"><thead class="table-light"><tr>';
            echo '<th class="text-start">Machine Name</th><th>Service Interval</th><th>Last Service Date</th><th>Last Service Hours</th><th>Current Meter Hours</th><th>Next Service Due</th><th>Hours Remaining</th><th>Status</th><th>Action</th></tr></thead><tbody>';

            foreach ($items as $status) {
                $interval = $status['calc_interval'];
                $lastServiceHours = $status['calc_last_service_hours'];
                $currentHours = $status['calc_current_hours'];
                $dueHours = $status['calc_due_hours'];
                $remaining = $status['calc_remaining'];

                if ($category === 'red') {
                    $badgeClass = 'bg-danger';
                    $badgeText = '🔴 OVERDUE (' . abs(round($remaining, 1)) . ' hrs overdue)';
                } elseif ($category === 'yellow') {
                    $badgeClass = 'bg-warning text-dark';
                    $badgeText = '🟡 DUE SOON (' . number_format($remaining, 1) . ' hrs left)';
                } elseif ($category === 'green') {
                    $badgeClass = 'bg-success';
                    $badgeText = '🟢 OK (' . number_format($remaining, 1) . ' hrs left)';
                } else {
                    $badgeClass = 'bg-secondary';
                    $badgeText = 'No Log Data';
                }

                echo '<tr>';
                echo '<td class="text-start fw-bold">' . htmlspecialchars($status['machine_name']) . '</td>';
                echo '<td><span class="badge bg-light text-dark border">' . number_format($interval) . ' hrs</span></td>';
                echo '<td>' . ($status['last_service_date'] ? htmlspecialchars((string)$status['last_service_date']) : '<span class="text-muted">Never Logged</span>') . '</td>';
                echo '<td>' . ($status['last_service_hours'] !== null ? number_format($lastServiceHours, 1) . ' hrs' : '-') . '</td>';
                echo '<td><strong>' . ($status['current_running_hours'] !== null ? number_format($currentHours, 1) . ' hrs' : '-') . '</strong>';
                if (!empty($status['current_meter_date'])) {
                    echo '<br><small class="text-muted" style="font-size: 11px;">(as of ' . htmlspecialchars((string)$status['current_meter_date']) . ')</small>';
                }
                echo '</td>';
                echo '<td><strong>' . number_format($dueHours, 1) . ' hrs</strong></td>';
                echo '<td><span class="fw-bold ' . ($remaining <= 0 ? 'text-danger' : ($remaining <= 20 ? 'text-warning' : 'text-success')) . '">' . number_format($remaining, 1) . ' hrs</span></td>';
                echo '<td><span class="badge ' . $badgeClass . ' p-2" style="font-size: 12px;">' . $badgeText . '</span></td>';
                echo '<td><a href="?action=showCreateForm&machine_id=' . $status['machine_id'] . '" class="btn btn-sm btn-primary">Log Service</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
    ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 font-monospace">⏱️ Predictive Machine Maintenance Due Status</h5>
                <small class="text-muted">Showing machines with <strong>Run Duration Tracking (Hours)</strong> enabled</small>
            </div>

            <!-- Category Navigation Tabs -->
            <ul class="nav nav-pills card-header-pills" id="maintenanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active btn-sm fw-bold border border-danger text-danger me-2" id="red-tab" data-bs-toggle="tab" data-bs-target="#red-section" type="button" role="tab">
                        🚨 Overdue Servicing <span class="badge bg-danger ms-1"><?php echo count($redMachines); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn-sm fw-bold border border-warning text-dark me-2" id="yellow-tab" data-bs-toggle="tab" data-bs-target="#yellow-section" type="button" role="tab">
                        ⚠️ Servicing Due Soon <span class="badge bg-warning text-dark ms-1"><?php echo count($yellowMachines); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn-sm fw-bold border border-success text-success me-2" id="green-tab" data-bs-toggle="tab" data-bs-target="#green-section" type="button" role="tab">
                        ✅ Healthy Machinery <span class="badge bg-success ms-1"><?php echo count($greenMachines); ?></span>
                    </button>
                </li>
                <?php if (!empty($noDataMachines)): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm fw-bold border border-secondary text-secondary" id="nodata-tab" data-bs-toggle="tab" data-bs-target="#nodata-section" type="button" role="tab">
                            ℹ️ Pending Log Data <span class="badge bg-secondary ms-1"><?php echo count($noDataMachines); ?></span>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="maintenanceTabContent">
                <!-- Red Section (Overdue) -->
                <div class="tab-pane fade show active" id="red-section" role="tabpanel">
                    <?php renderStatusTable($redMachines, 'red'); ?>
                </div>

                <!-- Yellow Section (Due Soon) -->
                <div class="tab-pane fade" id="yellow-section" role="tabpanel">
                    <?php renderStatusTable($yellowMachines, 'yellow'); ?>
                </div>

                <!-- Green Section (Healthy) -->
                <div class="tab-pane fade" id="green-section" role="tabpanel">
                    <?php renderStatusTable($greenMachines, 'green'); ?>
                </div>

                <!-- Pending Log Data Section -->
                <?php if (!empty($noDataMachines)): ?>
                    <div class="tab-pane fade" id="nodata-section" role="tabpanel">
                        <?php renderStatusTable($noDataMachines, 'nodata'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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

    <!-- Servicing Log Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-monospace">Servicing Records</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Sl. No.</th>
                            <th>Date</th>
                            <th>Machine</th>
                            <th>Service Type</th>
                            <th>Meter Reading</th>
                            <th>Next Due at</th>
                            <th>Serviced By</th>
                            <th>Cost (₹)</th>
                            <th>Job Card #</th>
                            <th>Remarks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($servicingLogs)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    No servicing records found. Click <strong>"➕ Log New Servicing"</strong> to record maintenance.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $slNo = 1; foreach ($servicingLogs as $log): ?>
                                <tr>
                                    <td><?php echo $slNo++; ?></td>
                                    <td><strong><?php echo htmlspecialchars((string)$log['service_date']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 13px;">
                                            <?php echo htmlspecialchars($log['machine_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?php echo htmlspecialchars($log['service_type'] ?: 'Routine Maintenance'); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format((float)$log['hours_at_service'], 1); ?> hrs</strong></td>
                                    <td>
                                        <?php 
                                            $nextDue = (float)$log['hours_at_service'] + (float)$log['next_service_due']; 
                                            echo number_format($nextDue, 1) . ' hrs';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['serviced_by'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($log['cost'] !== null): ?>
                                            <span class="fw-bold text-success">₹<?php echo number_format((float)$log['cost'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($log['job_card_no'] ?: '-'); ?></code></td>
                                    <td style="max-width: 200px;" class="text-truncate" title="<?php echo htmlspecialchars($log['remarks'] ?: ''); ?>">
                                        <?php echo htmlspecialchars($log['remarks'] ?: '-'); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?action=showEditForm&id=<?php echo $log['id']; ?>" class="btn btn-sm btn-outline-warning">
                                            Edit
                                        </a>
                                        <?php if ($userRole === 'superuser'): ?>
                                            <a href="?action=delete&id=<?php echo $log['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger ms-1" 
                                               onclick="return confirm('Are you sure you want to delete this servicing log record?');">
                                                Del
                                            </a>
                                        <?php endif; ?>
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
