<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop & Fleet Operations Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .kpi-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .nav-pills .nav-link {
            border-radius: 8px;
            font-weight: 600;
            padding: 8px 18px;
            color: #495057;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.35);
        }
        .table-balance-row:hover {
            background-color: #f8f9fa;
        }
        .badge-balance-positive {
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .badge-balance-zero {
            background-color: #e2e3e5;
            color: #41464b;
            font-weight: 600;
        }
        .badge-balance-negative {
            background-color: #f8d7da;
            color: #842029;
            font-weight: 700;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">

    <!-- Top Action & Title Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary fs-6 px-3 py-2 fw-bold"><i class="bi bi-speedometer2 me-1"></i> DASHBOARD</span>
                <h2 class="h3 fw-bold text-dark mb-0">Workshop & Fleet Command Center</h2>
            </div>
            <small class="text-muted">Live Stock Balances (Fuel, Chemicals & Spares) &bull; As-Of-Date History &bull; Machine Fleet Maintenance</small>
        </div>

        <!-- As-of-Date Datepicker & Quick Presets -->
        <div class="d-flex flex-wrap align-items-center gap-2 bg-white p-2 rounded-3 shadow-sm border">
            <span class="small fw-bold text-muted ps-2"><i class="bi bi-calendar-check text-primary me-1"></i> As-of Date:</span>
            <input type="date" id="as_of_date_picker" class="form-control form-control-sm border-primary fw-bold" style="width: 145px;" value="<?php echo htmlspecialchars($as_of_date); ?>">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="setDatePreset('today')" title="Today's Current Date">Today</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setDatePreset('2026-06-30')" title="Legacy Balance Date">30 June 2026</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setDatePreset('end_last_month')" title="End of Previous Month">End of Last Mo.</button>
            </div>
            <button type="button" class="btn btn-sm btn-primary fw-bold" id="btn-refresh-balances" onclick="refreshDashboardBalances()">
                <i class="bi bi-arrow-clockwise"></i> Apply
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Working Machines -->
        <div class="col-md-2 col-sm-6">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Working Fleet</small>
                        <h3 class="fw-bold text-success mb-0"><?php echo (int)($fleet['working_count'] ?? 0); ?></h3>
                    </div>
                    <div class="bg-success-subtle p-2 rounded-circle text-success fs-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Total: <strong><?php echo (int)($fleet['total_machines'] ?? 0); ?></strong> machines
                </div>
            </div>
        </div>

        <!-- 2. Off-Road Fleet -->
        <div class="col-md-2 col-sm-6">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Off-Road Fleet</small>
                        <h3 class="fw-bold text-danger mb-0"><?php echo (int)($fleet['off_road_count'] ?? 0); ?></h3>
                    </div>
                    <div class="bg-danger-subtle p-2 rounded-circle text-danger fs-4">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                </div>
                <div class="mt-2 small">
                    <a href="#offroad-section" class="text-danger text-decoration-none fw-semibold">View breakdowns &rarr;</a>
                </div>
            </div>
        </div>

        <!-- 3. Service Due -->
        <div class="col-md-2 col-sm-6">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Service Due</small>
                        <h3 class="fw-bold text-warning mb-0"><?php echo (int)($fleet['service_due_count'] ?? 0); ?></h3>
                    </div>
                    <div class="bg-warning-subtle p-2 rounded-circle text-warning fs-4">
                        <i class="bi bi-wrench-adjustable"></i>
                    </div>
                </div>
                <div class="mt-2 small">
                    <a href="#servicedue-section" class="text-warning text-decoration-none fw-semibold">Needs service &rarr;</a>
                </div>
            </div>
        </div>

        <!-- 4. Petrol Balance -->
        <div class="col-md-2 col-sm-6">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Petrol Balance</small>
                        <h3 class="fw-bold text-primary mb-0" id="card-petrol-bal"><?php echo number_format($headline_fuels['petrol']['balance'] ?? 0, 1); ?></h3>
                    </div>
                    <div class="bg-primary-subtle p-2 rounded-circle text-primary fs-4">
                        <i class="bi bi-fuel-pump"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Litre as of <span class="badge bg-light text-dark border as-of-badge"><?php echo htmlspecialchars($as_of_date); ?></span>
                </div>
            </div>
        </div>

        <!-- 5. Diesel Balance -->
        <div class="col-md-2 col-sm-6">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Diesel Balance</small>
                        <h3 class="fw-bold text-dark mb-0" id="card-diesel-bal"><?php echo number_format($headline_fuels['diesel']['balance'] ?? 0, 1); ?></h3>
                    </div>
                    <div class="bg-info-subtle p-2 rounded-circle text-info fs-4">
                        <i class="bi bi-truck-front"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Litre as of <span class="badge bg-light text-dark border as-of-badge"><?php echo htmlspecialchars($as_of_date); ?></span>
                </div>
            </div>
        </div>

        <!-- 6. 2T Oil Balance -->
        <div class="col-md-2 col-sm-6">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">2T Oil Balance</small>
                        <h3 class="fw-bold text-dark mb-0" id="card-twot-bal"><?php echo number_format($headline_fuels['two_t']['balance'] ?? 0, 1); ?></h3>
                    </div>
                    <div class="bg-secondary-subtle p-2 rounded-circle text-secondary fs-4">
                        <i class="bi bi-droplet"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Litre as of <span class="badge bg-light text-dark border as-of-badge"><?php echo htmlspecialchars($as_of_date); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN SECTION: Material & Stock Balance Inspector -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-box-seam text-primary me-2"></i>Stock Balance Inspector
                    </h5>
                    <span class="badge bg-light text-primary border">As of <span class="as-of-badge"><?php echo htmlspecialchars($as_of_date); ?></span></span>
                </div>

                <!-- Tabs: Fuel, Chemicals, Spares -->
                <ul class="nav nav-pills" id="balanceTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" onclick="switchCategory('fuel')">
                            <i class="bi bi-fuel-pump me-1"></i> Fuel & Lubricants
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" onclick="switchCategory('chemical')">
                            <i class="bi bi-flower1 me-1"></i> Chemicals & Fertilizers
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" onclick="switchCategory('spares')">
                            <i class="bi bi-gear-wide-connected me-1"></i> Spare Parts Inventory
                        </button>
                    </li>
                </ul>

                <!-- Search Input -->
                <div class="input-group input-group-sm" style="width: 240px;">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" id="table_search_input" class="form-control" placeholder="Search items..." oninput="filterTableRows()">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="balances-table">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr id="table-headers">
                            <th style="width: 45px;">#</th>
                            <th>Item Description / Nomenclature</th>
                            <th>Category</th>
                            <th style="width: 100px;">Unit</th>
                            <th class="text-end" style="width: 150px;">Total Inward (RVs)</th>
                            <th class="text-end" style="width: 150px;">Total Outward</th>
                            <th class="text-end" style="width: 170px;">Balance as of Date</th>
                        </tr>
                    </thead>
                    <tbody id="balances-tbody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light py-2 text-muted small d-flex justify-content-between align-items-center">
            <span id="items-count-label">Loading items...</span>
            <span class="text-muted"><i class="bi bi-info-circle me-1"></i> Inward reflects all receipts up to selected date; outward reflects machine consumptions & issues.</span>
        </div>
    </div>

    <!-- LOWER GRID: Machine Fleet Health Breakdown -->
    <div class="row g-4">
        <!-- 1. Service Due Machines -->
        <div class="col-lg-6" id="servicedue-section">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">
                            <i class="bi bi-wrench-adjustable text-warning me-2"></i>Machines Due for Service
                        </h5>
                        <small class="text-muted">Machines within 20 running hours or overdue for scheduled service</small>
                    </div>
                    <span class="badge bg-warning text-dark fs-6"><?php echo count($fleet['service_due_machines'] ?? []); ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Machine</th>
                                    <th>Make</th>
                                    <th class="text-end">Run Hours</th>
                                    <th class="text-end">Service Due</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($fleet['service_due_machines'])): ?>
                                    <?php foreach ($fleet['service_due_machines'] as $sm): ?>
                                        <?php 
                                            $hrsLeft = (float)($sm['hours_to_service'] ?? 0);
                                            $isOverdue = $hrsLeft <= 0;
                                        ?>
                                        <tr>
                                            <td class="fw-bold small"><?php echo htmlspecialchars($sm['name']); ?></td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($sm['make'] ?? '-'); ?></td>
                                            <td class="small text-end fw-semibold"><?php echo number_format((float)$sm['total_hours_run'], 1); ?> h</td>
                                            <td class="small text-end"><?php echo number_format((float)$sm['service_due'], 1); ?> h</td>
                                            <td>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger small">Overdue (<?php echo abs($hrsLeft); ?>h)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark small"><?php echo $hrsLeft; ?>h left</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="/modules/Workshop/Machine/TurfManagement/Servicing/Controller/ServicingController.php?action=showCreateForm&machine_id=<?php echo urlencode($sm['id']); ?>" 
                                                   class="btn btn-sm btn-outline-warning text-dark fw-bold" title="Log Service">
                                                    <i class="bi bi-wrench"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                            All machines are within service limits!
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Off-Road / Breakdown Machines -->
        <div class="col-lg-6" id="offroad-section">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">
                            <i class="bi bi-exclamation-octagon text-danger me-2"></i>Off-Road & Breakdown Fleet
                        </h5>
                        <small class="text-muted">Machines currently out of operation needing repairs</small>
                    </div>
                    <span class="badge bg-danger fs-6"><?php echo count($fleet['off_road_machines'] ?? []); ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Machine</th>
                                    <th>Make</th>
                                    <th>Office / Location</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($fleet['off_road_machines'])): ?>
                                    <?php foreach ($fleet['off_road_machines'] as $om): ?>
                                        <tr>
                                            <td class="fw-bold small text-danger">
                                                <i class="bi bi-x-circle me-1"></i><?php echo htmlspecialchars($om['name']); ?>
                                            </td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($om['make'] ?? '-'); ?></td>
                                            <td class="small"><?php echo htmlspecialchars($om['office_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-danger small"><?php echo htmlspecialchars($om['status']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="/modules/Workshop/Machine/TurfManagement/Repairs/Controller/RepairController.php?action=list&machine_id=<?php echo urlencode($om['id']); ?>" 
                                                   class="btn btn-sm btn-outline-danger fw-bold" title="View Repairs / Job Cards">
                                                    <i class="bi bi-tools"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                            Zero off-road machines! Full fleet is active and working.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentCategory = 'fuel';
    let currentAsOfDate = "<?php echo htmlspecialchars($as_of_date); ?>";
    let cachedItemsData = [];

    document.addEventListener('DOMContentLoaded', function() {
        loadBalanceData(currentCategory, currentAsOfDate);
    });

    function setDatePreset(preset) {
        const picker = document.getElementById('as_of_date_picker');
        if (preset === 'today') {
            const today = new Date().toISOString().split('T')[0];
            picker.value = today;
        } else if (preset === '2026-06-30') {
            picker.value = '2026-06-30';
        } else if (preset === 'end_last_month') {
            const d = new Date();
            d.setDate(1);
            d.setHours(-1);
            picker.value = d.toISOString().split('T')[0];
        }
        refreshDashboardBalances();
    }

    function refreshDashboardBalances() {
        const newDate = document.getElementById('as_of_date_picker').value;
        if (!newDate) return;
        currentAsOfDate = newDate;

        document.querySelectorAll('.as-of-badge').forEach(el => el.textContent = currentAsOfDate);
        loadBalanceData(currentCategory, currentAsOfDate);
    }

    function switchCategory(cat) {
        currentCategory = cat;
        document.querySelectorAll('#balanceTabs .nav-link').forEach(btn => btn.classList.remove('active'));
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
        loadBalanceData(currentCategory, currentAsOfDate);
    }

    function loadBalanceData(cat, asOfDate) {
        const tbody = document.getElementById('balances-tbody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Loading ${cat.toUpperCase()} balances as of ${asOfDate}...</td></tr>`;
        document.getElementById('items-count-label').textContent = 'Loading...';

        fetch(`?action=getBalanceData&category=${encodeURIComponent(cat)}&as_of_date=${encodeURIComponent(asOfDate)}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    cachedItemsData = res.data || [];
                    renderBalanceTable(cat, cachedItemsData);

                    // Update Headline Cards if fuel
                    if (cat === 'fuel') {
                        cachedItemsData.forEach(item => {
                            const desc = (item.description || '').toLowerCase();
                            const bal = parseFloat(item.balance_qty || 0);
                            if (item.id == 1 || desc.includes('petrol')) {
                                document.getElementById('card-petrol-bal').textContent = bal.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2});
                            } else if (item.id == 2 || desc.includes('diesel')) {
                                document.getElementById('card-diesel-bal').textContent = bal.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2});
                            } else if (item.id == 86 || desc.includes('2t')) {
                                document.getElementById('card-twot-bal').textContent = bal.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2});
                            }
                        });
                    }
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${res.message || 'Error fetching balances'}</td></tr>`;
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Failed to load data: ${err.message}</td></tr>`;
            });
    }

    function renderBalanceTable(cat, items) {
        const tbody = document.getElementById('balances-tbody');
        const headers = document.getElementById('table-headers');
        tbody.innerHTML = '';

        if (cat === 'spares') {
            headers.innerHTML = `
                <th style="width: 45px;">#</th>
                <th>Part Number</th>
                <th>Nomenclature / Description</th>
                <th>Machine Compatibility</th>
                <th class="text-end" style="width: 130px;">Received</th>
                <th class="text-end" style="width: 130px;">Issued</th>
                <th class="text-end" style="width: 160px;">Current Stock</th>
            `;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No spare parts found.</td></tr>`;
                document.getElementById('items-count-label').textContent = '0 spare parts';
                return;
            }

            items.forEach((it, idx) => {
                const stock = parseFloat(it.current_stock || 0);
                const unit = it.unit || 'Pcs';
                let badgeClass = 'badge-balance-positive';
                if (stock === 0) badgeClass = 'badge-balance-zero';
                else if (stock < 0) badgeClass = 'badge-balance-negative';

                const tr = document.createElement('tr');
                tr.className = 'table-balance-row';
                tr.innerHTML = `
                    <td class="text-muted small">${idx + 1}</td>
                    <td class="fw-bold text-primary font-monospace">${escapeHtml(it.part_no || '-')}</td>
                    <td class="fw-semibold">${escapeHtml(it.nomenclature || '-')}</td>
                    <td class="small text-muted">${escapeHtml(it.machine_name || it.group_name || 'General')}</td>
                    <td class="text-end small">${parseFloat(it.received_qty || 0).toLocaleString()}</td>
                    <td class="text-end small">${parseFloat(it.issued_qty || 0).toLocaleString()}</td>
                    <td class="text-end">
                        <span class="badge ${badgeClass} px-3 py-1">
                            ${stock.toLocaleString()} ${escapeHtml(unit)}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('items-count-label').textContent = `Showing ${items.length} spare part(s)`;
            return;
        }

        // Fuel & Chemicals Table
        headers.innerHTML = `
            <th style="width: 45px;">#</th>
            <th>Item Description</th>
            <th>Category</th>
            <th style="width: 100px;">Unit</th>
            <th class="text-end" style="width: 150px;">Total Inward (RVs)</th>
            <th class="text-end" style="width: 150px;">Total Outward</th>
            <th class="text-end" style="width: 170px;">Balance as of Date</th>
        `;

        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No items found for this category.</td></tr>`;
            document.getElementById('items-count-label').textContent = '0 items';
            return;
        }

        items.forEach((it, idx) => {
            const inw = parseFloat(it.total_inward || 0);
            const out = parseFloat(it.total_outward || 0);
            const bal = parseFloat(it.balance_qty || 0);
            const unit = it.ac_unit || 'Unit';

            let badgeClass = 'badge-balance-positive';
            if (bal === 0) badgeClass = 'badge-balance-zero';
            else if (bal < 0) badgeClass = 'badge-balance-negative';

            const tr = document.createElement('tr');
            tr.className = 'table-balance-row';
            tr.innerHTML = `
                <td class="text-muted small">${idx + 1}</td>
                <td class="fw-bold text-dark">${escapeHtml(it.description || '')}</td>
                <td><span class="badge bg-light text-secondary border text-uppercase small">${escapeHtml(it.category_name || '-')}</span></td>
                <td class="small text-muted">${escapeHtml(unit)}</td>
                <td class="text-end small fw-semibold text-secondary">${inw.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2})}</td>
                <td class="text-end small fw-semibold text-secondary">${out.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2})}</td>
                <td class="text-end">
                    <span class="badge ${badgeClass} px-3 py-2">
                        ${bal.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2})} ${escapeHtml(unit)}
                    </span>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('items-count-label').textContent = `Showing ${items.length} item(s) as of ${currentAsOfDate}`;
    }

    function filterTableRows() {
        const query = (document.getElementById('table_search_input').value || '').trim().toLowerCase();
        const rows = document.querySelectorAll('#balances-tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
</body>
</html>
