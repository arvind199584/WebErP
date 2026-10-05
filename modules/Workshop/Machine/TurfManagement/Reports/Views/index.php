<!-- Include SheetJS for robust Excel exports -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
    .table-crosstab th, .table-crosstab td { white-space: nowrap; text-align: center; vertical-align: middle; }
    .table-crosstab th:first-child, .table-crosstab td:first-child { text-align: left; position: sticky; left: 0; background-color: #fff; z-index: 1; border-right: 2px solid #dee2e6;}
    .table-crosstab thead th:first-child { background-color: #f8f9fa; z-index: 2; }
    #notebook-entry-display { font-size: 1.2rem; }
</style>

<div class="container-fluid">
    <h2 class="mb-4">Workshop Management Standard Reports</h2>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="fuel-consumption-tab" data-bs-toggle="tab" data-bs-target="#fuel-consumption-pane" type="button" role="tab">Fuel Consumption Report</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="machine-log-tab" data-bs-toggle="tab" data-bs-target="#machine-log-pane" type="button" role="tab">Individual Machine Log</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notebook-entry-tab" data-bs-toggle="tab" data-bs-target="#notebook-entry-pane" type="button" role="tab">Notebook Entry</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit-pane" type="button" role="tab">Running Hours Audit</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="backfill-tab" data-bs-toggle="tab" data-bs-target="#backfill-pane" type="button" role="tab">Manipulate Readings</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-primary" id="monthwise-stats-tab" data-bs-toggle="tab" data-bs-target="#monthwise-stats-pane" type="button" role="tab">📊 Monthwise Analytics</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="reportTabsContent">

        <!-- ========================================== -->
        <!-- TAB 1: FUEL CONSUMPTION REPORT             -->
        <!-- ========================================== -->
        <div class="tab-pane fade show active" id="fuel-consumption-pane" role="tabpanel">
            <div class="card mb-4 border-success">
                <div class="card-body bg-light">
                    <form id="fuel-consumption-filter-form" class="row g-3 align-items-end">
                        <?php if ($userRole === 'superuser'): ?>
                            <div class="col-md-3">
                                <label for="fuel_filter_office_id" class="form-label">Office</label>
                                <select id="fuel_filter_office_id" name="office_id" class="form-select">
                                    <option value="<?php echo $currentUser['officeid']; ?>">My Office (Default)</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" id="fuel_filter_office_id" name="office_id" value="<?php echo $currentUser['officeid']; ?>">
                        <?php endif; ?>

                        <div class="col-md-2">
                            <label for="fuel_filter_from" class="form-label">From Date</label>
                            <input type="date" id="fuel_filter_from" name="from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-15 days')); ?>" required>
                        </div>

                        <div class="col-md-2">
                            <label for="fuel_filter_to" class="form-label">To Date</label>
                            <input type="date" id="fuel_filter_to" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label for="fuel_filter_type" class="form-label">Fuel Type</label>
                            <select id="fuel_filter_type" name="fuel_type" class="form-select">
                                <option value="all">All</option>
                                <option value="diesel">Diesel</option>
                                <option value="petrol">Petrol</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="button" class="btn btn-success w-100" id="generate-fuel-btn">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daily Fuel Consumption Cross-Tab Report -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daily Fuel Consumption Log (Litres)</h5>
                    <button class="btn btn-sm btn-light text-success" onclick="exportTableToExcel('fuel-crosstab-table', 'Fuel_Consumption_Report')">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                        <table class="table table-bordered table-hover mb-0 table-crosstab" id="fuel-crosstab-table">
                            <thead class="table-light" id="fuel-crosstab-head">
                                <tr><th>Machine Name</th><th class="bg-warning text-dark">Total Petrol</th><th class="bg-secondary text-white">Total Diesel</th></tr>
                            </thead>
                            <tbody id="fuel-crosstab-body">
                                <tr><td colspan="100%" class="text-center py-4 text-muted">Click Generate Report to load data.</td></tr>
                            </tbody>
                            <tfoot class="table-dark" id="fuel-crosstab-foot"></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: INDIVIDUAL MACHINE LOG              -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="machine-log-pane" role="tabpanel">
            <div class="card mb-4 border-info">
                <div class="card-body bg-light">
                    <form id="machine-log-form" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Machine</label>
                            <select id="single_machine_id" name="machine_id" class="form-select" required>
                                <option value="">-- Select Machine --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="single_filter_from" name="from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="single_filter_to" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Toggle for showing virtual dates vs only logged dates -->
                        <div class="col-md-2">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="show_only_fueled_days" name="show_only_fueled_days">
                                <label class="form-check-label text-muted small" for="show_only_fueled_days">
                                    Show Only Fueled Days
                                </label>
                            </div>
                        </div>

                        <div class="col-md-1">
                            <button type="submit" class="btn btn-info w-100" id="generate-single-btn">Get Log</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detailed Machine Log</h5>
                    <button class="btn btn-sm btn-light text-success" onclick="exportTableToExcel('single-machine-table', 'Individual_Machine_Log')">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="single-machine-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Running Hour Start<br><small class="text-muted">(Previous Day)</small></th>
                                    <th>Running Hour To<br><small class="text-muted">(Current Day)</small></th>
                                    <th>Today's Run<br><small class="text-muted">(To - Start)</small></th>
                                    <th>Fuel Used</th>
                                    <th>Operator Name</th>
                                </tr>
                            </thead>
                            <tbody id="single-machine-tbody">
                                <tr><td colspan="6" class="text-center py-4 text-muted">Select a machine to view its log.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2.5: NOTEBOOK ENTRY                    -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="notebook-entry-pane" role="tabpanel">
            <div class="card mb-4 border-secondary">
                <div class="card-body bg-light">
                    <form id="notebook-filter-form" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Machine</label>
                            <select id="notebook_machine_id" name="machine_id" class="form-select" required>
                                <option value="">-- Select Machine --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="notebook_filter_from" name="from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="notebook_filter_to" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-secondary w-100" id="generate-notebook-btn">Fetch Log</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="notebook-display-area" class="text-center" style="display: none;">
                <div class="d-flex justify-content-center align-items-center mb-3">
                    <button id="notebook-prev-btn" class="btn btn-outline-secondary">&larr; Previous Day</button>
                    <h4 id="notebook-date-display" class="mx-4 mb-0"></h4>
                    <button id="notebook-next-btn" class="btn btn-outline-secondary">Next Day &rarr;</button>
                </div>
                <p id="notebook-day-counter" class="text-muted"></p>
                <div id="notebook-entry-display" class="p-4 bg-light border rounded">
                    <p>Select a machine and date range, then click "Fetch Log".</p>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: RUNNING HOURS AUDIT                 -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="audit-pane" role="tabpanel">
            <div class="alert alert-warning">
                <strong>Running Hours Audit Tool</strong><br>
                This tool scans a machine's chronological log history for impossible drops or spikes in running hours.
                It strictly ignores days where the machine did not run (zeros). If it finds an anomaly that violates the rule <code>PREV <= CURR <= NEXT</code>, it will propose a mathematically sound midpoint average correction.
            </div>

            <div class="card mb-4 border-warning">
                <div class="card-body bg-light">
                    <form id="audit-form" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Machine (Tracking Hours Only)</label>
                            <select id="audit_machine_id" name="machine_id" class="form-select" required>
                                <option value="">-- Select Machine --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="audit_filter_from" name="from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-60 days')); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="audit_filter_to" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning w-100" id="run-audit-btn">Scan Anomalies</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm" id="audit-results-card" style="display:none;">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detected Anomalies</h5>
                    <span class="badge bg-light text-danger fs-6" id="anomaly-count-badge">0 Found</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 text-center align-middle" id="audit-results-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Error Type</th>
                                    <th>Previous Valid<br><small class="text-muted">Anchor</small></th>
                                    <th class="bg-danger text-white">Erroneous Reading</th>
                                    <th>Next Valid<br><small class="text-muted">Anchor</small></th>
                                    <th class="bg-success text-white">Proposed Fix<br><small>(Interpolation)</small></th>
                                    <th class="no-export">Action</th>
                                </tr>
                            </thead>
                            <tbody id="audit-results-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-end py-3">
                    <button class="btn btn-sm btn-light text-success float-start" onclick="exportTableToExcel('audit-results-table', 'Audit_Report')"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                    <p class="text-muted small mt-2 mb-0"><em>* Applying fixes will update the turf_consumption_log table directly. Please review carefully. Currently this is a diagnostic view only. The update function resides in the Log module.</em></p>
                </div>
            </div>

            <div id="no-anomalies-message" class="alert alert-success mt-4" style="display:none;">
                <h4 class="alert-heading">Machine History is Clean!</h4>
                <p>No mathematical anomalies (impossible drops or spikes) were found in the recorded running hours for this period.</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: AUTO-BACKFILL ENGINE                -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="backfill-pane" role="tabpanel">
            <div class="alert alert-primary">
                <strong>Auto-Backfill Engine</strong><br>
                This tool is specifically designed for "Workhorse" machines that run daily but are only logged when fueled.
                It finds the gaps between physical fuel entries and automatically generates mathematically randomized running hours
                for the missing calendar days, respecting the machine's scheduled Rest Days.
            </div>

            <div class="card mb-4 border-primary">
                <div class="card-body bg-light">
                    <form id="backfill-form" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Machine (Daily Run Configured)</label>
                            <select id="backfill_machine_id" name="machine_id" class="form-select" required>
                                <option value="">-- Select Machine --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Target Month</label>
                            <input type="month" id="backfill_month" name="month" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100" id="run-backfill-btn">Preview Backfill</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Backfill Preview Area -->
            <div class="card shadow-sm" id="backfill-preview-card" style="display:none;">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Backfill Preview</h5>
                    <div>
                        <span class="badge bg-primary fs-6 me-2" id="backfill-count-badge">0 Logs to Generate</span>
                        <button class="btn btn-sm btn-light text-success" onclick="exportTableToExcel('backfill-preview-table', 'Manipulated_Readings_Preview')">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 text-center align-middle" id="backfill-preview-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-primary">Calculated Hours</th>
                                    <th>Fuel (Will be Null)</th>
                                    <th>Entry Type</th>
                                </tr>
                            </thead>
                            <tbody id="backfill-preview-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-end py-3">
                    <button type="button" id="commit-backfill-btn" class="btn btn-success btn-lg px-4">Commit to Database</button>
                </div>
            </div>

            <div id="no-backfill-message" class="alert alert-info mt-4" style="display:none;">
                <h4 class="alert-heading">No Backfill Needed</h4>
                <p>This machine either has a complete log for this month, or there are not enough surrounding fuel anchors to safely interpolate the data.</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 6: MONTHWISE MACHINE ANALYTICS         -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="monthwise-stats-pane" role="tabpanel">
            <div class="card mb-4 border-primary">
                <div class="card-body bg-light">
                    <form id="monthwise-stats-filter-form" class="row g-3 align-items-end">
                        <?php if ($userRole === 'superuser'): ?>
                            <div class="col-md-4">
                                <label for="mstat_office_id" class="form-label">Office</label>
                                <select id="mstat_office_id" name="office_id" class="form-select">
                                    <option value="<?php echo $currentUser['officeid']; ?>">My Office (Default)</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" id="mstat_office_id" name="office_id" value="<?php echo $currentUser['officeid']; ?>">
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label for="mstat_machine_id" class="form-label font-weight-bold">Select Machine</label>
                            <select id="mstat_machine_id" name="machine_id" class="form-select" required>
                                <option value="">-- Select Machine --</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100" id="fetch-mstat-btn">View Stats</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Analytics Display Container -->
            <div id="mstat-display-container" style="display:none;">
                <!-- Header Banner / Card -->
                <div class="card shadow-sm mb-4 border-0 bg-primary text-white">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1" id="mstat-machine-name">Machine Name</h3>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark fs-6" id="mstat-tracking-badge">Run Hours Tracked</span>
                                <span class="badge bg-warning text-dark fs-6" id="mstat-daily-badge">Daily Configured</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-light text-primary font-weight-bold" onclick="exportTableToExcel('mstat-table', 'Monthwise_Machine_Analytics')">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mode Warning Alert -->
                <div id="mstat-mode-alert" class="alert alert-info border-0 shadow-sm mb-4" style="display:none;">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <strong id="mstat-mode-title">Mode Notice:</strong> <span id="mstat-mode-desc"></span>
                </div>

                <!-- Fancy KPI Stat Cards Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 bg-light-primary text-primary h-100" style="background-color: #eef2ff;">
                            <div class="card-body text-center py-4">
                                <h6 class="text-uppercase text-muted fw-bold mb-2">Total Lifetime Fuel Consumed</h6>
                                <h2 class="display-6 fw-bold mb-0 text-primary" id="mstat-kpi-fuel">0 Litres</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" id="mstat-kpi-hours-col">
                        <div class="card shadow-sm border-0 text-success h-100" style="background-color: #ecfdf5;">
                            <div class="card-body text-center py-4">
                                <h6 class="text-uppercase text-muted fw-bold mb-2">Total Net Run Hours</h6>
                                <h2 class="display-6 fw-bold mb-0 text-success" id="mstat-kpi-hours">0 Hrs</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 text-dark h-100" style="background-color: #f3f4f6;">
                            <div class="card-body text-center py-4">
                                <h6 class="text-uppercase text-muted fw-bold mb-2">Months Recorded</h6>
                                <h2 class="display-6 fw-bold mb-0 text-dark" id="mstat-kpi-months">0 Months</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Monthwise Performance Breakdown</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 text-center align-middle" id="mstat-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-4">Month</th>
                                        <th class="text-success">Fuel Consumed</th>
                                        <th class="hours-col text-primary">Monthly Net Run Hours</th>
                                        <th class="hours-col">Start Reading</th>
                                        <th class="hours-col">End Reading</th>
                                        <th>Days Logged</th>
                                    </tr>
                                </thead>
                                <tbody id="mstat-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End Tab Content -->
</div>

<script>
// --- Excel Export Helper using SheetJS ---
function exportTableToExcel(tableID, filename = ''){
    var tableSelect = document.getElementById(tableID);
    if (!tableSelect) return;

    // Clone the table so we can strip out non-exportable buttons before saving
    var clone = tableSelect.cloneNode(true);

    // Remove elements designated as non-exportable (like Action column headers)
    var noExportElements = clone.querySelectorAll('.no-export');
    for (var i = 0; i < noExportElements.length; i++) {
        noExportElements[i].parentNode.removeChild(noExportElements[i]);
    }

    // Remove any action buttons inside rows
    var actionBtns = clone.querySelectorAll('button');
    for (var j = 0; j < actionBtns.length; j++) {
        actionBtns[j].parentNode.removeChild(actionBtns[j]);
    }

    // Remove all <small> tags to get rid of petrol/diesel details on the next line (and header sub-labels)
    var smalls = clone.querySelectorAll('small');
    for (var k = 0; k < smalls.length; k++) {
        smalls[k].parentNode.removeChild(smalls[k]);
    }

    // Remove all <br> tags to prevent line breaks within cells in the Excel sheet
    var brs = clone.querySelectorAll('br');
    for (var l = 0; l < brs.length; l++) {
        brs[l].parentNode.removeChild(brs[l]);
    }

    if (typeof XLSX === 'undefined') {
        alert('Excel export library is not loaded properly.');
        return;
    }

    var wb = XLSX.utils.table_to_book(clone, {sheet:"Report"});
    XLSX.writeFile(wb, filename + ".xlsx");
}

document.addEventListener('DOMContentLoaded', function() {
    const allMachines = <?php echo json_encode($machines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
    const userRole = "<?php echo $userRole; ?>";
    const fuelOfficeSelect = document.getElementById('fuel_filter_office_id');

    // Format Date helper - UPDATED TO SHOW ONLY DAY (DD)
    const formatDateShort = (dateString) => {
        const d = new Date(dateString);
        return String(d.getDate()).padStart(2, '0');
    };

    const mstatOfficeSelect = document.getElementById('mstat_office_id');

    // Populate Machine Dropdowns based on currently selected Master Office
    const populateMachineDropdowns = async (changedSelectElement = null) => {
        let selectedOfficeId = "";
        if (changedSelectElement && changedSelectElement.value) {
            selectedOfficeId = changedSelectElement.value;
        } else if (mstatOfficeSelect && mstatOfficeSelect.value) {
            selectedOfficeId = mstatOfficeSelect.value;
        } else if (fuelOfficeSelect && fuelOfficeSelect.value) {
            selectedOfficeId = fuelOfficeSelect.value;
        } else {
            selectedOfficeId = "<?php echo (string)$currentUser['officeid']; ?>";
        }

        // Sync both office selects if one changed
        if (changedSelectElement) {
            if (fuelOfficeSelect && fuelOfficeSelect !== changedSelectElement) fuelOfficeSelect.value = selectedOfficeId;
            if (mstatOfficeSelect && mstatOfficeSelect !== changedSelectElement) mstatOfficeSelect.value = selectedOfficeId;
        }

        const singleSelect = document.getElementById('single_machine_id');
        const auditSelect = document.getElementById('audit_machine_id');
        const backfillSelect = document.getElementById('backfill_machine_id');
        const notebookSelect = document.getElementById('notebook_machine_id');
        const mstatSelect = document.getElementById('mstat_machine_id');

        if (singleSelect) singleSelect.innerHTML = '<option value="">-- Select Machine --</option>';
        if (auditSelect) auditSelect.innerHTML = '<option value="">-- Select Machine --</option>';
        if (backfillSelect) backfillSelect.innerHTML = '<option value="">-- Select Machine --</option>';
        if (notebookSelect) notebookSelect.innerHTML = '<option value="">-- Select Machine --</option>';
        if (mstatSelect) mstatSelect.innerHTML = '<option value="">-- Select Machine --</option>';

        let targetMachines = [];

        // Check if allMachines has records for this office
        if (Array.isArray(allMachines) && allMachines.length > 0) {
            targetMachines = allMachines.filter(m => {
                const machOfficeId = m.officeid ?? m.officeId ?? m.Officeid;
                return machOfficeId === undefined || machOfficeId === null || String(machOfficeId) === String(selectedOfficeId);
            });
        }

        // If no machines were found in the preloaded PHP array for this office (e.g., superuser changed office dropdown), fetch dynamically via AJAX
        if (targetMachines.length === 0 && selectedOfficeId) {
            try {
                const res = await fetch(`/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=getOfficeMachines&office_id=${selectedOfficeId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json.status === 'success' && Array.isArray(json.data)) {
                        targetMachines = json.data;
                    }
                }
            } catch (e) {
                console.error("Failed to fetch office machines:", e);
            }
        }

        // If user is superuser and no office is specifically filtered, or if we still have no office filter, show all target machines
        if (targetMachines.length === 0 && userRole === 'superuser' && (!selectedOfficeId || selectedOfficeId === '0')) {
            targetMachines = allMachines;
        }

        targetMachines.forEach(machine => {
            const option = new Option(machine.name, machine.id);
            if (singleSelect) singleSelect.add(option.cloneNode(true));
            if (notebookSelect) notebookSelect.add(option.cloneNode(true));
            if (mstatSelect) mstatSelect.add(option.cloneNode(true));

            const isRunDuration = (machine.runduration == 1 || machine.runduration === 't' || machine.runduration === true || machine.runduration === 'true' || machine.runduration === '1');
            const isDailyRun = (machine.daily_run == 1 || machine.daily_run === 't' || machine.daily_run === true || machine.daily_run === 'true' || machine.daily_run === '1');

            if (isRunDuration && auditSelect) {
                auditSelect.add(option.cloneNode(true));
            }
            if (isRunDuration && isDailyRun && backfillSelect) {
                backfillSelect.add(option.cloneNode(true));
            }
        });
    };

    if (fuelOfficeSelect) fuelOfficeSelect.addEventListener('change', function() { populateMachineDropdowns(this); });
    if (mstatOfficeSelect) mstatOfficeSelect.addEventListener('change', function() { populateMachineDropdowns(this); });
    populateMachineDropdowns();

    // Auto-select Machine and Date inputs from URL search parameters if passed
    const urlParams = new URLSearchParams(window.location.search);
    const paramMachineId = urlParams.get('machine_id');
    const paramFrom = urlParams.get('from');
    const paramTo = urlParams.get('to');

    if (paramFrom) {
        document.querySelectorAll('input[type="date"][name="from"]').forEach(input => input.value = paramFrom);
    }
    if (paramTo) {
        document.querySelectorAll('input[type="date"][name="to"]').forEach(input => input.value = paramTo);
    }
    if (paramMachineId) {
        ['single_machine_id', 'audit_machine_id', 'backfill_machine_id', 'notebook_machine_id', 'mstat_machine_id'].forEach(id => {
            const select = document.getElementById(id);
            if (select) select.value = paramMachineId;
        });

        // Automatically trigger "Individual Machine Log" fetch if machine_id is provided in URL
        const machineLogTabBtn = document.getElementById('machine-log-tab');
        if (machineLogTabBtn) {
            if (window.bootstrap && bootstrap.Tab) {
                const tab = new bootstrap.Tab(machineLogTabBtn);
                tab.show();
            } else {
                machineLogTabBtn.click();
            }
            const genSingleBtn = document.getElementById('generate-single-btn');
            if (genSingleBtn) genSingleBtn.click();
        }
    }

    // --- TAB 1: FUEL CONSUMPTION REPORT ---
    const fetchAndRenderFuelData = async () => {
        const btn = document.getElementById('generate-fuel-btn');
        btn.innerText = 'Loading...';
        btn.disabled = true;

        const form = document.getElementById('fuel-consumption-filter-form');
        const params = new URLSearchParams(new FormData(form)).toString();

        try {
            const response = await fetch(`?action=getChartData&${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            const data = result.data;

            // CrossTab
            const crosstab = data.crosstab_report;
            const crossHead = document.getElementById('fuel-crosstab-head');
            const crossBody = document.getElementById('fuel-crosstab-body');
            const crossFoot = document.getElementById('fuel-crosstab-foot');

            let headHtml = `<tr><th>Machine Name</th>`;
            crosstab.dates.forEach(date => headHtml += `<th>${formatDateShort(date)}</th>`);
            headHtml += `<th class="bg-warning text-dark">Total Petrol</th><th class="bg-secondary text-white">Total Diesel</th></tr>`;
            crossHead.innerHTML = headHtml;

            if (Object.keys(crosstab.rows).length === 0) {
                crossBody.innerHTML = `<tr><td colspan="${crosstab.dates.length + 3}" class="text-center py-4">No fuel consumption logged in this period.</td></tr>`;
                crossFoot.innerHTML = '';
            } else {
                let bodyHtml = '';
                for (const [machineName, machineData] of Object.entries(crosstab.rows)) {
                    let fType = machineData.fuel_type || '';
                    bodyHtml += `<tr><td class="fw-bold">${machineName} <br><small class="text-muted">${fType}</small></td>`;
                    crosstab.dates.forEach(date => bodyHtml += `<td>${machineData.dates[date] > 0 ? machineData.dates[date] : '-'}</td>`);
                    const isPetrol = fType.toLowerCase().includes('petrol');
                    const isDiesel = fType.toLowerCase().includes('diesel');
                    bodyHtml += `<td class="fw-bold bg-light">${isPetrol ? machineData.total : '-'}</td>`;
                    bodyHtml += `<td class="fw-bold bg-light">${isDiesel ? machineData.total : '-'}</td></tr>`;
                }
                crossBody.innerHTML = bodyHtml;

                // Add Daily Totals for Petrol and Diesel
                let dailyTotalHtml = '';
                if (data.crosstab_report.daily_fuel_type_totals) {
                    const daily = data.crosstab_report.daily_fuel_type_totals;
                    
                    // Petrol Row
                    dailyTotalHtml += `<tr class="table-warning"><td class="fw-bold">DAILY TOTAL PETROL</td>`;
                    crosstab.dates.forEach(date => dailyTotalHtml += `<td class="fw-bold">${daily.petrol[date] > 0 ? daily.petrol[date].toFixed(2) : '-'}</td>`);
                    
                    let grandPetrol = 0;
                    if (crosstab.grand_totals) {
                        for (const [ft, tot] of Object.entries(crosstab.grand_totals)) {
                            if (ft.toLowerCase().includes('petrol')) grandPetrol += tot;
                        }
                    }
                    dailyTotalHtml += `<td class="fw-bold">${grandPetrol > 0 ? grandPetrol.toFixed(2) : '-'}</td><td>-</td></tr>`;

                    // Diesel Row
                    dailyTotalHtml += `<tr class="table-secondary"><td class="fw-bold">DAILY TOTAL DIESEL</td>`;
                    crosstab.dates.forEach(date => dailyTotalHtml += `<td class="fw-bold">${daily.diesel[date] > 0 ? daily.diesel[date].toFixed(2) : '-'}</td>`);
                    
                    let grandDiesel = 0;
                    if (crosstab.grand_totals) {
                        for (const [ft, tot] of Object.entries(crosstab.grand_totals)) {
                            if (ft.toLowerCase().includes('diesel')) grandDiesel += tot;
                        }
                    }
                    dailyTotalHtml += `<td>-</td><td class="fw-bold">${grandDiesel > 0 ? grandDiesel.toFixed(2) : '-'}</td></tr>`;
                }
                crossBody.innerHTML += dailyTotalHtml;

                let grandTotalPetrol = 0, grandTotalDiesel = 0;
                if(crosstab.grand_totals) {
                    for (const [ft, tot] of Object.entries(crosstab.grand_totals)) {
                        if (ft.toLowerCase().includes('petrol')) grandTotalPetrol += tot;
                        if (ft.toLowerCase().includes('diesel')) grandTotalDiesel += tot;
                    }
                }
                let footHtml = `<tr><th class="text-end">GRAND TOTALS:</th>`;
                crosstab.dates.forEach(() => footHtml += `<th></th>`);
                footHtml += `<th class="text-warning fs-5">${grandTotalPetrol > 0 ? grandTotalPetrol.toFixed(2) : '-'}</th><th class="text-white fs-5">${grandTotalDiesel > 0 ? grandTotalDiesel.toFixed(2) : '-'}</th></tr>`;
                crossFoot.innerHTML = footHtml;
            }
        } catch (error) {
            alert('Failed to load report data: ' + error.message);
        } finally {
            btn.innerText = 'Generate Report';
            btn.disabled = false;
        }
    };

    document.getElementById('generate-fuel-btn').addEventListener('click', fetchAndRenderFuelData);


    // --- TAB 2: SINGLE MACHINE LOG ---
    document.getElementById('generate-single-btn').addEventListener('click', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('generate-single-btn');
        btn.innerText = 'Loading...';
        btn.disabled = true;

        const form = document.getElementById('machine-log-form');
        let params = new URLSearchParams(new FormData(form)).toString();
        if(fuelOfficeSelect) params += `&office_id=${fuelOfficeSelect.value}`;

        // Get the value of the toggle
        const showOnlyFueled = document.getElementById('show_only_fueled_days').checked;
        const displayMode = showOnlyFueled ? 'logged' : 'auto';
        params += `&display_mode=${displayMode}`;

        const tbody = document.getElementById('single-machine-tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border spinner-border-sm text-info"></div> Loading...</td></tr>';

        try {
            const response = await fetch(`?action=getMachineLogData&${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            let logData = result.data;

            // Apply the filter locally if the toggle is checked
            if (showOnlyFueled) {
                logData = logData.filter(row => row.fuel_consumed_qty !== '-' && row.fuel_consumed_qty !== null && row.fuel_consumed_qty !== "");
            }

            if(logData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No log entries found for this machine in the selected period.</td></tr>';
            } else {
                tbody.innerHTML = '';
                logData.forEach(row => {
                    // Calculate Today's Run
                    let todaysRun = '-';
                    if (row.running_hours_to && row.running_hours_start) {
                        const run = parseFloat(row.running_hours_to) - parseFloat(row.running_hours_start);
                        if (!isNaN(run) && run >= 0) {
                            todaysRun = run.toFixed(2);
                        }
                    }

                    // Style virtual rows slightly differently
                    const rowStyle = row.is_virtual ? 'class="table-light text-muted"' : '';

                    tbody.innerHTML += `
                        <tr ${rowStyle}>
                            <td class="fw-bold">${formatDateShort(row.log_date)}</td>
                            <td>${row.running_hours_start || '-'}</td>
                            <td>${row.running_hours_to || '-'}</td>
                            <td class="fw-bold text-success">${todaysRun}</td>
                            <td>${row.fuel_consumed_qty || '-'}</td>
                            <td>${row.operator || '-'}</td>
                        </tr>
                    `;
                });
            }
        } catch(err) {
            alert(err.message);
            tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Error loading data.</td></tr>';
        } finally {
            btn.innerText = 'Get Log';
            btn.disabled = false;
        }
    });

    // --- TAB 2.5: NOTEBOOK ENTRY ---
    let notebookLogData = [];
    let currentNotebookIndex = 0;

    const notebookDisplayArea = document.getElementById('notebook-display-area');
    const notebookDateDisplay = document.getElementById('notebook-date-display');
    const notebookDayCounter = document.getElementById('notebook-day-counter');
    const notebookEntryDisplay = document.getElementById('notebook-entry-display');
    const notebookPrevBtn = document.getElementById('notebook-prev-btn');
    const notebookNextBtn = document.getElementById('notebook-next-btn');

    const renderNotebookEntry = () => {
        if (notebookLogData.length === 0) {
            notebookEntryDisplay.innerHTML = '<p class="text-danger">No log data found for the selected criteria.</p>';
            notebookDisplayArea.style.display = 'block';
            notebookDateDisplay.textContent = '';
            notebookDayCounter.textContent = '';
            return;
        }

        const entry = notebookLogData[currentNotebookIndex];
        notebookDateDisplay.textContent = formatDateShort(entry.log_date);
        notebookDayCounter.textContent = `Showing Day ${currentNotebookIndex + 1} of ${notebookLogData.length}`;

        let todaysRun = '-';
        if (entry.running_hours_to && entry.running_hours_start) {
            const run = parseFloat(entry.running_hours_to) - parseFloat(entry.running_hours_start);
            if (!isNaN(run) && run >= 0) {
                todaysRun = run.toFixed(2);
            }
        }

        notebookEntryDisplay.innerHTML = `
            <p><strong>Start Hours:</strong> ${entry.running_hours_start || 'N/A'}</p>
            <p><strong>End Hours:</strong> ${entry.running_hours_to || 'N/A'}</p>
            <p><strong>Today's Run:</strong> ${todaysRun}</p>
            <p><strong>Fuel Used:</strong> ${entry.fuel_consumed_qty || 'N/A'}</p>
            <p><strong>Operator:</strong> ${entry.operator || 'N/A'}</p>
        `;

        notebookPrevBtn.disabled = currentNotebookIndex === 0;
        notebookNextBtn.disabled = currentNotebookIndex === notebookLogData.length - 1;
    };

    document.getElementById('generate-notebook-btn').addEventListener('click', async function() {
        const btn = this;
        btn.innerText = 'Fetching...';
        btn.disabled = true;

        const form = document.getElementById('notebook-filter-form');
        let params = new URLSearchParams(new FormData(form)).toString();
        if(fuelOfficeSelect) params += `&office_id=${fuelOfficeSelect.value}`;
        params += '&display_mode=all'; // Always get all days for notebook

        try {
            const response = await fetch(`?action=getMachineLogData&${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            notebookLogData = result.data;
            currentNotebookIndex = 0;
            notebookDisplayArea.style.display = 'block';
            renderNotebookEntry();

        } catch (err) {
            alert(err.message);
        } finally {
            btn.innerText = 'Fetch Log';
            btn.disabled = false;
        }
    });

    notebookPrevBtn.addEventListener('click', () => {
        if (currentNotebookIndex > 0) {
            currentNotebookIndex--;
            renderNotebookEntry();
        }
    });

    notebookNextBtn.addEventListener('click', () => {
        if (currentNotebookIndex < notebookLogData.length - 1) {
            currentNotebookIndex++;
            renderNotebookEntry();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (notebookDisplayArea.style.display === 'block') {
            if (e.key === 'ArrowLeft') {
                notebookPrevBtn.click();
            } else if (e.key === 'ArrowRight') {
                notebookNextBtn.click();
            }
        }
    });


    // --- TAB 3: AUDIT ---
    document.getElementById('run-audit-btn').addEventListener('click', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('run-audit-btn');
        btn.innerText = 'Scanning...';
        btn.disabled = true;

        const form = document.getElementById('audit-form');
        let params = new URLSearchParams(new FormData(form)).toString();
        if(fuelOfficeSelect) params += `&office_id=${fuelOfficeSelect.value}`;

        const resultsCard = document.getElementById('audit-results-card');
        const noMsg = document.getElementById('no-anomalies-message');
        const tbody = document.getElementById('audit-results-tbody');

        resultsCard.style.display = 'none';
        noMsg.style.display = 'none';

        try {
            const response = await fetch(`?action=getMachineAuditData&${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const anomalies = result.data;
            if(anomalies.length === 0) {
                noMsg.style.display = 'block';
            } else {
                tbody.innerHTML = '';
                document.getElementById('anomaly-count-badge').innerText = `${anomalies.length} Found`;
                anomalies.forEach(a => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="fw-bold">${formatDateShort(a.log_date)}</td>
                            <td class="text-danger fw-bold">${a.type}</td>
                            <td class="text-muted">${a.prev_val}</td>
                            <td class="bg-danger text-white fw-bold fs-5">${a.curr_val}</td>
                            <td class="text-muted">${a.next_val}</td>
                            <td class="bg-success text-white fw-bold fs-5">${a.proposed_val}</td>
                            <td class="no-export">
                                <button type="button" class="btn btn-sm btn-outline-success apply-single-fix-btn" data-id="${a.id}" data-val="${a.proposed_val}">Accept</button>
                            </td>
                        </tr>
                    `;
                });
                resultsCard.style.display = 'block';
            }
        } catch(err) {
            alert(err.message);
        } finally {
            btn.innerText = 'Scan Anomalies';
            btn.disabled = false;
        }
    });

    // Handle single row "Accept" fix
    // Make sure we unbind old listeners if any (using event delegation here is fine)
    const auditTbody = document.getElementById('audit-results-tbody');
    // Remove previous listener to prevent double firing if script re-runs (rare but good practice)
    const newAuditTbody = auditTbody.cloneNode(true);
    auditTbody.parentNode.replaceChild(newAuditTbody, auditTbody);

    newAuditTbody.addEventListener('click', async function(e) {
        if (e.target.classList.contains('apply-single-fix-btn')) {
            const btn = e.target;
            const id = btn.getAttribute('data-id');
            const newVal = btn.getAttribute('data-val');

            // ENABLED THE CONFIRM DIALOG AGAIN
            if (!confirm(`Apply the new value of ${newVal} to this record?`)) return;

            btn.innerText = 'Applying...';
            btn.disabled = true;

            try {
                // The correct endpoint in LogController is applyAuditFixes
                const response = await fetch('/modules/Workshop/Machine/TurfManagement/Logs/Controller/LogController.php?action=applyAuditFixes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>", fixes: [{ id: id, new_hours: newVal }] })
                });

                const result = await response.json();

                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.error || result.message || "Failed to update database.");
                }

                // Show success briefly
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success');
                btn.innerText = 'Applied!';

                // Re-run scan automatically after a short delay to update UI and fetch next set of anomalies
                setTimeout(() => {
                    document.getElementById('run-audit-btn').click();
                }, 800);

            } catch (err) {
                alert('Error applying fix: ' + err.message);
                btn.innerText = 'Accept';
                btn.disabled = false;
            }
        }
    });

    // --- TAB 4: BACKFILL ENGINE ---
    let currentBackfillData = []; // Store it so we can push it to the server

    document.getElementById('backfill-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('run-backfill-btn');
        btn.innerText = 'Calculating...';
        btn.disabled = true;

        const form = document.getElementById('backfill-form');
        let params = new URLSearchParams(new FormData(form)).toString();
        if(fuelOfficeSelect) params += `&office_id=${fuelOfficeSelect.value}`;

        const resultsCard = document.getElementById('backfill-preview-card');
        const noMsg = document.getElementById('no-backfill-message');
        const tbody = document.getElementById('backfill-preview-tbody');
        const commitBtn = document.getElementById('commit-backfill-btn');

        resultsCard.style.display = 'none';
        noMsg.style.display = 'none';

        try {
            const response = await fetch(`?action=calculateBackfill&${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            currentBackfillData = result.data;
            if(currentBackfillData.length === 0) {
                noMsg.style.display = 'block';
            } else {
                tbody.innerHTML = '';
                document.getElementById('backfill-count-badge').innerText = `${currentBackfillData.length} Logs to Generate`;

                currentBackfillData.forEach(row => {
                    const rowClass = row.type.includes('Rest Day') ? 'table-warning text-dark' : 'table-light';
                    tbody.innerHTML += `
                        <tr class="${rowClass}">
                            <td class="fw-bold">${formatDateShort(row.log_date)}</td>
                            <td>${row.type}</td>
                            <td class="text-primary fw-bold">${row.running_hours}</td>
                            <td class="text-muted">NULL</td>
                            <td><span class="badge bg-secondary">AUTO</span></td>
                        </tr>
                    `;
                });
                resultsCard.style.display = 'block';
                commitBtn.style.display = 'inline-block';
            }
        } catch(err) {
            alert(err.message);
            tbody.innerHTML = '<tr><td colspan="5" class="text-danger text-center">Error loading data.</td></tr>';
        } finally {
            btn.innerText = 'Preview Data';
            btn.disabled = false;
        }
    });

    document.getElementById('commit-backfill-btn').addEventListener('click', async function() {
        if (currentBackfillData.length === 0) return;

        if (!confirm(`Are you sure you want to permanently insert ${currentBackfillData.length} auto-generated records into the database?`)) {
            return;
        }

        const btn = this;
        const originalText = btn.innerText;
        btn.innerText = 'Saving to Database...';
        btn.disabled = true;

        const machineId = document.getElementById('backfill_machine_id').value;
        let officeId = fuelOfficeSelect ? fuelOfficeSelect.value : "<?php echo $currentUser['officeid']; ?>";

        // Prepare payload format that LogController expects
        const logsPayload = currentBackfillData.map(row => ({
            machine_id: machineId,
            log_date: row.log_date,
            running_hours: row.running_hours,
            fuel_consumed_qty: null,
            operator: 'AUTO-GENERATED' // This gets inserted instead of an empty string
        }));

        try {
            // We use the existing LogController createLogs endpoint, which now uses a bulk transaction
            const response = await fetch('/modules/Workshop/Machine/TurfManagement/Logs/Controller/LogController.php?action=createLogs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>", logs: logsPayload, office_id: officeId })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || "Failed to save backfill data.");
            }

            alert(result.message);
            // Re-run the preview (which should now return empty since the days are filled)
            document.getElementById('run-backfill-btn').click();

        } catch (err) {
            alert('Error saving data: ' + err.message);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    });

    // --- TAB 6: MONTHWISE MACHINE ANALYTICS LOGIC ---
    // (Note: mstatOfficeSelect is already declared at top of DOMContentLoaded)
    const mstatMachineSelect = document.getElementById('mstat_machine_id');

    document.getElementById('monthwise-stats-filter-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const machineId = mstatMachineSelect.value;
        if (!machineId) {
            alert('Please select a machine.');
            return;
        }

        const btn = document.getElementById('fetch-mstat-btn');
        btn.innerText = 'Loading...';
        btn.disabled = true;

        const officeId = mstatOfficeSelect ? mstatOfficeSelect.value : '';
        const url = `/modules/Workshop/Machine/TurfManagement/Reports/Controller/ReportController.php?action=getMonthwiseMachineStats&machine_id=${machineId}${officeId ? '&office_id=' + officeId : ''}`;

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || 'Failed to fetch analytics.');
            }

            const data = result.data;
            const machine = data.machine;
            const stats = data.stats || [];

            const container = document.getElementById('mstat-display-container');
            container.style.display = 'block';

            // Set Header Details
            document.getElementById('mstat-machine-name').innerText = machine.name || 'Machine Analytics';

            const isHoursTracked = (machine.runduration == 1 || machine.runduration === 't' || machine.runduration === true || machine.runduration === 'true');
            const isDailyConfigured = (machine.daily_run == 1 || machine.daily_run === 't' || machine.daily_run === true || machine.daily_run === 'true');

            const trackingBadge = document.getElementById('mstat-tracking-badge');
            trackingBadge.innerText = isHoursTracked ? '⏱️ Run Hours Tracked' : '⛽ Fuel Only (No Hour Meter)';
            trackingBadge.className = isHoursTracked ? 'badge bg-light text-dark fs-6' : 'badge bg-secondary text-white fs-6';

            const dailyBadge = document.getElementById('mstat-daily-badge');
            dailyBadge.innerText = isDailyConfigured ? '📅 Daily Run Configured' : '📋 On-Demand Run';

            // Show/Hide Alert for Fuel-Only Machines
            const modeAlert = document.getElementById('mstat-mode-alert');
            const modeTitle = document.getElementById('mstat-mode-title');
            const modeDesc = document.getElementById('mstat-mode-desc');
            const hoursKpiCol = document.getElementById('mstat-kpi-hours-col');
            const hourColsInTable = document.querySelectorAll('.hours-col');

            if (!isHoursTracked) {
                modeAlert.style.display = 'block';
                modeAlert.className = 'alert alert-warning border-0 shadow-sm mb-4';
                modeTitle.innerText = 'Fuel-Only Machine Notice:';
                modeDesc.innerText = 'This machine does not have a running hour meter configured. Only monthly fuel consumption is displayed.';

                // Hide Hours KPI Card & Table Hours Columns
                hoursKpiCol.style.display = 'none';
                hourColsInTable.forEach(el => el.style.display = 'none');
            } else {
                modeAlert.style.display = 'block';
                modeAlert.className = 'alert alert-info border-0 shadow-sm mb-4';
                modeTitle.innerText = 'Hourly Meter Configured:';
                modeDesc.innerText = 'Monthly Net Run Hours are calculated dynamically by subtracting the starting hour meter reading (MIN) from the ending reading (MAX) for each month.';

                hoursKpiCol.style.display = 'block';
                hourColsInTable.forEach(el => el.style.display = '');
            }

            // Calculate Totals for KPIs
            let totalFuel = 0;
            let totalNetHours = 0;

            const tbody = document.getElementById('mstat-tbody');
            tbody.innerHTML = '';

            if (stats.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-4 text-muted">No monthly log records found for this machine.</td></tr>`;
            } else {
                stats.forEach(row => {
                    const fuel = parseFloat(row.total_fuel) || 0;
                    const netHours = parseFloat(row.net_run_hours) || 0;
                    const startHours = parseFloat(row.start_hours) || 0;
                    const endHours = parseFloat(row.end_hours) || 0;

                    totalFuel += fuel;
                    if (isHoursTracked && netHours > 0) {
                        totalNetHours += netHours;
                    }

                    const tr = document.createElement('tr');
                    let rowHtml = `
                        <td class="text-start ps-4 fw-bold text-dark">${row.month_display || row.month_key}</td>
                        <td class="text-success font-weight-bold">${fuel > 0 ? fuel.toFixed(2) + ' L' : '<span class="text-muted">-</span>'}</td>
                    `;

                    if (isHoursTracked) {
                        rowHtml += `
                            <td class="text-primary font-weight-bold">${netHours > 0 ? netHours.toFixed(2) + ' Hrs' : '<span class="text-muted">-</span>'}</td>
                            <td>${startHours > 0 ? startHours.toFixed(1) : '<span class="text-muted">-</span>'}</td>
                            <td>${endHours > 0 ? endHours.toFixed(1) : '<span class="text-muted">-</span>'}</td>
                        `;
                    }

                    rowHtml += `<td><span class="badge bg-secondary">${row.days_logged} Days</span></td>`;

                    tr.innerHTML = rowHtml;
                    tbody.appendChild(tr);
                });
            }

            // Update KPI Cards
            document.getElementById('mstat-kpi-fuel').innerText = totalFuel.toFixed(2) + ' L';
            document.getElementById('mstat-kpi-hours').innerText = totalNetHours.toFixed(2) + ' Hrs';
            document.getElementById('mstat-kpi-months').innerText = stats.length + ' Months';

        } catch (err) {
            alert('Error fetching analytics: ' + err.message);
        } finally {
            btn.innerText = 'View Stats';
            btn.disabled = false;
        }
    });

    // Initial load
    fetchAndRenderFuelData();
});
</script>