<?php
// modules/Workshop/Machine/TurfManagement/Machines/Views/status.php
/**
 * @var array $machines
 * @var int $countOverdue
 * @var int $countDueSoon
 * @var int $countGood
 * @var int $countNoData
 * @var int $totalAlerts
 * @var int $totalFleet
 * @var string $statusFilter
 * @var float $threshold
 * @var int|null $selectedOffice
 * @var array $offices
 * @var array|null $currentUser
 * @var string $userRole
 */
$isSuperuser = (strtolower($userRole ?? '') === 'superuser');
?>
<div class="container-fluid py-3">

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">🚜 Machine Fleet & Service Status</h1>
            <p class="text-muted mb-0">Real-time monitoring of machine meter readings, upcoming routine servicing, and overdue maintenance alerts.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/modules/Workshop/Machine/TurfManagement/Repairs/Controller/RepairController.php?action=showCreateForm" class="btn btn-primary shadow-sm">
                🛠️ Log Service / Repair
            </a>
            <a href="?action=list" class="btn btn-outline-secondary">
                📋 Machine Master List
            </a>
            <a href="/modules/Workshop/Machine/TurfManagement/Servicing/Controller/ServicingController.php?action=list" class="btn btn-outline-secondary">
                📜 Service Logs
            </a>
            <button onclick="window.print()" class="btn btn-outline-dark">
                🖨️ Print
            </button>
        </div>
    </div>

    <!-- Summary KPI Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Overdue Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm <?php echo ($countOverdue > 0) ? 'bg-danger text-white' : 'bg-light'; ?> h-100 cursor-pointer stat-card" onclick="filterByTab('overdue')">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="<?php echo ($countOverdue > 0) ? 'text-white-50' : 'text-muted'; ?> text-uppercase fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Overdue Services
                        </h6>
                        <h2 class="mb-0 fw-bold display-6"><?php echo number_format($countOverdue); ?></h2>
                        <small class="<?php echo ($countOverdue > 0) ? 'text-white-50' : 'text-muted'; ?>">
                            <?php echo ($countOverdue > 0) ? 'Requires immediate servicing' : 'No overdue machines'; ?>
                        </small>
                    </div>
                    <div class="display-5 opacity-75">🚨</div>
                </div>
            </div>
        </div>

        <!-- Due Soon Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm <?php echo ($countDueSoon > 0) ? 'bg-warning text-dark' : 'bg-light'; ?> h-100 cursor-pointer stat-card" onclick="filterByTab('due_soon')">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1 opacity-75" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Due Soon (≤ <?php echo (int)$threshold; ?> hrs)
                        </h6>
                        <h2 class="mb-0 fw-bold display-6"><?php echo number_format($countDueSoon); ?></h2>
                        <small class="opacity-75">
                            Approaching service milestone
                        </small>
                    </div>
                    <div class="display-5 opacity-75">⏳</div>
                </div>
            </div>
        </div>

        <!-- On Schedule Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm bg-success text-white h-100 cursor-pointer stat-card" onclick="filterByTab('good')">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Up to Date / Healthy
                        </h6>
                        <h2 class="mb-0 fw-bold display-6"><?php echo number_format($countGood); ?></h2>
                        <small class="text-white-50">
                            Service hours within range
                        </small>
                    </div>
                    <div class="display-5 opacity-75">✅</div>
                </div>
            </div>
        </div>

        <!-- Total Fleet Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm bg-dark text-white h-100 cursor-pointer stat-card" onclick="filterByTab('all')">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Total Fleet Monitored
                        </h6>
                        <h2 class="mb-0 fw-bold display-6"><?php echo number_format($totalFleet); ?></h2>
                        <small class="text-white-50">
                            <?php echo $totalAlerts; ?> machines need attention
                        </small>
                    </div>
                    <div class="display-5 opacity-75">🚜</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <!-- Navigation Tabs / Pills -->
                <div class="col-lg-6">
                    <ul class="nav nav-pills gap-1 flex-wrap" id="status-tabs">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold btn-tab" data-filter="alerts" onclick="setFilter('alerts')">
                                🚨 Urgent Alerts <span class="badge bg-danger ms-1"><?php echo $totalAlerts; ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold btn-tab" data-filter="overdue" onclick="setFilter('overdue')">
                                🔴 Overdue <span class="badge bg-danger ms-1"><?php echo $countOverdue; ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold btn-tab" data-filter="due_soon" onclick="setFilter('due_soon')">
                                🟡 Due Soon <span class="badge bg-warning text-dark ms-1"><?php echo $countDueSoon; ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold btn-tab" data-filter="good" onclick="setFilter('good')">
                                🟢 On Schedule <span class="badge bg-success ms-1"><?php echo $countGood; ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold btn-tab" data-filter="all" onclick="setFilter('all')">
                                📋 All Fleet <span class="badge bg-secondary ms-1"><?php echo $totalFleet; ?></span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Search and Controls -->
                <div class="col-lg-6">
                    <div class="d-flex gap-2 justify-content-lg-end flex-wrap align-items-center">
                        <?php if ($isSuperuser && !empty($offices)): ?>
                            <select id="office-select" class="form-select form-select-sm" style="width: auto;" onchange="applyOfficeFilter(this.value)">
                                <option value="">-- All Offices --</option>
                                <?php foreach ($offices as $o): ?>
                                    <option value="<?php echo $o['officeid']; ?>" <?php echo ($selectedOffice == $o['officeid']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($o['officename']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <!-- Threshold Selector -->
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text bg-light text-muted">Alert if Due in:</span>
                            <select id="threshold-select" class="form-select form-select-sm" onchange="applyThreshold(this.value)">
                                <option value="10" <?php echo ($threshold == 10) ? 'selected' : ''; ?>>10 Hours</option>
                                <option value="20" <?php echo ($threshold == 20) ? 'selected' : ''; ?>>20 Hours (Default)</option>
                                <option value="30" <?php echo ($threshold == 30) ? 'selected' : ''; ?>>30 Hours</option>
                                <option value="50" <?php echo ($threshold == 50) ? 'selected' : ''; ?>>50 Hours</option>
                            </select>
                        </div>

                        <!-- Live Search Input -->
                        <div class="input-group input-group-sm" style="min-width: 180px; max-width: 260px;">
                            <span class="input-group-text bg-white">🔍</span>
                            <input type="text" id="machine-search-input" class="form-control" placeholder="Search machine or make..." oninput="handleSearch(this.value)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Machines Status Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="machines-status-table">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width: 180px;">Machine & Make</th>
                        <?php if ($isSuperuser): ?>
                            <th>Office</th>
                        <?php endif; ?>
                        <th class="text-center" style="min-width: 110px;">Current Meter</th>
                        <th style="min-width: 170px;">Service Schedule</th>
                        <th class="text-center" style="min-width: 140px;">Service Status</th>
                        <th style="min-width: 180px;">Service Progress</th>
                        <th style="min-width: 160px;">Service Kit Required</th>
                        <th class="text-end" style="min-width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody id="machines-table-body">
                    <?php if (!empty($machines)): ?>
                        <?php foreach ($machines as $m): ?>
                            <?php
                                $statusKey = $m['status_key'];
                                $isOverdue = ($statusKey === 'overdue');
                                $isDueSoon = ($statusKey === 'due_soon');
                                $isAlert = ($isOverdue || $isDueSoon);

                                $rowClass = '';
                                if ($isOverdue) $rowClass = 'table-danger border-start border-danger border-4';
                                elseif ($isDueSoon) $rowClass = 'table-warning border-start border-warning border-4';

                                $meterDisplay = ($m['computed_meter'] !== null) 
                                    ? number_format((float)$m['computed_meter'], 1) . ' hrs' 
                                    : '<span class="text-muted fst-italic">No logs</span>';

                                $oilQty = (float)($m['engine_oil_qty'] ?? 0);
                                $serviceItems = $m['default_service_items'] ?? [];
                            ?>
                            <tr class="machine-status-row <?php echo $rowClass; ?>" 
                                data-status="<?php echo $statusKey; ?>" 
                                data-alert="<?php echo $isAlert ? '1' : '0'; ?>"
                                data-name="<?php echo strtolower(htmlspecialchars($m['name'])); ?>"
                                data-make="<?php echo strtolower(htmlspecialchars($m['make'] ?? '')); ?>"
                                data-office="<?php echo strtolower(htmlspecialchars($m['office_name'] ?? '')); ?>">

                                <!-- Machine Name & Make -->
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($m['name']); ?></div>
                                    <div class="small text-muted">
                                        <?php if (!empty($m['make'])): ?>
                                            <span>Make: <?php echo htmlspecialchars($m['make']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($m['fuel_name'])): ?>
                                            <span class="badge bg-light text-secondary border ms-1">⛽ <?php echo htmlspecialchars($m['fuel_name']); ?></span>
                                        <?php endif; ?>
                                        <span class="badge <?php echo ($m['machine_status'] === 'Working') ? 'bg-success-subtle text-success' : 'bg-secondary'; ?> ms-1">
                                            <?php echo htmlspecialchars($m['machine_status'] ?? 'Active'); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Office (Superuser only) -->
                                <?php if ($isSuperuser): ?>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            🏢 <?php echo htmlspecialchars($m['office_name']); ?>
                                        </span>
                                    </td>
                                <?php endif; ?>

                                <!-- Current Meter Reading -->
                                <td class="text-center">
                                    <div class="fw-bold fs-6 font-monospace text-primary"><?php echo $meterDisplay; ?></div>
                                    <?php if (!empty($m['last_run_date'])): ?>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                            📅 <?= htmlspecialchars($m['last_run_date']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <!-- Service Schedule -->
                                <td>
                                    <div class="small">
                                        <span class="text-muted">Last Service:</span> 
                                        <strong><?php echo number_format((float)$m['last_service_hours'], 1); ?> hrs</strong>
                                        <?php if (!empty($m['last_service_date'])): ?>
                                            <span class="text-muted">(<?php echo htmlspecialchars($m['last_service_date']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small mt-1">
                                        <span class="text-muted">Next Due:</span> 
                                        <strong class="<?php echo $isOverdue ? 'text-danger fw-bold' : ($isDueSoon ? 'text-warning fw-bold' : 'text-success'); ?>">
                                            <?php echo number_format((float)$m['next_service_due'], 1); ?> hrs
                                        </strong>
                                        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.7rem;">
                                            Every <?= number_format((float)$m['interval_hours']); ?> hrs
                                        </span>
                                    </div>
                                </td>

                                <!-- Service Status Badge -->
                                <td class="text-center">
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger fs-6 py-2 px-3 shadow-sm d-inline-block">
                                            ⚠️ OVERDUE by <?php echo number_format($m['hours_overdue'], 1); ?> hrs
                                        </span>
                                    <?php elseif ($isDueSoon): ?>
                                        <span class="badge bg-warning text-dark fs-6 py-2 px-3 shadow-sm d-inline-block">
                                            ⏳ DUE in <?php echo number_format($m['remaining_hours'], 1); ?> hrs
                                        </span>
                                    <?php elseif ($statusKey === 'good'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success fs-6 py-2 px-3 d-inline-block">
                                            ✓ <?php echo number_format($m['remaining_hours'], 1); ?> hrs left
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border fs-6 py-2 px-3 d-inline-block">
                                            <?php echo htmlspecialchars($m['status_label']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Service Progress Bar -->
                                <td>
                                    <?php if ($m['computed_meter'] !== null && $m['computed_meter'] > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.75rem;">
                                            <span class="text-muted">Interval Used:</span>
                                            <span class="fw-bold <?php echo $isOverdue ? 'text-danger' : ($isDueSoon ? 'text-warning text-dark' : 'text-muted'); ?>">
                                                <?php echo $m['progress_percent']; ?>%
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <?php
                                                $barClass = 'bg-success';
                                                if ($isOverdue) $barClass = 'bg-danger progress-bar-striped progress-bar-animated';
                                                elseif ($isDueSoon) $barClass = 'bg-warning progress-bar-striped';
                                                $barWidth = min(100, $m['progress_percent']);
                                            ?>
                                            <div class="progress-bar <?php echo $barClass; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $barWidth; ?>%;" 
                                                 aria-valuenow="<?php echo $barWidth; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Tracking disabled / not metered</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Service Kit Required -->
                                <td>
                                    <?php if ($oilQty > 0 || !empty($serviceItems)): ?>
                                        <?php if ($oilQty > 0): ?>
                                            <div class="badge bg-primary-subtle text-primary border border-primary mb-1 d-block text-start py-1">
                                                🛢️ Engine Oil: <strong><?php echo number_format($oilQty, 2); ?> Ltr</strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($serviceItems)): ?>
                                            <small class="text-muted d-block">
                                                ⚙️ +<?php echo count($serviceItems); ?> Spare Part(s) configured
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Standard Maintenance</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Action Buttons -->
                                <td class="text-end">
                                    <div class="d-flex flex-column gap-1 align-items-end">
                                        <a href="/modules/Workshop/Machine/TurfManagement/Repairs/Controller/RepairController.php?action=showCreateForm&machine_id=<?php echo $m['id']; ?>&work_type=service_done" 
                                           class="btn btn-sm <?php echo $isOverdue ? 'btn-danger' : ($isDueSoon ? 'btn-warning' : 'btn-outline-primary'); ?> w-100 shadow-sm"
                                           title="Log Routine Service for <?php echo htmlspecialchars($m['name']); ?>">
                                            🛠️ Service Now
                                        </a>
                                        <div class="btn-group btn-group-sm w-100">
                                            <a href="/modules/Workshop/Machine/TurfManagement/Servicing/Controller/ServicingController.php?action=list&machine_id=<?php echo $m['id']; ?>" 
                                               class="btn btn-outline-secondary" 
                                               title="View previous service records">
                                                📜 History
                                            </a>
                                            <a href="?action=showEditForm&id=<?php echo $m['id']; ?>" 
                                               class="btn btn-outline-secondary" 
                                               title="Edit Machine & Service Kit Settings">
                                                ✏️
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $isSuperuser ? '8' : '7'; ?>" class="text-center py-5">
                                <div class="display-6 text-muted mb-2">🚜</div>
                                <h5 class="text-muted">No machines found for the selected criteria.</h5>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <!-- Empty state row for client-side filter hiding all rows -->
                    <tr id="no-matching-row" style="display: none;">
                        <td colspan="<?php echo $isSuperuser ? '8' : '7'; ?>" class="text-center py-5">
                            <div class="display-6 text-success mb-2">🎉</div>
                            <h5 class="fw-bold text-dark">No machines require attention under this filter!</h5>
                            <p class="text-muted mb-0">All machines are up-to-date and within healthy operating service intervals.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.cursor-pointer {
    cursor: pointer;
}
.machine-status-row.table-danger {
    background-color: #fff5f5 !important;
}
.machine-status-row.table-warning {
    background-color: #fffdf0 !important;
}
@media print {
    .btn, #status-tabs, .input-group, #threshold-select, #office-select {
        display: none !important;
    }
}
</style>

<script>
let currentFilter = 'alerts'; // Default view: alerts (Overdue + Due Soon)
let currentSearch = '';

function setFilter(filter) {
    currentFilter = filter;

    // Update active tab buttons
    document.querySelectorAll('.btn-tab').forEach(btn => {
        if (btn.getAttribute('data-filter') === filter) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    applyFilters();
}

function filterByTab(filter) {
    setFilter(filter);
    document.getElementById('machines-status-table').scrollIntoView({ behavior: 'smooth' });
}

function handleSearch(term) {
    currentSearch = term.toLowerCase().trim();
    applyFilters();
}

function applyFilters() {
    const rows = document.querySelectorAll('.machine-status-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        const isAlert = row.getAttribute('data-alert') === '1';
        const name = row.getAttribute('data-name') || '';
        const make = row.getAttribute('data-make') || '';
        const office = row.getAttribute('data-office') || '';

        // 1. Status Filter Check
        let matchesStatus = false;
        if (currentFilter === 'alerts') {
            matchesStatus = isAlert;
        } else if (currentFilter === 'all') {
            matchesStatus = true;
        } else {
            matchesStatus = (status === currentFilter);
        }

        // 2. Search Text Check
        let matchesSearch = true;
        if (currentSearch !== '') {
            matchesSearch = name.includes(currentSearch) || make.includes(currentSearch) || office.includes(currentSearch);
        }

        if (matchesStatus && matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const emptyRow = document.getElementById('no-matching-row');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
    }
}

function applyThreshold(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('threshold', val);
    window.location.href = url.toString();
}

function applyOfficeFilter(officeId) {
    const url = new URL(window.location.href);
    if (officeId) {
        url.searchParams.set('office_id', officeId);
    } else {
        url.searchParams.delete('office_id');
    }
    window.location.href = url.toString();
}

// Initialize on page load: Apply 'alerts' filter by default if there are alerts, else show 'all'
document.addEventListener('DOMContentLoaded', function() {
    const totalAlerts = <?php echo (int)$totalAlerts; ?>;
    if (totalAlerts > 0) {
        setFilter('alerts');
    } else {
        setFilter('all');
    }
});
</script>
