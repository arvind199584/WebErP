<?php
// core/Layout.php

$currentUser = $currentUser ?? ($user ?? ((isset($this) && property_exists($this, 'currentUser')) ? $this->currentUser : null));
$role = strtolower(trim((string)($currentUser['role'] ?? 'guest')));
$permissions = $currentUser['permissions'] ?? [];

// Helper function to check if a user has access to a specific system/module
if (!function_exists('hasAccess')) {
    function hasAccess($role, $permissions, $systemName) {
        $role = strtolower(trim((string)$role));
        if ($role === 'superuser') {
            return true;
        }
        if (!empty($permissions) && is_array($permissions) && in_array($systemName, $permissions)) {
            return true;
        }
        // Managers and Admins have operational access across standard modules
        if (in_array($role, ['manager', 'admin'])) {
            return true;
        }
        // General staff and users have operational access to standard workflows
        if (in_array($role, ['staff', 'user', 'operator'])) {
            return in_array($systemName, ['HumanResource', 'Employee', 'Store', 'TurfManagement', 'Workshop', 'Finance', 'AI_and_Tools', 'Admin']);
        }
        return false;
    }
}

// Check access for different functional areas
$hasSuperuser = ($role === 'superuser');
$hasAdmin     = $hasSuperuser || hasAccess($role, $permissions, 'Admin');
$hasHr        = $hasSuperuser || hasAccess($role, $permissions, 'HumanResource') || hasAccess($role, $permissions, 'Employee');
$hasWorkshop  = $hasSuperuser || hasAccess($role, $permissions, 'TurfManagement') || hasAccess($role, $permissions, 'Workshop');
$hasStore     = $hasSuperuser || hasAccess($role, $permissions, 'Store');
$hasFinance   = $hasSuperuser || hasAccess($role, $permissions, 'Finance');
$hasTools     = $hasSuperuser || hasAccess($role, $permissions, 'AI_and_Tools');

$menu = [];

// 1. Administration (Agreements, AA & ES, Work Orders, Supply Orders, Bills)
if ($hasAdmin) {
    $menu['Administration'] = [
        ['name' => 'Agreements Registry', 'url' => '/modules/Admin/Agreement/Controller/AgreementController.php?action=list', 'icon' => 'bi-file-earmark-ruled'],
        ['name' => 'AA & ES Sanctions', 'url' => '/modules/Admin/AA_ES/Controller/AA_ESController.php?action=list', 'icon' => 'bi-award'],
        ['name' => 'Work Orders', 'url' => '/modules/Admin/WorkOrder/Controller/WorkOrderController.php?action=list', 'icon' => 'bi-briefcase'],
        ['name' => 'Supply Orders', 'url' => '/modules/Admin/SupplyOrder/Controller/SupplyOrderController.php?action=list', 'icon' => 'bi-cart-check'],
        ['name' => 'Bill Payments', 'url' => '/modules/Admin/Bills/Payment/Controller/PaymentController.php?action=list', 'icon' => 'bi-receipt-cutoff'],
        ['name' => 'Hand Receipts', 'url' => '/modules/Admin/Bills/HandReciept/Controller/HandRecieptController.php?action=list', 'icon' => 'bi-journal-check'],
    ];
}

// 2. Human Resource (Employee Directory, Attendance, Wages, EPF, ESIC)
if ($hasHr) {
    $menu['Human Resource'] = [
        ['name' => 'Employee Master', 'url' => '/modules/HumanResource/Employee/Controller/EmployeeController.php?action=list', 'icon' => 'bi-person-lines-fill'],
        ['name' => 'Attendance & Absentee', 'url' => '/modules/HumanResource/Absentee/Controller/AttendanceController.php?action=index', 'icon' => 'bi-calendar-check'],
        ['name' => 'Wages Processing', 'url' => '/modules/HumanResource/Wages/Controller/WagesController.php?action=index', 'icon' => 'bi-cash-coin'],
        ['name' => 'EPF Statements', 'url' => '/modules/HumanResource/EPF/Controller/EpfController.php?action=list', 'icon' => 'bi-shield-shaded'],
        ['name' => 'ESIC Statements', 'url' => '/modules/HumanResource/ESIC/Controller/EsicController.php?action=list', 'icon' => 'bi-heart-pulse'],
    ];
}

