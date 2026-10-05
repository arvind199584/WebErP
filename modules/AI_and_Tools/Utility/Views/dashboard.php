<?php
/**
 * Dynamic ERP Dashboard
 * Aligned with Enterprise System Modules:
 * - Administration
 * - Human Resource
 * - Workshop & Machinery
 * - Store & Inventory
 * - Finance & Revenue
 * - AI & Productivity Tools
 * - Superadmin Management
 *
 * @var bool $hasAdmin
 * @var bool $hasHr
 * @var bool $hasWorkshop
 * @var bool $hasStore
 * @var bool $hasFinance
 * @var bool $hasTools
 * @var bool $hasSuperuser
 * @var string $role
 * @var array|null $currentUser
 */
$userName = $currentUser['username'] ?? 'User';
$officeId = $currentUser['officeid'] ?? 1;
?>
<div class="container-fluid py-2">
    <!-- Welcome Header Banner -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-3 border-bottom">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-grid-1x2-fill text-primary me-2"></i>Enterprise ERP Dashboard
            </h1>
            <p class="text-muted mb-0 small">
                Welcome, <strong><?= htmlspecialchars($userName) ?></strong> &bull; Office ID: <strong><?= (int)$officeId ?></strong> &bull; Integrated Operations & Governance System
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 py-2 px-3">
                <i class="bi bi-shield-check me-1"></i> Role: <?= strtoupper(htmlspecialchars($role)) ?>
            </span>
            <span class="badge bg-light text-secondary border fs-6 py-2 px-3">
                <i class="bi bi-clock-history me-1"></i> <?= date('D, d M Y') ?>
            </span>
        </div>
    </div>

    <!-- Alert / Fast Action Banner: Machine Service Status -->
    <?php if ($hasWorkshop): ?>
    <div class="card bg-warning-subtle border-warning shadow-sm mb-4">
        <div class="card-body py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-warning text-dark p-2 rounded-circle shadow-sm flex-shrink-0">
                    <i class="bi bi-speedometer2 fs-4"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Machinery Maintenance & Service Tracker</h6>
                    <small class="text-muted">Proactively monitor engine hours, overdue servicing, and parts requirements for all machines.</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=status" class="btn btn-warning text-dark fw-bold btn-sm px-3 shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> View Machine Status
                </a>
                <a href="/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=list" class="btn btn-outline-dark btn-sm px-3">
                    <i class="bi bi-truck me-1"></i> Machine Fleet
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Module Department Cards Grid -->
    <div class="row g-4 mb-4">

        <!-- 1. Workshop & Machinery -->
        <?php if ($hasWorkshop): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-warning shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-warning text-uppercase">Workshop Operations</span>
                        <div class="bg-warning-subtle text-warning p-2 rounded-circle">
                            <i class="bi bi-tools fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">Workshop & Machinery</h5>
                    <p class="text-muted small mb-3">Fleet catalog, live service status alerts, daily meter run logs, servicing intervals, repair job cards, and fuel consumption.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=list" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-truck me-1"></i> Fleet List
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=status" class="btn btn-sm btn-warning text-dark fw-bold">
                            <i class="bi bi-speedometer2 me-1"></i> Machine Status
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Logs/Controller/LogController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-journal-text me-1"></i> Run Logs
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Servicing/Controller/ServicingController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-wrench-adjustable me-1"></i> Servicing
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Repairs/Controller/RepairController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-wrench me-1"></i> Repairs
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Reports/Controller/ReportController.php?action=index" class="btn btn-sm btn-light border">
                            <i class="bi bi-bar-chart-line me-1"></i> Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. Store & Inventory -->
        <?php if ($hasStore): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-info shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-info text-uppercase">Store & Inventory</span>
                        <div class="bg-info-subtle text-info p-2 rounded-circle">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">Store Management</h5>
                    <p class="text-muted small mb-3">Inventory catalog, spare parts stock, engine oils, fuels & lubricants, inward receipts, and outward indents.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/Workshop/Machine/TurfManagement/InventoryItems/Controller/InventoryItemController.php?action=list" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-boxes me-1"></i> Store Inventory
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Receipts/Controller/ReceiptController.php?action=list" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Material Receipts
                        </a>
                        <a href="/modules/Workshop/Machine/TurfManagement/Indents/Controller/IndentController.php?action=list" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Material Indents
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. Human Resource -->
        <?php if ($hasHr): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-success shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-success text-uppercase">Human Resource</span>
                        <div class="bg-success-subtle text-success p-2 rounded-circle">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">Staff & Labor Operations</h5>
                    <p class="text-muted small mb-3">Employee master directory, daily attendance & absentee sheets, monthly wages processing, EPF & ESIC statutory statements.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/HumanResource/Employee/Controller/EmployeeController.php?action=list" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-person-lines-fill me-1"></i> Employee Master
                        </a>
                        <a href="/modules/HumanResource/Absentee/Controller/AttendanceController.php?action=index" class="btn btn-sm btn-success">
                            <i class="bi bi-calendar-check me-1"></i> Attendance
                        </a>
                        <a href="/modules/HumanResource/Wages/Controller/WagesController.php?action=index" class="btn btn-sm btn-light border">
                            <i class="bi bi-cash-coin me-1"></i> Wages
                        </a>
                        <a href="/modules/HumanResource/EPF/Controller/EpfController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-shield-shaded me-1"></i> EPF
                        </a>
                        <a href="/modules/HumanResource/ESIC/Controller/EsicController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-heart-pulse me-1"></i> ESIC
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 4. Administration & Contracts -->
        <?php if ($hasAdmin): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-primary shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-primary text-uppercase">Administration</span>
                        <div class="bg-primary-subtle text-primary p-2 rounded-circle">
                            <i class="bi bi-briefcase fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">Contracts & Administration</h5>
                    <p class="text-muted small mb-3">Agreements registry, Administrative Approvals & Sanctions (AA & ES), Work Orders, Supply Orders, and Contractor Bill Payments.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/Admin/Agreement/Controller/AgreementController.php?action=list" class="btn btn-sm btn-primary">
                            <i class="bi bi-file-earmark-ruled me-1"></i> Agreements
                        </a>
                        <a href="/modules/Admin/AA_ES/Controller/AA_ESController.php?action=list" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-award me-1"></i> AA & ES
                        </a>
                        <a href="/modules/Admin/WorkOrder/Controller/WorkOrderController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-briefcase me-1"></i> Work Orders
                        </a>
                        <a href="/modules/Admin/SupplyOrder/Controller/SupplyOrderController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-cart-check me-1"></i> Supply Orders
                        </a>
                        <a href="/modules/Admin/Bills/Payment/Controller/PaymentController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-receipt-cutoff me-1"></i> Bills
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 5. Finance & Accounts -->
        <?php if ($hasFinance): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-danger shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-danger text-uppercase">Finance & Accounts</span>
                        <div class="bg-danger-subtle text-danger p-2 rounded-circle">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">Finance & Revenue</h5>
                    <p class="text-muted small mb-3">Annual budget heads, expenditure tracking, agreement opening balances, and diverse revenue counters (members, booking, sports).</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/Finance/Budget/Controller/BudgetController.php?action=list" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-pie-chart me-1"></i> Budget
                        </a>
                        <a href="/modules/Finance/Expenditure/OpeningBalance/Controller/OpeningBalanceController.php" class="btn btn-sm btn-danger">
                            <i class="bi bi-cash-stack me-1"></i> Opening Balance
                        </a>
                        <a href="/modules/Finance/Revenue/Revenue/TemporaryMember/Controller/TMController.php?action=list" class="btn btn-sm btn-light border">
                            <i class="bi bi-person-badge me-1"></i> Temp Members
                        </a>
                        <a href="/modules/Finance/Revenue/Income/Membership/Controller/MembershipController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-person-check me-1"></i> Membership
                        </a>
                        <a href="/modules/Finance/Revenue/Income/GroundBooking/Controller/GroundBookingController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-calendar-event me-1"></i> Booking
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 6. AI & Productivity Tools -->
        <?php if ($hasTools): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-secondary shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-secondary text-uppercase">AI & Utilities</span>
                        <div class="bg-secondary-subtle text-secondary p-2 rounded-circle">
                            <i class="bi bi-cpu fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">AI & Master Utilities</h5>
                    <p class="text-muted small mb-3">AI assistance & automated data queries, official government letter generation, master report printing, and SQL analytics.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/AI_and_Tools/AIML/Controller/AIMLController.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-robot me-1"></i> AI Assistant
                        </a>
                        <a href="/modules/AI_and_Tools/AIDrafter/Controller/AIDrafterController.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-file-earmark-text me-1"></i> AI Letter Drafter
                        </a>
                        <a href="/modules/AI_and_Tools/ReportPrinting/Controller/ReportPrintingController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-printer me-1"></i> Report Printing
                        </a>
                        <a href="/modules/AI_and_Tools/Utility/Controller/SQLRunnerController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-terminal me-1"></i> SQL Runner
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 7. Superadmin (Visible to superuser only) -->
        <?php if ($hasSuperuser): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-dark shadow-sm h-100 py-1 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-dark text-uppercase">Superadmin</span>
                        <div class="bg-dark text-white p-2 rounded-circle">
                            <i class="bi bi-shield-lock fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-gray-800 mb-1">System Governance</h5>
                    <p class="text-muted small mb-3">Offices setup, user account authentication & RBAC permissions, contractor agencies, wage rates master, and system activity logs.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/modules/Superadmin/Office/Controller/OfficeController.php?action=list" class="btn btn-sm btn-dark">
                            <i class="bi bi-buildings me-1"></i> Offices
                        </a>
                        <a href="/modules/Superadmin/Users/Controller/UserController.php" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-people me-1"></i> Users & Security
                        </a>
                        <a href="/modules/Superadmin/Agency/Controller/AgencyController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-building me-1"></i> Agencies
                        </a>
                        <a href="/modules/Superadmin/WagesRates/Controller/WageRateController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-currency-rupee me-1"></i> Wage Rates
                        </a>
                        <a href="/modules/Superadmin/ActivityLog/Controller/ActivityLogController.php" class="btn btn-sm btn-light border">
                            <i class="bi bi-activity me-1"></i> Activity Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Secondary Row: Architecture Guide & Quick Access Launchpad -->
    <div class="row g-4">
        <!-- Integrated System Architecture Guide -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-diagram-3-fill me-2"></i>Integrated Module Workflows
                    </h6>
                    <span class="badge bg-light text-dark border">Data Flow Overview</span>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">Enterprise ERP synchronizes operations across 6 core departmental modules:</p>
                    
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-tools text-warning fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-dark">Workshop:</strong> 
                                Equipment registry, real-time maintenance status alerts (overdue/due soon), daily meter runs, servicing schedules with mandatory Engine Oil & spares calculation, and repair job cards.
                            </div>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-box-seam text-info fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-dark">Store:</strong> 
                                Centralized inventory items, fuels, lubricants, compatible machinery spares catalog, inward material receipts, and outward department indents.
                            </div>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-people text-success fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-dark">Human Resource:</strong> 
                                Employee directory, biometric & manual attendance/absentee tracking, monthly wages calculations with statutory EPF and ESIC deduction sheets.
                            </div>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-briefcase text-primary fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-dark">Administration:</strong> 
                                Administrative Approvals & Expenditure Sanctions (AA & ES), contractor agreements, work orders, supply orders, running bill payments, and hand receipts.
                            </div>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-cash-stack text-danger fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-dark">Finance:</strong> 
                                Budget master allocations, agreement opening balance ledgers, and revenue collections across sports, memberships, bookings, and visitors.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Access Launchpad -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Quick Launchpad
                    </h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Shortcuts</span>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <!-- Machine Status Live Alert -->
                        <a href="/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=status" class="btn btn-outline-warning text-dark d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="fw-semibold"><i class="bi bi-speedometer2 text-warning me-2 fs-5"></i> Machine Service Status & Due Alerts</span>
                            <span class="badge bg-warning text-dark">&rarr;</span>
                        </a>

                        <!-- AI Assistant -->
                        <a href="/modules/AI_and_Tools/AIML/Controller/AIMLController.php" class="btn btn-outline-primary d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="fw-semibold"><i class="bi bi-robot text-primary me-2 fs-5"></i> AI Natural Language Query</span>
                            <span>&rarr;</span>
                        </a>

                        <!-- AI Letter Drafter -->
                        <a href="/modules/AI_and_Tools/AIDrafter/Controller/AIDrafterController.php" class="btn btn-outline-primary d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="fw-semibold"><i class="bi bi-file-earmark-text text-primary me-2 fs-5"></i> AI Official Letter Drafter</span>
                            <span>&rarr;</span>
                        </a>

                        <!-- Agreement Opening Balance -->
                        <a href="/modules/Finance/Expenditure/OpeningBalance/Controller/OpeningBalanceController.php" class="btn btn-outline-danger d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="fw-semibold"><i class="bi bi-cash-stack text-danger me-2 fs-5"></i> Expenditure Opening Balances</span>
                            <span>&rarr;</span>
                        </a>

                        <!-- Budget Tracking Dashboard -->
                        <a href="/modules/AI_and_Tools/Utility/Controller/UtilityController.php?action=budget_tracking" class="btn btn-outline-secondary d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="fw-semibold"><i class="bi bi-graph-up text-secondary me-2 fs-5"></i> Budget Tracking Analytics</span>
                            <span>&rarr;</span>
                        </a>

                        <!-- Master Report Printing -->
                        <a href="/modules/AI_and_Tools/ReportPrinting/Controller/ReportPrintingController.php" class="btn btn-outline-secondary d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="fw-semibold"><i class="bi bi-printer text-secondary me-2 fs-5"></i> Master Report Printing Center</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-shadow:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
</style>
