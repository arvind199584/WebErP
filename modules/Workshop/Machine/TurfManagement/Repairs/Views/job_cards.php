<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop Job Cards Register & Summary</title>
</head>
<body>
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">📋 Workshop Job Cards Register & Summary</h1>
            <p class="text-muted mb-0">Audit tracking of all Job IDs, spare parts depletion, and machine service logs.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=showCreateForm" class="btn btn-primary shadow-sm">
                ➕ New Job Card / Repair
            </a>
            <a href="?action=list" class="btn btn-outline-secondary">
                ← All Repairs Log
            </a>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Job Cards Issued</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($totalJobCards); ?></h2>
                    </div>
                    <div class="display-6">📋</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Spare Parts Issued</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format((float)$totalPartsIssued, 1); ?> Units</h2>
                    </div>
                    <div class="display-6">⚙️</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Job Card Cost</h6>
                        <h2 class="mb-0 fw-bold">₹<?php echo number_format((float)$totalCost, 2); ?></h2>
                    </div>
                    <div class="display-6">💸</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="jobCards">

                <div class="col-md-3">
                    <label for="search" class="form-label fw-bold small">Search Job Card / Machine</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search Job ID, machine, work..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                </div>

                <div class="col-md-3">
                    <label for="machine_id" class="form-label fw-bold small">Filter by Machine</label>
                    <select name="machine_id" id="machine_id" class="form-select">
                        <option value="">-- All Machines --</option>
                        <?php foreach ($machines as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($filters['machine_id'] == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="work_type" class="form-label fw-bold small">Work Category</label>
                    <select name="work_type" id="work_type" class="form-select">
                        <option value="">-- All Categories --</option>
                        <option value="service_done" <?php echo ($filters['work_type'] === 'service_done') ? 'selected' : ''; ?>>🛠️ Service Done</option>
                        <option value="repair" <?php echo ($filters['work_type'] === 'repair') ? 'selected' : ''; ?>>➕ General Repair</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="from_date" class="form-label fw-bold small">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo htmlspecialchars($filters['from_date']); ?>">
                </div>

                <div class="col-md-2">
                    <label for="to_date" class="form-label fw-bold small">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo htmlspecialchars($filters['to_date']); ?>">
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                    <button type="submit" class="btn btn-primary px-3">🔍 Filter</button>
                    <?php if (!empty($filters['search']) || !empty($filters['machine_id']) || !empty($filters['work_type']) || !empty($filters['from_date']) || !empty($filters['to_date'])): ?>
                        <a href="?action=jobCards" class="btn btn-outline-secondary">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Job Cards Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-monospace">📑 Issued Job Cards Summary</h5>
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()">🖨️ Print Report</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="text-start">Job Card No.</th>
                            <th class="text-start">Date</th>
                            <th class="text-start">Machine Name</th>
                            <th>Category</th>
                            <th class="text-start">Work Done</th>
                            <th>Meter (Hrs)</th>
                            <th class="text-start">Parts Consumed</th>
                            <th>Cost (₹)</th>
                            <th>Mechanic</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jobCards)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <h5>No Job Cards Found</h5>
                                    <p class="mb-0">No job cards match your filter criteria or none have been logged yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jobCards as $jc): ?>
                                <tr>
                                    <td class="text-start">
                                        <span class="badge bg-dark font-monospace fs-6 px-2 py-1">
                                            <?php echo htmlspecialchars($jc['job_card_no']); ?>
                                        </span>
                                    </td>
                                    <td class="text-start text-nowrap">
                                        <?php echo date('d-M-Y', strtotime($jc['repair_date'])); ?>
                                    </td>
                                    <td class="text-start fw-bold">
                                        <?php echo htmlspecialchars($jc['machine_name']); ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($jc['work_type'] === 'service_done'): ?>
                                            <span class="badge bg-primary">🛠️ Service Done</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">➕ Repair</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start" style="max-width: 250px;">
                                        <?php echo htmlspecialchars($jc['work_done']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $jc['running_hours'] ? number_format((float)$jc['running_hours'], 1) : '-'; ?>
                                    </td>
                                    <td class="text-start" style="min-width: 200px;">
                                        <?php if (!empty($jc['items'])): ?>
                                            <ul class="list-unstyled mb-0 small">
                                                <?php foreach ($jc['items'] as $item): ?>
                                                    <li>
                                                        <span class="text-dark fw-bold">• <?php echo htmlspecialchars($item['nomenclature']); ?></span>
                                                        <span class="text-muted">(<?php echo htmlspecialchars($item['part_no'] ?: 'No Code'); ?>)</span>: 
                                                        <span class="badge bg-light text-dark border"><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'Pcs'); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php elseif (!empty($jc['spare_parts_used'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($jc['spare_parts_used']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-nowrap">
                                        ₹<?php echo number_format((float)$jc['cost'], 2); ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php echo htmlspecialchars($jc['repaired_by'] ?: 'In-house'); ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge <?php echo ($jc['status'] === 'Completed') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo htmlspecialchars($jc['status']); ?>
                                        </span>
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