// 3. Workshop & Machinery
if ($hasWorkshop) {
    $menu['Workshop'] = [
        ['name' => 'Machine Fleet', 'url' => '/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=list', 'icon' => 'bi-truck'],
        ['name' => 'Machine Status', 'url' => '/modules/Workshop/Machine/TurfManagement/Machines/Controller/MachineController.php?action=status', 'icon' => 'bi-speedometer2'],
        ['name' => 'Daily Run Logs', 'url' => '/modules/Workshop/Machine/TurfManagement/Logs/Controller/LogController.php?action=list', 'icon' => 'bi-journal-text'],
        ['name' => 'Machine Service Logs', 'url' => '/modules/Workshop/Machine/TurfManagement/Servicing/Controller/ServicingController.php?action=list', 'icon' => 'bi-wrench-adjustable'],
        ['name' => 'Machine Repair Work', 'url' => '/modules/Workshop/Machine/TurfManagement/Repairs/Controller/RepairController.php?action=list', 'icon' => 'bi-tools'],
        ['name' => 'Job Cards Register', 'url' => '/modules/Workshop/Machine/TurfManagement/Repairs/Controller/RepairController.php?action=jobCards', 'icon' => 'bi-card-checklist'],
        ['name' => 'Water Logs', 'url' => '/modules/Workshop/Machine/TurfManagement/WaterLog/Controller/WaterLogController.php?action=list', 'icon' => 'bi-droplet-half'],
        ['name' => 'Workshop Report', 'url' => '/modules/Workshop/Machine/TurfManagement/Reports/Controller/ReportController.php?action=index', 'icon' => 'bi-bar-chart-line'],
    ];
}

// 4. Store & Inventory
if ($hasStore) {
    $menu['Store'] = [
        ['name' => 'Store Inventory', 'url' => '/modules/Workshop/Machine/TurfManagement/InventoryItems/Controller/InventoryItemController.php?action=list', 'icon' => 'bi-box-seam'],
        ['name' => 'Material Receipts', 'url' => '/modules/Workshop/Machine/TurfManagement/Receipts/Controller/ReceiptController.php?action=list', 'icon' => 'bi-box-arrow-in-down'],
        ['name' => 'Material Indents', 'url' => '/modules/Workshop/Machine/TurfManagement/Indents/Controller/IndentController.php?action=list', 'icon' => 'bi-box-arrow-up-right'],
    ];
}

// 5. Finance & Accounts
if ($hasFinance) {
    $menu['Finance'] = [
        ['name' => 'Budget Management', 'url' => '/modules/Finance/Budget/Controller/BudgetController.php?action=list', 'icon' => 'bi-pie-chart'],
        ['name' => 'Expenditure & Opening Balance', 'url' => '/modules/Finance/Expenditure/OpeningBalance/Controller/OpeningBalanceController.php', 'icon' => 'bi-cash-stack'],
        ['name' => 'Temporary Members', 'url' => '/modules/Finance/Revenue/Revenue/TemporaryMember/Controller/TMController.php?action=list', 'icon' => 'bi-person-badge'],
        ['name' => 'Membership Revenue', 'url' => '/modules/Finance/Revenue/Income/Membership/Controller/MembershipController.php', 'icon' => 'bi-person-check'],
        ['name' => 'Ground Booking', 'url' => '/modules/Finance/Revenue/Income/GroundBooking/Controller/GroundBookingController.php', 'icon' => 'bi-calendar-event'],
        ['name' => 'Pay & Play', 'url' => '/modules/Finance/Revenue/Income/PaynPlay/Controller/PaynPlayController.php', 'icon' => 'bi-controller'],
        ['name' => 'Swimming Pool', 'url' => '/modules/Finance/Revenue/Income/SwimmingPool/Controller/SwimmingPoolController.php', 'icon' => 'bi-water'],
        ['name' => 'Coaching Revenue', 'url' => '/modules/Finance/Revenue/Income/Coaching/Controller/CoachingController.php', 'icon' => 'bi-mortarboard'],
        ['name' => 'Daily Visitors', 'url' => '/modules/Finance/Revenue/Income/Visitor/Controller/VisitorController.php', 'icon' => 'bi-ticket-perforated'],
    ];
}

