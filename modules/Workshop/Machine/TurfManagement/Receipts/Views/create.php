<?php require_once __DIR__ . '/../../../../../../core/CSRFManager.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV Control System - Material Receipts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .source-card {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border: 2px solid #dee2e6;
        }
        .source-card:hover {
            transform: translateY(-2px);
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .source-card.active {
            border-color: #0d6efd;
            background-color: #f0f7ff;
            color: #0d6efd;
        }
        .voucher-badge {
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }
        .staged-voucher-card {
            border-left: 5px solid #0d6efd;
            background-color: #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .boq-table-scroll {
            max-height: 380px;
            overflow-y: auto;
        }
        .table-qty-input {
            width: 120px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-3">

    <!-- Top Navigation Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-dark mb-0">
                <i class="bi bi-box-arrow-in-down text-primary me-2"></i>RV Control System
            </h2>
            <small class="text-muted">Multi-RV Staging &bull; Tabular Contract Delivery Schedule &bull; Statutory 40L Fuel Splitting</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=list" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Receipts List
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT COLUMN: Main Inward & Staging Workstation -->
        <div class="col-lg-7">

            <!-- STEP 1: RECEIPT SOURCE SELECTION -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary rounded-pill me-2">Step 1</span>
                        <strong class="text-dark">Select Receipt Source & Contract Order</strong>
                    </div>
                    <span class="badge bg-light text-dark border">Procurement Mode</span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center source-card active" data-source="MANUAL" id="card-src-manual">
                                <i class="bi bi-pencil-square fs-3 d-block mb-1"></i>
                                <span class="fw-bold small">Manual / Retail</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center source-card" data-source="AGREEMENT" id="card-src-agreement">
                                <i class="bi bi-file-earmark-text fs-3 d-block mb-1 text-success"></i>
                                <span class="fw-bold small">From Agreement</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center source-card" data-source="SUPPLY_ORDER" id="card-src-so">
                                <i class="bi bi-truck fs-3 d-block mb-1 text-warning"></i>
                                <span class="fw-bold small">Supply Order (SO)</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded text-center source-card" data-source="WORK_ORDER" id="card-src-wo">
                                <i class="bi bi-tools fs-3 d-block mb-1 text-info"></i>
                                <span class="fw-bold small">Work Order (WO)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Selector (Dynamic based on source) -->
                    <div id="contract-selection-section" style="display: none;" class="p-3 bg-light rounded border mb-3">
                        <div class="row g-3 align-items-center mb-2">
                            <div class="col-md-7">
                                <label for="source_contract_select" class="form-label fw-bold text-primary" id="lbl-contract-select">
                                    Select Contract
                                </label>
                                <select id="source_contract_select" class="form-select border-primary fw-bold">
                                    <option value="">-- Choose Contract / Order --</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="small text-muted p-2 bg-white rounded border" id="contract-quick-info">
                                    <i class="bi bi-info-circle text-primary"></i> Selecting an order auto-fetches Agency Name & Tabular BOQ Schedule.
                                </div>
                            </div>
                        </div>

                        <!-- TABULAR CONTRACT DELIVERY SCHEDULE (Formatted for Human Reading) -->
                        <div id="boq-container" class="mt-3 pt-3 border-top" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="bi bi-table me-2 text-success"></i>Contract Sanction & Delivery Lot Schedule
                                    </h6>
                                    <small class="text-muted">Enter delivered lot quantities for this RV below. Multiple lots supported across RV installments.</small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge bg-primary fs-6" id="boq-items-count">0 Items</span>
                                    <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" id="btn-stage-all-lots">
                                        <i class="bi bi-check2-all me-1"></i> Stage All Filled Lots
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive boq-table-scroll border rounded bg-white shadow-sm mb-2">
                                <table class="table table-sm table-bordered table-hover align-middle mb-0 small">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th style="width: 35px;">#</th>
                                            <th class="text-start">Item Description / Nomenclature</th>
                                            <th style="width: 65px;">Unit</th>
                                            <th style="width: 95px;" class="text-end">Sanctioned</th>
                                            <th style="width: 95px;" class="text-end text-muted">Prev. Received</th>
                                            <th style="width: 105px;" class="text-end text-success fw-bold">Remaining Balance</th>
                                            <th style="width: 85px;" class="text-end">Rate (₹)</th>
                                            <th style="width: 140px;" class="bg-warning-subtle text-dark fw-bold">Delivered (This RV)</th>
                                            <th style="width: 95px;" class="text-end">Lot Value</th>
                                            <th style="width: 85px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="boq-items-tbody">
                                        <!-- Rendered dynamically via JS in tabular format -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted px-1">
                                <span><i class="bi bi-shield-check text-success"></i> <strong>Statutory Cap:</strong> Delivered lot quantity cannot exceed remaining sanctioned balance.</span>
                                <span id="boq-schedule-summary" class="fw-bold text-dark"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Master Header Fields -->
                    <div class="row g-3">
                        <?php if ($userRole === 'superuser'): ?>
                            <div class="col-md-4">
                                <label for="master_office_id" class="form-label fw-bold small">Office <span class="text-danger">*</span></label>
                                <select id="master_office_id" class="form-select form-select-sm" required>
                                    <option value="">-- Choose Office --</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo htmlspecialchars((string)$office['officeid']); ?>" <?php echo ((int)$currentUser['officeid'] === (int)$office['officeid']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['officename']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-<?php echo ($userRole === 'superuser') ? '4' : '6'; ?>">
                            <label for="master_voucher_no" class="form-label fw-bold small">
                                Voucher / Cash Memo No. <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-receipt"></i></span>
                                <input type="text" id="master_voucher_no" class="form-control fw-bold" placeholder="e.g. RV-101 / CM-4501" required>
                            </div>
                        </div>

                        <div class="col-md-<?php echo ($userRole === 'superuser') ? '4' : '6'; ?>">
                            <label for="master_voucher_date" class="form-label fw-bold small">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="master_voucher_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label for="master_party" class="form-label fw-bold small">
                                Received From (Vendor / Agency) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-building"></i></span>
                                <input type="text" id="master_party" class="form-control fw-bold" placeholder="Auto-fetched on contract select or enter vendor name">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="master_supply_status" class="form-label fw-bold small">Consignment Status</label>
                            <select id="master_supply_status" class="form-select form-select-sm">
                                <option value="COMPLETE">Complete Consignment</option>
                                <option value="PARTIAL">Partial Delivery (Ongoing)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: MANUAL CATALOG ITEM ENTRY (For Retail / Direct Store Inward) -->
            <div class="card border-0 shadow-sm mb-4" id="card-catalog-item-section">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary rounded-pill me-2">Step 2</span>
                        <strong class="text-dark">Manual Inward / Catalog Item Selection</strong>
                    </div>
                    <span class="badge bg-light text-muted border">Direct / Retail Mode</span>
                </div>
                <div class="card-body">
                    <!-- Division Selector -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="store_category_select" class="form-label fw-bold small">Sub-Store Division <span class="text-danger">*</span></label>
                            <select id="store_category_select" class="form-select border-success fw-bold">
                                <option value="fuel">⛽ Fuel / Lubricant Store (Petrol, Diesel, 2T)</option>
                                <option value="workshop">🛠️ Workshop Store (Machine Spare Parts)</option>
                                <option value="agronomy">🌱 Agronomy Store (Fertilizers, Chemicals)</option>
                                <option value="housekeeping">🧹 Housekeeping / Sanitary Store</option>
                                <option value="stationery">✏️ Stationery Store</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="alert alert-info py-2 px-3 mb-0 small w-100" id="statutory-fuel-tip">
                                <i class="bi bi-fuel-pump text-danger"></i> <strong>40L Fuel Rule:</strong> Add multiple RV numbers (e.g. RV-101, RV-102) in staging for petrol split-bills.
                            </div>
                        </div>
                    </div>

                    <!-- Item Fields -->
                    <div class="row g-3 align-items-end">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="item_select" class="form-label fw-bold small mb-0">
                                    Select Catalog Item <span class="text-danger">*</span>
                                </label>
                                <span class="text-muted small" style="font-size: 11px;">
                                    Can't find an item? Select <strong class="text-primary">"---> Add Custom Item"</strong> at bottom of list
                                </span>
                            </div>
                            <select id="item_select" class="form-select fw-bold border-2">
                                <option value="">-- Choose Item from Division --</option>
                            </select>
                        </div>

                        <!-- Manual Custom Item Panel (Opens when '---> Add Custom Item' is selected) -->
                        <div class="col-md-12" id="custom-item-name-row" style="display: none;">
                            <div class="p-3 bg-light rounded border border-primary border-2 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                    <span class="fw-bold text-primary small">
                                        <i class="bi bi-plus-circle-fill me-1"></i> Add New Consumable Item to Division
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border" id="custom_cat_badge">Category</span>
                                </div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label for="custom_item_name" class="form-label fw-bold small text-dark mb-1">
                                            New Item Name / Nomenclature <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="custom_item_name" class="form-control form-control-sm fw-bold border-primary" placeholder="Enter full item name (e.g. Bermuda Grass Seed, Weedicide Pro)">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="custom_item_unit" class="form-label fw-bold small text-dark mb-1">Unit of Measure (UOM)</label>
                                        <input type="text" id="custom_item_unit" class="form-control form-control-sm" placeholder="e.g. Litre, Kg, Pcs, Bag" value="Pcs">
                                    </div>
                                    <div class="col-md-3" id="custom_item_subcat_col">
                                        <label for="custom_item_cat_id" class="form-label fw-bold small text-dark mb-1">Specific Category</label>
                                        <select id="custom_item_cat_id" class="form-select form-select-sm">
                                            <!-- Dynamic based on selected store division -->
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center mt-2 pt-1 border-top">
                                        <small class="text-muted" style="font-size: 11px;">
                                            <i class="bi bi-shield-check text-success"></i> Will be permanently added to the list of consumable items in this category.
                                        </small>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-custom-item">
                                                Cancel
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm" id="btn-save-custom-item">
                                                <i class="bi bi-save me-1"></i> Save Item to Consumables
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="custom-item-feedback" class="small mt-2" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="item_quantity" class="form-label fw-bold small">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="item_quantity" class="form-control fw-bold" step="0.01" min="0.01" placeholder="Qty">
                                <span class="input-group-text" id="item_unit_display">Units</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="item_rate" class="form-label fw-bold small">Unit Rate (₹)</label>
                            <input type="number" id="item_rate" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="col-md-4">
                            <label for="item_remarks" class="form-label fw-bold small">Remarks</label>
                            <input type="text" id="item_remarks" class="form-control form-control-sm" placeholder="Tank / Pump / Batch">
                        </div>
                    </div>

                    <div class="mt-4 pt-2 text-end">
                        <button type="button" id="btn-add-to-staging" class="btn btn-success fw-bold px-4 shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Item to Staged Vouchers
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Staged Vouchers Tray & Multi-RV Batch Commit -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-collection me-2 text-warning"></i>
                        <strong class="h6 mb-0">Staged Vouchers Tray</strong>
                    </div>
                    <span class="badge bg-primary fs-6" id="badge-total-vouchers">0 Vouchers</span>
                </div>

                <div class="card-body p-3" style="max-height: 540px; overflow-y: auto;">
                    <div id="staged-empty-placeholder" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="mb-1 fw-bold">No items staged yet.</p>
                        <small>Fill details on the left and click <strong>"Add to Staged Vouchers"</strong> or stage lots from the contract schedule.<br>Multiple distinct RVs can be staged together!</small>
                    </div>

                    <!-- Container for grouped vouchers -->
                    <div id="staged-vouchers-container"></div>
                </div>

                <!-- Footer Summary & Final Commit -->
                <div class="card-footer bg-light p-3 border-top">
                    <div class="d-flex justify-content-between small mb-2 text-muted">
                        <span>Total Items Staged:</span>
                        <strong class="text-dark" id="summary-total-items">0</strong>
                    </div>
                    <div class="d-flex justify-content-between small mb-3 text-muted">
                        <span>Estimated Staged Value:</span>
                        <strong class="text-success" id="summary-total-val">₹0.00</strong>
                    </div>

                    <button type="button" id="btn-commit-all" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" disabled>
                        <i class="bi bi-cloud-arrow-up me-1"></i> Commit All Receipt Vouchers
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOGIC -->
<script>
    const userRole = "<?php echo $userRole; ?>";
    const currentUserOfficeId = "<?php echo (int)($currentUser['officeid'] ?? 0); ?>";
    const csrfToken = "<?php echo \App\Core\CSRFManager::generateToken(); ?>";
    const allInventoryItems = <?php echo json_encode($inventoryItems); ?>;
    const allSpareParts = <?php echo json_encode($spareParts); ?>;
    const initialSourceOptions = <?php echo json_encode($sourceOptions); ?>;
    const allCategories = <?php echo json_encode($categories ?? []); ?>;

    // State Variables
    let currentSourceType = 'MANUAL';
    let currentContractId = null;
    let currentContractRefNo = '';
    let currentAgencyId = null;
    let contractBoqItems = [];

    // Array of staged items
    let stagedItems = [];

    document.addEventListener('DOMContentLoaded', function() {
        initSourceSelector();
        initCategoryFilter();
        initCustomItemHandler();
        initStagingLogic();
        initCommitLogic();
        populateItemsForCategory('fuel');
    });

    // 1. Source Card Handling (Manual vs Agreement vs SO vs WO)
    function initSourceSelector() {
        const cards = document.querySelectorAll('.source-card');
        const contractSec = document.getElementById('contract-selection-section');
        const contractSelect = document.getElementById('source_contract_select');
        const partyInput = document.getElementById('master_party');
        const lblContract = document.getElementById('lbl-contract-select');
        const boqContainer = document.getElementById('boq-container');
        const catalogCard = document.getElementById('card-catalog-item-section');

        cards.forEach(card => {
            card.addEventListener('click', function() {
                cards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                currentSourceType = this.dataset.source;
                currentContractId = null;
                currentContractRefNo = '';
                currentAgencyId = null;
                contractBoqItems = [];
                boqContainer.style.display = 'none';

                if (currentSourceType === 'MANUAL') {
                    contractSec.style.display = 'none';
                    partyInput.readOnly = false;
                    partyInput.placeholder = 'Vendor / Source / Petrol Pump';
                    partyInput.value = '';
                    catalogCard.style.display = 'block';
                } else {
                    contractSec.style.display = 'block';
                    partyInput.readOnly = true;
                    partyInput.placeholder = 'Auto-fetched from contract order';
                    partyInput.value = '';

                    lblContract.textContent = 'Select ' + (currentSourceType === 'AGREEMENT' ? 'Agreement' : (currentSourceType === 'SUPPLY_ORDER' ? 'Supply Order' : 'Work Order'));
                    populateContractDropdown(currentSourceType);
                }
            });
        });

        contractSelect.addEventListener('change', function() {
            const selectedId = this.value;
            if (!selectedId) {
                partyInput.value = '';
                boqContainer.style.display = 'none';
                return;
            }
            fetchContractDetails(currentSourceType, selectedId);
        });
    }

    function populateContractDropdown(sourceType) {
        const select = document.getElementById('source_contract_select');
        select.innerHTML = '<option value="">-- Choose ' + sourceType + ' --</option>';

        let list = [];
        if (sourceType === 'AGREEMENT') {
            list = initialSourceOptions.agreements || [];
            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = `${item.agreement_no} [Agency: ${item.agency_name || 'N/A'}]`;
                select.appendChild(opt);
            });
        } else if (sourceType === 'SUPPLY_ORDER') {
            list = initialSourceOptions.supply_orders || [];
            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = `${item.supply_order_no} [Agency: ${item.agency_name || 'N/A'}]`;
                select.appendChild(opt);
            });
        } else if (sourceType === 'WORK_ORDER') {
            list = initialSourceOptions.work_orders || [];
            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = `${item.work_order_no} [Agency: ${item.agency_name || 'N/A'}]`;
                select.appendChild(opt);
            });
        }
    }

    function fetchContractDetails(sourceType, sourceId) {
        fetch(`?action=fetchSourceDetails&source_type=${encodeURIComponent(sourceType)}&source_id=${encodeURIComponent(sourceId)}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    const d = res.data;
                    currentContractId = d.source_id;
                    currentContractRefNo = d.source_ref_no;
                    currentAgencyId = d.agency_id;

                    // Instantly auto-fetch & set Agency Name
                    const partyInput = document.getElementById('master_party');
                    partyInput.value = d.agency_name || '';

                    // Display BOQ schedule in tabular format
                    contractBoqItems = d.items || [];
                    renderBoqTable(contractBoqItems);
                } else {
                    alert(res.message || 'Error fetching contract details');
                }
            })
            .catch(err => console.error('Fetch contract error:', err));
    }

    // 2. Render Tabular Contract Schedule with Live Inward Inputs & Validation
    function renderBoqTable(items) {
        const container = document.getElementById('boq-container');
        const countBadge = document.getElementById('boq-items-count');
        const tbody = document.getElementById('boq-items-tbody');

        if (!items || items.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        countBadge.textContent = items.length + ' Items';
        tbody.innerHTML = '';

        items.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.id = `boq-row-${idx}`;

            // Calculate live balance taking into account any lots already staged in this current session
            const currentSessionStaged = getCurrentlyStagedQtyForBoqItem(item.description);
            const effectiveBalance = Math.max(0, item.balance_quantity - currentSessionStaged);

            tr.innerHTML = `
                <td class="text-center fw-bold">${idx + 1}</td>
                <td>
                    <strong class="text-dark">${escapeHtml(item.description)}</strong>
                </td>
                <td class="text-center">${escapeHtml(item.unit || 'Units')}</td>
                <td class="text-end fw-bold">${parseFloat(item.sanctioned_quantity).toFixed(2)}</td>
                <td class="text-end text-muted">${parseFloat(item.previously_received_qty).toFixed(2)}</td>
                <td class="text-end text-success fw-bold" id="boq-balance-${idx}">${effectiveBalance.toFixed(2)}</td>
                <td class="text-end">₹${parseFloat(item.rate || 0).toFixed(2)}</td>
                <td class="bg-warning-subtle text-center">
                    <input type="number" 
                           class="form-control form-control-sm text-end fw-bold boq-delivered-input" 
                           id="boq-del-input-${idx}" 
                           data-idx="${idx}"
                           min="0" 
                           max="${effectiveBalance}" 
                           step="0.01" 
                           placeholder="0.00" 
                           oninput="onBoqQtyInput(${idx})"
                           ${effectiveBalance <= 0 ? 'disabled' : ''}>
                    <div class="text-danger small mt-1 fw-bold" id="boq-err-${idx}" style="display: none; font-size: 11px;"></div>
                </td>
                <td class="text-end fw-bold" id="boq-lot-val-${idx}">-</td>
                <td class="text-center">
                    <button type="button" 
                            class="btn btn-xs btn-outline-success py-1 px-2 fw-bold" 
                            id="boq-stage-btn-${idx}"
                            onclick="stageSingleBoqLot(${idx})"
                            ${effectiveBalance <= 0 ? 'disabled' : ''}>
                        <i class="bi bi-plus-circle"></i> Stage
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Hook Stage All Filled Lots button
        document.getElementById('btn-stage-all-lots').onclick = stageAllFilledBoqLots;
    }

    function getCurrentlyStagedQtyForBoqItem(description) {
        let sum = 0;
        const dKey = description.toLowerCase().trim();
        stagedItems.forEach(it => {
            if (it.description.toLowerCase().trim() === dKey) {
                sum += parseFloat(it.quantity || 0);
            }
        });
        return sum;
    }

    window.onBoqQtyInput = function(idx) {
        const item = contractBoqItems[idx];
        if (!item) return;

        const input = document.getElementById(`boq-del-input-${idx}`);
        const errDiv = document.getElementById(`boq-err-${idx}`);
        const valCell = document.getElementById(`boq-lot-val-${idx}`);
        const stageBtn = document.getElementById(`boq-stage-btn-${idx}`);

        const currentSessionStaged = getCurrentlyStagedQtyForBoqItem(item.description);
        const effectiveBalance = Math.max(0, item.balance_quantity - currentSessionStaged);

        const enteredQty = parseFloat(input.value);

        if (isNaN(enteredQty) || enteredQty <= 0) {
            input.classList.remove('is-invalid', 'is-valid');
            errDiv.style.display = 'none';
            valCell.textContent = '-';
            return;
        }

        // STATUTORY CONSTRAINT CHECK: Delivered cannot exceed remaining sanctioned balance
        if (enteredQty > effectiveBalance) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            errDiv.style.display = 'block';
            errDiv.textContent = `Exceeds Balance! Max: ${effectiveBalance.toFixed(2)}`;
            valCell.textContent = '-';
            stageBtn.disabled = true;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            errDiv.style.display = 'none';
            valCell.textContent = '₹' + (enteredQty * item.rate).toFixed(2);
            stageBtn.disabled = false;
        }
    };

    window.stageSingleBoqLot = function(idx) {
        const item = contractBoqItems[idx];
        if (!item) return;

        const input = document.getElementById(`boq-del-input-${idx}`);
        const qty = parseFloat(input.value);

        if (isNaN(qty) || qty <= 0) {
            alert('Please enter a valid delivered quantity greater than zero.');
            input.focus();
            return;
        }

        const voucherNo = document.getElementById('master_voucher_no').value.trim();
        const voucherDate = document.getElementById('master_voucher_date').value;
        const party = document.getElementById('master_party').value.trim();
        const supplyStatus = document.getElementById('master_supply_status').value;
        const officeId = document.getElementById('master_office_id') ? document.getElementById('master_office_id').value : currentUserOfficeId;

        if (!voucherNo) {
            alert('Please enter a Voucher / Cash Memo Number in Step 1 before staging.');
            document.getElementById('master_voucher_no').focus();
            return;
        }

        // Duplicate Check within the SAME Voucher
        const duplicateInSameVoucher = stagedItems.some(st => {
            return (st.voucher_no.toUpperCase() === voucherNo.toUpperCase() && 
                    st.description.toLowerCase().trim() === item.description.toLowerCase().trim());
        });

        if (duplicateInSameVoucher) {
            alert(`Item '${item.description}' is already staged under Voucher '${voucherNo}'.\n\nIf this is a split-delivery lot (e.g. 40L petrol limit), change the Voucher Number above to stage under a separate RV!`);
            return;
        }

        // Validation against balance
        const currentSessionStaged = getCurrentlyStagedQtyForBoqItem(item.description);
        const effectiveBalance = Math.max(0, item.balance_quantity - currentSessionStaged);

        if (qty > effectiveBalance) {
            alert(`Validation Failed: Delivered quantity (${qty}) exceeds the allowable sanctioned balance (${effectiveBalance.toFixed(2)}) for item '${item.description}'.`);
            return;
        }

        // Determine category
        let cat = 'general';
        const dLow = item.description.toLowerCase();
        if (dLow.includes('petrol') || dLow.includes('diesel') || dLow.includes('oil') || dLow.includes('fuel')) cat = 'fuel';
        else if (dLow.includes('fertilizer') || dLow.includes('seed') || dLow.includes('weedicide') || dLow.includes('pesticide')) cat = 'agronomy';
        else if (dLow.includes('bearing') || dLow.includes('blade') || dLow.includes('filter') || dLow.includes('spare')) cat = 'workshop';

        // Stage record
        stagedItems.push({
            id: 'stg_boq_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
            officeid: officeId,
            voucher_no: voucherNo,
            voucher_date: voucherDate,
            party: party,
            agency_id: currentAgencyId,
            source_type: currentSourceType,
            source_id: currentContractId,
            source_ref_no: currentContractRefNo,
            store_category: cat,
            supply_status: supplyStatus,
            item_id: null,
            spare_part_id: null,
            is_spare_part: (cat === 'workshop'),
            is_new_item: true,
            description: item.description,
            quantity: qty,
            unit: item.unit || 'Units',
            rate: item.rate || 0,
            remarks: `Ref: ${currentContractRefNo} Lot`
        });

        // Reset input and refresh table & tray
        input.value = '';
        input.classList.remove('is-valid');
        document.getElementById(`boq-lot-val-${idx}`).textContent = '-';
        renderStagedVouchers();
        renderBoqTable(contractBoqItems);
    };

    function stageAllFilledBoqLots() {
        const voucherNo = document.getElementById('master_voucher_no').value.trim();
        if (!voucherNo) {
            alert('Please enter a Voucher / Cash Memo Number in Step 1 first.');
            document.getElementById('master_voucher_no').focus();
            return;
        }

        let stagedCount = 0;
        contractBoqItems.forEach((item, idx) => {
            const input = document.getElementById(`boq-del-input-${idx}`);
            if (input && input.value) {
                const qty = parseFloat(input.value);
                if (qty > 0) {
                    const currentSessionStaged = getCurrentlyStagedQtyForBoqItem(item.description);
                    const effectiveBalance = Math.max(0, item.balance_quantity - currentSessionStaged);

                    if (qty <= effectiveBalance) {
                        stageSingleBoqLot(idx);
                        stagedCount++;
                    }
                }
            }
        });

        if (stagedCount === 0) {
            alert('No valid lot quantities were filled in the table. Enter delivered quantities in the "Delivered (This RV)" column first.');
        }
    }

    // 3. Category Filter & Manual Item Dropdown population
    function initCategoryFilter() {
        const catSelect = document.getElementById('store_category_select');
        catSelect.addEventListener('change', function() {
            populateItemsForCategory(this.value);
            const tip = document.getElementById('statutory-fuel-tip');
            if (this.value === 'fuel') {
                tip.innerHTML = '<i class="bi bi-fuel-pump text-danger"></i> <strong>40L Fuel Rule:</strong> Add multiple RV numbers (e.g. RV-101, RV-102) in staging for petrol split-bills.';
            } else {
                tip.innerHTML = '<i class="bi bi-info-circle text-primary"></i> Stage received items for this division under the selected Voucher.';
            }
        });
    }

    function populateItemsForCategory(category) {
        const itemSelect = document.getElementById('item_select');
        itemSelect.innerHTML = '<option value="">-- Choose Item from Division --</option>';

        if (category === 'workshop') {
            allSpareParts.forEach(sp => {
                const opt = document.createElement('option');
                opt.value = 'sp_' + sp.id;
                opt.dataset.type = 'spare';
                opt.dataset.id = sp.id;
                opt.dataset.unit = sp.unit || 'Pcs';
                opt.dataset.desc = (sp.machine_name ? '[' + sp.machine_name + '] ' : '') + sp.nomenclature + (sp.part_no ? ' (' + sp.part_no + ')' : '');
                opt.textContent = opt.dataset.desc;
                itemSelect.appendChild(opt);
            });
            // Also include consumable spare parts from inventory items
            allInventoryItems.forEach(item => {
                const cat = (item.category_name || '').toLowerCase();
                const code = (item.category_code || '').toLowerCase();
                if (cat.includes('spare') || code.includes('spare') || item.category_id == 9) {
                    const opt = document.createElement('option');
                    opt.value = 'item_' + item.id;
                    opt.dataset.type = 'general';
                    opt.dataset.id = item.id;
                    opt.dataset.unit = item.ac_unit || 'Pcs';
                    opt.dataset.desc = item.description;
                    opt.textContent = item.description;
                    itemSelect.appendChild(opt);
                }
            });
        } else {
            allInventoryItems.forEach(item => {
                const cat = (item.category_name || '').toLowerCase();
                let isMatch = false;

                if (category === 'fuel' && (cat.includes('fuel') || cat.includes('lubricant') || cat.includes('oil'))) isMatch = true;
                else if (category === 'housekeeping' && (cat.includes('sanitary') || cat.includes('housekeeping'))) isMatch = true;
                else if (category === 'stationery' && cat.includes('station')) isMatch = true;
                else if (category === 'agronomy' && !cat.includes('fuel') && !cat.includes('spare') && !cat.includes('sanitary') && !cat.includes('housekeeping') && !cat.includes('station')) isMatch = true;

                if (isMatch) {
                    const opt = document.createElement('option');
                    opt.value = 'item_' + item.id;
                    opt.dataset.type = 'general';
                    opt.dataset.id = item.id;
                    opt.dataset.unit = item.ac_unit || 'Pcs';
                    opt.dataset.desc = item.description;
                    opt.textContent = item.description;
                    itemSelect.appendChild(opt);
                }
            });
        }

        // Add ---> Add Custom Item at the very end / last of the dropdown
        const customOpt = document.createElement('option');
        customOpt.value = '__ADD_CUSTOM__';
        customOpt.textContent = '---> Add Custom Item';
        customOpt.className = 'fw-bold text-primary';
        itemSelect.appendChild(customOpt);

        hideCustomItemBox();
    }

    function initCustomItemHandler() {
        const itemSelect = document.getElementById('item_select');
        const cancelBtn = document.getElementById('btn-cancel-custom-item');
        const saveBtn = document.getElementById('btn-save-custom-item');
        const nameInput = document.getElementById('custom_item_name');

        itemSelect.addEventListener('change', function() {
            if (this.value === '__ADD_CUSTOM__') {
                showCustomItemBox();
            } else {
                hideCustomItemBox();
                const selectedOpt = this.options[this.selectedIndex];
                if (selectedOpt && selectedOpt.dataset.unit) {
                    document.getElementById('item_unit_display').textContent = selectedOpt.dataset.unit;
                }
            }
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                itemSelect.value = '';
                hideCustomItemBox();
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', async function() {
                await saveCustomItemDirectly();
            });
        }

        if (nameInput) {
            nameInput.addEventListener('keydown', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    await saveCustomItemDirectly();
                }
            });
        }
    }

    function showCustomItemBox() {
        const customRow = document.getElementById('custom-item-name-row');
        if (!customRow) return;
        customRow.style.display = 'block';

        const storeCategory = document.getElementById('store_category_select').value;
        const badge = document.getElementById('custom_cat_badge');
        const subCatSelect = document.getElementById('custom_item_cat_id');
        const unitInput = document.getElementById('custom_item_unit');
        const nameInput = document.getElementById('custom_item_name');

        const catLabels = {
            'fuel': 'Fuel & Lubricants Store',
            'agronomy': 'Agronomy (Fertilizers & Chemicals)',
            'workshop': 'Workshop Spares Store',
            'housekeeping': 'Housekeeping & Sanitary',
            'stationery': 'Stationery Store'
        };
        if (badge) badge.textContent = catLabels[storeCategory] || storeCategory.toUpperCase();

        // Populate sub-categories based on selected store division
        subCatSelect.innerHTML = '';
        let relevantCats = [];
        let defaultUnit = 'Pcs';

        if (storeCategory === 'fuel') {
            defaultUnit = 'Litre';
            relevantCats = allCategories.filter(c => c.code === 'fuel' || c.code === 'lubricants' || (c.name || '').toLowerCase().includes('fuel') || (c.name || '').toLowerCase().includes('lubricant'));
        } else if (storeCategory === 'agronomy') {
            defaultUnit = 'Kg';
            relevantCats = allCategories.filter(c => !['fuel', 'lubricants', 'spare_parts', 'stationary', 'sanitary_item'].includes(c.code));
        } else if (storeCategory === 'workshop') {
            defaultUnit = 'Nos';
            relevantCats = allCategories.filter(c => c.code === 'spare_parts' || (c.name || '').toLowerCase().includes('spare'));
        } else if (storeCategory === 'housekeeping') {
            defaultUnit = 'Nos';
            relevantCats = allCategories.filter(c => c.code === 'sanitary_item' || (c.name || '').toLowerCase().includes('sanitary'));
        } else if (storeCategory === 'stationery') {
            defaultUnit = 'Pcs';
            relevantCats = allCategories.filter(c => c.code === 'stationary' || (c.name || '').toLowerCase().includes('station'));
        }

        if (relevantCats.length === 0) {
            relevantCats = allCategories;
        }

        relevantCats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            subCatSelect.appendChild(opt);
        });

        unitInput.value = defaultUnit;
        document.getElementById('item_unit_display').textContent = defaultUnit;
        nameInput.value = '';
        setTimeout(() => nameInput.focus(), 50);
    }

    function hideCustomItemBox() {
        const customRow = document.getElementById('custom-item-name-row');
        if (customRow) customRow.style.display = 'none';
        const feedback = document.getElementById('custom-item-feedback');
        if (feedback) feedback.style.display = 'none';
    }

    async function saveCustomItemDirectly() {
        const nameInput = document.getElementById('custom_item_name');
        const unitInput = document.getElementById('custom_item_unit');
        const catSelect = document.getElementById('custom_item_cat_id');
        const storeCategory = document.getElementById('store_category_select').value;
        const saveBtn = document.getElementById('btn-save-custom-item');

        const itemName = nameInput.value.trim();
        if (!itemName) {
            alert('Please enter a name for the new item.');
            nameInput.focus();
            return null;
        }

        const itemUnit = unitInput.value.trim() || 'Pcs';
        const categoryId = catSelect.value;

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        }

        try {
            const payload = {
                description: itemName,
                ac_unit: itemUnit,
                category_id: categoryId,
                store_category: storeCategory
            };

            const response = await fetch('?action=addCustomConsumableItem', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (result.status === 'success' && result.item) {
                const newItem = result.item;

                // Push into allInventoryItems if not existing
                const exists = allInventoryItems.some(it => it.id === newItem.id);
                if (!exists) {
                    allInventoryItems.push({
                        id: newItem.id,
                        description: newItem.description,
                        ac_unit: newItem.ac_unit,
                        category_id: newItem.category_id,
                        category_name: newItem.category_name,
                        category_code: newItem.category_code
                    });
                }

                // Repopulate item_select for current category and select this new item
                populateItemsForCategory(storeCategory);
                const itemSelect = document.getElementById('item_select');
                itemSelect.value = 'item_' + newItem.id;
                document.getElementById('item_unit_display').textContent = newItem.ac_unit;

                hideCustomItemBox();

                showTemporaryToast(result.message || `Item "${newItem.description}" saved to catalog!`);
                document.getElementById('item_quantity').focus();
                return newItem;
            } else {
                alert('Error saving custom item: ' + (result.message || 'Unknown error'));
                return null;
            }
        } catch (err) {
            console.error('Error saving custom item:', err);
            alert('Server error saving item: ' + err.message);
            return null;
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save me-1"></i> Save Item to Consumables';
            }
        }
    }

    function showTemporaryToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1090';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show shadow mb-2`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-bold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // 4. Manual Staging Logic
    function initStagingLogic() {
        const btnAdd = document.getElementById('btn-add-to-staging');

        btnAdd.addEventListener('click', async function() {
            const voucherNo = document.getElementById('master_voucher_no').value.trim();
            const voucherDate = document.getElementById('master_voucher_date').value;
            const party = document.getElementById('master_party').value.trim();
            const storeCategory = document.getElementById('store_category_select').value;
            const quantity = parseFloat(document.getElementById('item_quantity').value);
            const rate = parseFloat(document.getElementById('item_rate').value) || 0;
            const remarks = document.getElementById('item_remarks').value.trim();
            const supplyStatus = document.getElementById('master_supply_status').value;

            const itemSelect = document.getElementById('item_select');

            // If user selected ---> Add Custom Item, save it directly first
            if (itemSelect.value === '__ADD_CUSTOM__') {
                const customName = document.getElementById('custom_item_name').value.trim();
                if (!customName) {
                    alert('Please write the name of the new item.');
                    document.getElementById('custom_item_name').focus();
                    return;
                }
                const saved = await saveCustomItemDirectly();
                if (!saved) return;
            }

            const selectedOpt = itemSelect.options[itemSelect.selectedIndex];

            if (!voucherNo) {
                alert('Please enter a Voucher / Cash Memo Number.');
                document.getElementById('master_voucher_no').focus();
                return;
            }
            if (!voucherDate) {
                alert('Please select a Date.');
                return;
            }
            if (!party) {
                alert('Please provide the Received From / Agency name.');
                document.getElementById('master_party').focus();
                return;
            }
            if (isNaN(quantity) || quantity <= 0) {
                alert('Please enter a valid Quantity greater than zero.');
                document.getElementById('item_quantity').focus();
                return;
            }
            if (!itemSelect.value || itemSelect.value === '__ADD_CUSTOM__') {
                alert('Please choose an item from the catalog or write a custom item name.');
                return;
            }

            let itemId = null;
            let sparePartId = null;
            let isSparePart = false;
            let isNewItem = false;
            let description = selectedOpt.dataset.desc || selectedOpt.textContent;
            let unit = selectedOpt.dataset.unit || document.getElementById('item_unit_display').textContent || 'Units';

            if (selectedOpt.dataset.type === 'spare') {
                sparePartId = parseInt(selectedOpt.dataset.id);
                isSparePart = true;
            } else {
                itemId = parseInt(selectedOpt.dataset.id);
            }

            // STATUTORY DUPLICATE CHECK:
            // Within the SAME Voucher No, an item cannot be repeated.
            // Across DIFFERENT Voucher Nos, the same item (e.g. 40L Petrol) is fully allowed!
            const duplicateInSameVoucher = stagedItems.some(item => {
                if (item.voucher_no.toUpperCase() !== voucherNo.toUpperCase()) {
                    return false;
                }
                if (itemId && item.item_id === itemId) return true;
                if (sparePartId && item.spare_part_id === sparePartId) return true;
                if (item.description.toLowerCase().trim() === description.toLowerCase().trim()) return true;
                return false;
            });

            if (duplicateInSameVoucher) {
                alert(`Duplicate Blocked: Item '${description}' is already staged under Voucher '${voucherNo}'.\n\nUnder statutory fuel rules, if this is a separate cash memo / 40L petrol lot, change the Voucher Number (e.g. ${voucherNo}-B or next RV No) above!`);
                return;
            }

            const officeId = document.getElementById('master_office_id') ? document.getElementById('master_office_id').value : currentUserOfficeId;

            const newItem = {
                id: 'stg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                officeid: officeId,
                voucher_no: voucherNo,
                voucher_date: voucherDate,
                party: party,
                agency_id: currentAgencyId,
                source_type: currentSourceType,
                source_id: currentContractId,
                source_ref_no: currentContractRefNo,
                store_category: storeCategory,
                supply_status: supplyStatus,
                item_id: itemId,
                spare_part_id: sparePartId,
                is_spare_part: isSparePart,
                is_new_item: isNewItem,
                description: description,
                quantity: quantity,
                unit: unit,
                rate: rate,
                remarks: remarks
            };

            stagedItems.push(newItem);
            renderStagedVouchers();

            // Reset Item fields
            document.getElementById('item_quantity').value = '';
            document.getElementById('item_rate').value = '';
            document.getElementById('item_remarks').value = '';
            document.getElementById('custom_item_name').value = '';
            hideCustomItemBox();
            itemSelect.value = '';
        });
    }

    // 5. Render Grouped Staged Vouchers Tray
    function renderStagedVouchers() {
        const container = document.getElementById('staged-vouchers-container');
        const emptyPlaceholder = document.getElementById('staged-empty-placeholder');
        const badgeTotal = document.getElementById('badge-total-vouchers');
        const summaryItems = document.getElementById('summary-total-items');
        const summaryVal = document.getElementById('summary-total-val');
        const commitBtn = document.getElementById('btn-commit-all');

        if (stagedItems.length === 0) {
            emptyPlaceholder.style.display = 'block';
            container.innerHTML = '';
            badgeTotal.textContent = '0 Vouchers';
            summaryItems.textContent = '0';
            summaryVal.textContent = '₹0.00';
            commitBtn.disabled = true;
            return;
        }

        emptyPlaceholder.style.display = 'none';
        commitBtn.disabled = false;

        const groups = {};
        let totalVal = 0;

        stagedItems.forEach(item => {
            const vNo = item.voucher_no;
            if (!groups[vNo]) {
                groups[vNo] = {
                    voucher_no: vNo,
                    voucher_date: item.voucher_date,
                    party: item.party,
                    store_category: item.store_category,
                    source_type: item.source_type,
                    source_ref_no: item.source_ref_no,
                    items: []
                };
            }
            groups[vNo].items.push(item);
            totalVal += (item.quantity * item.rate);
        });

        const distinctVoucherCount = Object.keys(groups).length;
        badgeTotal.textContent = distinctVoucherCount + ' Voucher' + (distinctVoucherCount > 1 ? 's' : '');
        summaryItems.textContent = stagedItems.length;
        summaryVal.textContent = '₹' + totalVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        container.innerHTML = '';

        Object.values(groups).forEach(g => {
            const card = document.createElement('div');
            card.className = 'card staged-voucher-card mb-3';

            let itemsRows = '';
            g.items.forEach(it => {
                itemsRows += `
                    <tr>
                        <td class="fw-bold small">${escapeHtml(it.description)}</td>
                        <td class="text-end fw-bold text-primary small">${it.quantity} ${escapeHtml(it.unit)}</td>
                        <td class="text-end small">${it.rate > 0 ? '₹' + parseFloat(it.rate).toFixed(2) : '-'}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-link text-danger p-0" title="Remove Item" onclick="removeStagedItem('${it.id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            card.innerHTML = `
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-primary voucher-badge me-1"><i class="bi bi-receipt"></i> ${escapeHtml(g.voucher_no)}</span>
                            <span class="badge bg-secondary small">${escapeHtml(g.store_category.toUpperCase())}</span>
                            ${g.source_type !== 'MANUAL' ? `<span class="badge bg-info text-dark small">${escapeHtml(g.source_type)}: ${escapeHtml(g.source_ref_no)}</span>` : ''}
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 small" onclick="removeEntireVoucher('${escapeHtml(g.voucher_no)}')">
                            Drop RV
                        </button>
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-calendar3 me-1"></i>${g.voucher_date} &bull; <i class="bi bi-building me-1"></i>${escapeHtml(g.party)}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Rate</th>
                                    <th style="width: 30px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    window.removeStagedItem = function(itemId) {
        stagedItems = stagedItems.filter(it => it.id !== itemId);
        renderStagedVouchers();
        if (contractBoqItems.length > 0) {
            renderBoqTable(contractBoqItems);
        }
    };

    window.removeEntireVoucher = function(vNo) {
        if (confirm(`Remove all items staged under voucher '${vNo}'?`)) {
            stagedItems = stagedItems.filter(it => it.voucher_no !== vNo);
            renderStagedVouchers();
            if (contractBoqItems.length > 0) {
                renderBoqTable(contractBoqItems);
            }
        }
    };

    // 6. Commit Staged Vouchers
    function initCommitLogic() {
        const commitBtn = document.getElementById('btn-commit-all');

        commitBtn.addEventListener('click', function() {
            if (stagedItems.length === 0) return;

            const distinctVouchers = [...new Set(stagedItems.map(it => it.voucher_no))];
            if (!confirm(`Are you sure you want to commit ${distinctVouchers.length} Receipt Voucher(s) with ${stagedItems.length} total items into the system?`)) {
                return;
            }

            commitBtn.disabled = true;
            commitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving to Database...';

            const payload = {
                csrf_token: csrfToken,
                masterData: {
                    office_id: document.getElementById('master_office_id') ? document.getElementById('master_office_id').value : currentUserOfficeId,
                    transaction_date: document.getElementById('master_voucher_date').value,
                    source_type: currentSourceType,
                    source_id: currentContractId,
                    source_ref_no: currentContractRefNo,
                    agency_id: currentAgencyId
                },
                stagedItems: stagedItems
            };

            fetch('?action=createBulkReceipt', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error(text || `HTTP Error ${res.status}`);
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || 'Receipt Vouchers committed successfully!');
                    window.location.href = '?action=list';
                } else {
                    alert('Error: ' + (data.message || 'Failed to save vouchers.'));
                    commitBtn.disabled = false;
                    commitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Commit All Receipt Vouchers';
                }
            })
            .catch(err => {
                console.error('Commit error:', err);
                alert('Server Error: ' + err.message);
                commitBtn.disabled = false;
                commitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Commit All Receipt Vouchers';
            });
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>
</body>
</html>