// 6. AI & Productivity Tools
if ($hasTools) {
    $menu['AI & Tools'] = [
        ['name' => 'AI Assistant & ML', 'url' => '/modules/AI_and_Tools/AIML/Controller/AIMLController.php', 'icon' => 'bi-robot'],
        ['name' => 'AI Letter Drafter', 'url' => '/modules/AI_and_Tools/AIDrafter/Controller/AIDrafterController.php', 'icon' => 'bi-file-earmark-text'],
        ['name' => 'Report Printing Center', 'url' => '/modules/AI_and_Tools/ReportPrinting/Controller/ReportPrintingController.php', 'icon' => 'bi-printer'],
        ['name' => 'SQL Runner & Query', 'url' => '/modules/AI_and_Tools/Utility/Controller/SQLRunnerController.php', 'icon' => 'bi-terminal'],
        ['name' => 'Budget Analytics', 'url' => '/modules/AI_and_Tools/Utility/Controller/UtilityController.php?action=budget_tracking', 'icon' => 'bi-graph-up'],
    ];
}

// 7. Superadmin (Only visible to superuser)
if ($hasSuperuser) {
    $menu['Superadmin'] = [
        ['name' => 'Office Management', 'url' => '/modules/Superadmin/Office/Controller/OfficeController.php?action=list', 'icon' => 'bi-buildings'],
        ['name' => 'User Security', 'url' => '/modules/Superadmin/Users/Controller/UserController.php', 'icon' => 'bi-shield-lock'],
        ['name' => 'Noticeboard Announcements', 'url' => '/modules/Superadmin/Noticeboard/Controller/NoticeboardController.php?action=list', 'icon' => 'bi-megaphone'],
        ['name' => 'Agency Management', 'url' => '/modules/Superadmin/Agency/Controller/AgencyController.php', 'icon' => 'bi-briefcase'],
        ['name' => 'Wage Rates Master', 'url' => '/modules/Superadmin/WagesRates/Controller/WageRateController.php', 'icon' => 'bi-currency-rupee'],
        ['name' => 'Wage Items Master', 'url' => '/modules/Superadmin/WagesRates/Controller/WageItemController.php', 'icon' => 'bi-list-check'],
        ['name' => 'Activity Logs', 'url' => '/modules/Superadmin/ActivityLog/Controller/ActivityLogController.php', 'icon' => 'bi-activity'],
        ['name' => 'Address Book', 'url' => '/modules/Superadmin/AddressBook/Controller/AddressBookController.php', 'icon' => 'bi-journal-bookmark'],
    ];
}

// Category Icons Mapping
$categoryIcons = [
    'Administration' => 'bi-briefcase-fill text-primary',
    'Human Resource' => 'bi-people-fill text-success',
    'Workshop'       => 'bi-tools text-warning',
    'Store'          => 'bi-box-seam-fill text-info',
    'Finance'        => 'bi-cash-stack text-danger',
    'AI & Tools'     => 'bi-cpu-fill text-secondary',
    'Superadmin'     => 'bi-shield-lock-fill text-dark',
];

// Current URL detection for active menu highlighting
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$currentScript = parse_url($currentUrl, PHP_URL_PATH) ?? '';

// Message/Error Handling from Session
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise ERP - ModPyPhp</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Font Awesome 6 CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --sidebar-width: 270px;
            --navbar-height: 60px;
            --body-bg: #f8fafc;
            --sidebar-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Navbar Styling */
        .navbar-main {
            height: var(--navbar-height);
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
            color: #ffffff !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            border-radius: 4px;
            margin-right: 10px;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            height: calc(100vh - var(--navbar-height));
            position: sticky;
            top: var(--navbar-height);
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .main-wrapper {
            display: flex;
            width: 100%;
            min-height: calc(100vh - var(--navbar-height));
        }

        .main-content {
            flex-grow: 1;
            padding: 1.5rem 2rem;
            background-color: var(--body-bg);
            min-width: 0; /* Prevents overflow with large tables */
        }

        /* Accordion Menu Styling */
        .sidebar .accordion-item {
            border: none;
            border-bottom: 1px solid #f1f5f9;
            background: transparent;
        }

        .sidebar .accordion-button {
            background-color: transparent;
            color: #334155;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.85rem 1.15rem;
            box-shadow: none;
            border-radius: 0;
            transition: all 0.2s ease;
        }

        .sidebar .accordion-button:not(.collapsed) {
            background-color: #f8fafc;
            color: #0f172a;
            box-shadow: inset 3px 0 0 #0d6efd;
        }

        .sidebar .accordion-button::after {
            background-size: 0.8rem;
            opacity: 0.6;
        }

        .sidebar .accordion-body {
            padding: 0.35rem 0.65rem 0.65rem 0.65rem;
            background-color: #ffffff;
        }

        .sidebar .nav-link {
            color: #475569;
            font-size: 0.835rem;
            font-weight: 500;
            padding: 0.5rem 0.85rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2px;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            color: #0d6efd;
            background-color: #f1f5f9;
            transform: translateX(2px);
        }

        .sidebar .nav-link.active {
            color: #0d6efd !important;
            background-color: #e0f2fe !important;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            font-size: 0.95rem;
            opacity: 0.85;
        }

        /* SB Admin 2 & ERP Cards Utility Classes */
        .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
        .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
        .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
        .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
        .border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
        .border-left-secondary { border-left: 0.25rem solid #858796 !important; }
        .border-left-dark { border-left: 0.25rem solid #5a5c69 !important; }

        .text-gray-800 { color: #1e293b !important; }
        .text-gray-700 { color: #334155 !important; }
        .text-gray-600 { color: #64748b !important; }
        .text-gray-500 { color: #94a3b8 !important; }
        .text-xs { font-size: 0.75rem !important; }

        .card {
            border: 1px solid var(--border-color);
            border-radius: 0.625rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04);
            background-color: #ffffff;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }

        /* Table Aesthetics */
        .table {
            color: #334155;
            vertical-align: middle;
        }

        .table thead th {
            font-weight: 600;
            font-size: 0.82rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* Badges & Buttons */
        .badge {
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .btn {
            font-weight: 500;
            border-radius: 6px;
        }

        /* Cloud Sync Button Animation */
        .btn-sync {
            background: rgba(255, 255, 255, 0.1);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            backdrop-filter: blur(4px);
            font-size: 0.825rem;
            transition: all 0.2s ease;
        }
        .btn-sync:hover {
            background: #38bdf8;
            color: #0f172a;
            border-color: #38bdf8;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -270px;
                z-index: 1040;
                transition: left 0.3s ease;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <header class="navbar navbar-main sticky-top px-3 py-2">
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-outline-light me-2 d-md-none" type="button" id="sidebarToggle" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>
            <a class="navbar-brand me-4" href="/">
                <img src="/assets/images/dda_logo.png" alt="DDA Logo" height="34" onerror="this.style.display='none'">
                <span>Enterprise ERP</span>
            </a>
        </div>

        <ul class="navbar-nav px-3 flex-row ms-auto align-items-center gap-3">
            <?php if ($role === 'superuser'): ?>
            <li class="nav-item">
                <button id="syncNeonBtn" class="btn btn-sync btn-sm py-1 px-3 d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Sync to Neon</span>
                </button>
            </li>
            <?php endif; ?>

            <!-- User Menu Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2 py-1 px-2 rounded hover-bg-dark" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary bg-opacity-25 rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-person-fill text-info"></i>
                    </div>
                    <div class="d-none d-sm-block text-start lh-1">
                        <span class="fw-semibold text-light" style="font-size: 0.875rem;"><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></span>
                        <small class="d-block text-white-50" style="font-size: 0.725rem;"><?= strtoupper(htmlspecialchars($role)) ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2 mt-2">
                    <li class="px-3 py-1 border-bottom mb-1">
                        <small class="text-muted d-block">Signed in as</small>
                        <strong class="text-dark"><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></strong>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="/modules/Superadmin/Users/Controller/UserController.php?action=logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </header>

    <?php if (!empty($_SESSION['is_demo_guest'])): ?>
    <!-- Demo Sandbox Alert & Quick Reset Bar -->
    <div class="alert alert-warning py-2 px-3 m-0 d-flex flex-wrap align-items-center justify-content-between rounded-0 border-0 shadow-sm" style="background: linear-gradient(90deg, #fff3cd 0%, #fef3c7 100%); color: #856404; font-size: 0.85rem; z-index: 1029;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-shaded text-warning fs-5"></i>
            <span><strong>DEMO SANDBOX ACTIVE (modpyphp_demo):</strong> You have full interactive CRUD privileges across all modules. Production data remains safely isolated.</span>
        </div>
        <div class="d-flex align-items-center gap-2 mt-1 mt-md-0">
            <a href="/modules/Superadmin/Users/Controller/UserController.php?action=reset_demo" class="btn btn-sm btn-outline-warning text-dark py-1 px-3 fw-semibold bg-white shadow-sm" onclick="return confirm('Restore all demo records back to the pristine initial dataset?');">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Demo Data
            </a>
            <a href="/modules/Superadmin/Users/Controller/UserController.php?action=logout" class="btn btn-sm btn-dark py-1 px-3 shadow-sm">
                Exit Demo
            </a>
        </div>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const syncBtn = document.getElementById('syncNeonBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', async function() {
                const origHtml = syncBtn.innerHTML;
                syncBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Syncing...';
                syncBtn.disabled = true;
                try {
                    const res = await fetch('/sync_to_neon.php');
                    const data = await res.json();
                    if (res.ok && data.status === 'success') {
                        alert('☁️ Mobile Cloud Sync Successful!\n\n' + JSON.stringify(data.synced, null, 2));
                    } else {
                        alert('Sync Error: ' + (data.message || 'Failed to sync to Neon Cloud.'));
                    }
                } catch (e) {
                    alert('Sync Error: ' + e.message);
                } finally {
                    syncBtn.innerHTML = origHtml;
                    syncBtn.disabled = false;
                }
            });
        }

        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('mainSidebar');
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
    </script>

    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <nav class="sidebar" id="mainSidebar">
            <div class="accordion accordion-flush" id="sidebarAccordion">
                <?php foreach ($menu as $category => $items): ?>
                    <?php
                        $collapseId = 'collapse-' . preg_replace('/[^a-zA-Z0-9]/', '', $category);

                        // Check if any child item matches current page
                        $isCategoryActive = false;
                        foreach ($items as $item) {
                            $itemPath = parse_url($item['url'], PHP_URL_PATH);
                            if ($itemPath === $currentScript) {
                                $isCategoryActive = true;
                                break;
                            }
                        }

                        $categoryIcon = $categoryIcons[$category] ?? 'bi-folder2-open';
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?php echo $collapseId; ?>">
                            <button class="accordion-button <?php echo $isCategoryActive ? '' : 'collapsed'; ?>" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#<?php echo $collapseId; ?>" 
                                    aria-expanded="<?php echo $isCategoryActive ? 'true' : 'false'; ?>" 
                                    aria-controls="<?php echo $collapseId; ?>">
                                <i class="bi <?php echo $categoryIcon; ?> me-2 fs-6"></i>
                                <span><?php echo htmlspecialchars($category); ?></span>
                            </button>
                        </h2>
                        <div id="<?php echo $collapseId; ?>" 
                             class="accordion-collapse collapse <?php echo $isCategoryActive ? 'show' : ''; ?>" 
                             data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body">
                                <nav class="nav flex-column">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                            $itemPath = parse_url($item['url'], PHP_URL_PATH);
                                            $itemQuery = parse_url($item['url'], PHP_URL_QUERY);
                                            $currentQuery = parse_url($currentUrl, PHP_URL_QUERY);

                                            $isActive = ($itemPath === $currentScript);
                                            if ($isActive && $itemQuery && $currentQuery) {
                                                // If query params are present in menu, require matching
                                                parse_str($itemQuery, $itemParams);
                                                parse_str($currentQuery, $currentParams);
                                                if (isset($itemParams['action']) && isset($currentParams['action'])) {
                                                    $isActive = ($itemParams['action'] === $currentParams['action']);
                                                }
                                            }

                                            $itemIcon = $item['icon'] ?? 'bi-chevron-right';
                                        ?>
                                        <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo $item['url']; ?>">
                                            <i class="bi <?php echo $itemIcon; ?>"></i>
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-octagon-fill fs-5 me-2 flex-shrink-0"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2 flex-shrink-0"></i>
                    <div><?= htmlspecialchars($message) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php
            if (isset($viewPath) && file_exists($viewPath)) {
                if (isset($data) && is_array($data)) { extract($data); }
                include $viewPath;
            }
            ?>
        </main>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
