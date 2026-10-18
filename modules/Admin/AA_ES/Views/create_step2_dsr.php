<?php
/**
 * @var array $data Previous session data.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create AA & ES (DSR Civil Works) - Step 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f7f6; }
        .page-wrapper { max-width: 1400px; margin: 30px auto; padding: 0 15px; }
        .card-main { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: none; }
        .step-indicator { text-align: center; margin-bottom: 25px; color: #777; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }

        .search-results-dropdown {
            position: absolute;
            z-index: 1050;
            width: 100%;
            max-height: 280px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            display: none;
        }
        .search-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f5;
            transition: background 0.15s;
        }
        .search-item:hover, .search-item.active {
            background-color: #e9ecef;
        }
        .search-item:last-child {
            border-bottom: none;
        }

        table.boq-table th { background-color: #212529; color: #fff; font-weight: 600; font-size: 0.88rem; vertical-align: middle; }
        table.boq-table td { vertical-align: middle; padding: 8px; }
        .summary-card { position: sticky; top: 20px; border-radius: 8px; border: 1px solid #e9ecef; background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="card-main">
        <h1 class="h3 text-center mb-1 fw-bold text-dark">Create AA & ES — Civil Works (DSR 2023)</h1>
        <div class="step-indicator">Step 2 of 3: Prepare Bill of Quantities (BoQ)</div>

        <form action="?action=create" method="POST" id="dsrBoqForm">
            <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="step" value="2">

            <div class="row g-4">
                <!-- Main BoQ Area -->
                <div class="col-lg-8 col-xl-9">
                    <!-- Live Item Search Bar -->
                    <div class="card bg-light border-0 p-3 mb-4 rounded-3 position-relative">
                        <label class="form-label fw-bold mb-1 text-dark">
                            <i class="bi bi-search text-primary me-1"></i> Search DSR 2023 Items by Code or Description:
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="dsrSearchInput" class="form-control border-start-0 ps-0" 
                                   placeholder="Type e.g. 'cc', 'earth work', 'brick work', 'mortar', '4.1.3'..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">Clear</button>
                        </div>
                        <div id="searchResultsDropdown" class="search-results-dropdown"></div>
                        <small class="text-muted mt-1">
                            <i class="bi bi-info-circle me-1"></i>Type any keyword or code above, then click an item to add it directly to the BoQ table with rate and unit.
                        </small>
                    </div>

                    <!-- BoQ Table -->
                    <div class="card border rounded-3 overflow-hidden mb-3">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="fw-bold text-dark"><i class="bi bi-table me-1"></i> BoQ Schedule of Items</span>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold" onclick="addMarketItem()">
                                    <i class="bi bi-plus-circle text-warning me-1"></i> + Add Market Item (MR)
                                </button>
                                <span class="badge bg-secondary font-monospace" id="itemCountBadge">0 Items</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover boq-table mb-0" id="boqTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th style="width: 120px;">Item Code</th>
                                        <th>Item Description (Nomenclature)</th>
                                        <th style="width: 90px;" class="text-center">Unit</th>
                                        <th style="width: 120px;" class="text-end">Rate (₹)</th>
                                        <th style="width: 100px;" class="text-end">Qty</th>
                                        <th style="width: 130px;" class="text-end">Amount (₹)</th>
                                        <th style="width: 90px;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="boqBody">
                                    <tr id="emptyRowPlaceholder">
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                            No items added to this BoQ yet.<br>
                                            <small>Use the search bar above to find DSR items, or click <strong>+ Add Market Item</strong> to add non-schedule items.</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="?action=showCreateForm&step=1" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Step 1
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnNextStep" disabled>
                            Next Step: Review & Finalize <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- Sticky Summary Sidebar -->
                <div class="col-lg-4 col-xl-3">
                    <div class="card summary-card p-3 shadow-sm">
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="bi bi-calculator me-1"></i> Estimate Summary
                        </h5>

                        <div class="mb-3">
                            <div class="text-muted small">Sub-Head (Work Scope):</div>
                            <div class="small fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($data['sub_head'] ?? '') ?>">
                                <?= htmlspecialchars(mb_strimwidth($data['sub_head'] ?? '', 0, 80, '...')) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Schedule Basis:</div>
                            <span class="badge bg-primary">CPWD DSR 2023 & Market Items</span>
                        </div>

                        <ul class="list-group list-group-flush mb-3 rounded-2 border">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 small">
                                <span class="text-muted">Total Line Items:</span>
                                <span class="fw-bold font-monospace" id="summaryTotalItems">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-3 bg-success bg-opacity-10">
                                <div>
                                    <div class="fw-bold text-success">Total Estimate:</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">(AA & ES Amount)</small>
                                </div>
                                <span class="fs-5 fw-bold font-monospace text-success" id="summaryTotalAmount">₹0.00</span>
                            </li>
                        </ul>

                        <div class="alert alert-info py-2 px-3 small mb-0">
                            <i class="bi bi-info-circle me-1"></i> The total calculated amount from this BoQ will form the sanctioned <strong>AA & ES Amount</strong> for budget verification in Step 3.
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- DAR Rate Analysis & Customizer Modal -->
<div class="modal fade" id="darAnalysisModal" tabindex="-1" aria-labelledby="darAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title fs-6 fw-bold" id="darAnalysisModalLabel">
                    <i class="bi bi-calculator me-1 text-warning"></i> CPWD DAR Rate Analysis & Customizer: <span id="modalItemCode" class="font-monospace text-warning"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-9">
                        <label class="form-label small fw-bold mb-1">Item Description / Nomenclature (Editable):</label>
                        <textarea id="modalItemDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Output / Cost Basis:</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="modalCostBasisQty" class="form-control text-center font-monospace" value="1" step="0.01" oninput="recalcModalAnalysis()">
                            <span class="input-group-text font-monospace" id="modalCostBasisUnit">cum</span>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Rate will be derived per 1 <span id="modalUnitLabel">cum</span></small>
                    </div>
                </div>

                <!-- Components Table -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-dark"><i class="bi bi-stack me-1"></i> Constituent Resources (Materials, Labour & Machinery)</span>
                    <button type="button" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" onclick="addModalComponentRow()">
                        <i class="bi bi-plus me-1"></i> + Add Constituent
                    </button>
                </div>
                <div class="table-responsive border rounded-2 mb-3">
                    <table class="table table-sm table-bordered table-hover mb-0" id="modalComponentsTable">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 90px;">Category</th>
                                <th style="width: 80px;">Res Code</th>
                                <th>Resource Description</th>
                                <th style="width: 80px;" class="text-center">Unit</th>
                                <th style="width: 100px;" class="text-end">Quantity</th>
                                <th style="width: 120px;" class="text-end">Rate (₹)</th>
                                <th style="width: 120px;" class="text-end">Amount (₹)</th>
                                <th style="width: 40px;" class="text-center"><i class="bi bi-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="modalComponentsBody"></tbody>
                    </table>
                </div>

                <!-- Arithmetic Calculation Breakdown Card -->
                <div class="card bg-light border p-3">
                    <div class="row g-2 align-items-center small">
                        <div class="col-md-3">
                            <span class="text-muted">Basic Components Sum (W):</span>
                            <div class="fw-bold font-monospace fs-6" id="calcW">₹0.00</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">With Water Charges (+1%):</span>
                            <div class="fw-semibold font-monospace" id="calcX">₹0.00</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">DAR Factor (CPOH + Taxes):</span>
                            <div class="input-group input-group-sm mt-1" style="max-width: 140px;">
                                <input type="number" step="0.0001" id="calcFactor" class="form-control text-center font-monospace" value="1.4085" oninput="recalcModalAnalysis()">
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <span class="text-success fw-bold">Derived Unit Rate:</span>
                            <div class="fs-4 fw-bold font-monospace text-success" id="calcDerivedRate">₹0.00</div>
                            <small class="text-muted">per <span id="calcUnitBasis">cum</span></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-3" onclick="applyModalAnalysisToBoQ()">
                    <i class="bi bi-check-lg me-1"></i> Apply & Put Rate onto BoQ
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let rowCounter = 0;
let searchTimeout = null;
const searchResultsCache = {};

const searchInput = document.getElementById('dsrSearchInput');
const dropdown = document.getElementById('searchResultsDropdown');
const boqBody = document.getElementById('boqBody');
const emptyPlaceholder = document.getElementById('emptyRowPlaceholder');
const btnNextStep = document.getElementById('btnNextStep');

// Debounced Live DSR Search
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`?action=searchDsr&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(items => {
                if (!items || items.length === 0) {
                    dropdown.innerHTML = '<div class="p-3 text-muted text-center small"><i class="bi bi-search me-1"></i>No matching DSR items found</div>';
                    dropdown.style.display = 'block';
                    return;
                }

                let html = '';
                items.forEach(item => {
                    searchResultsCache[item.item_code] = item;
                    const escapedCode = escapeHtml(item.item_code);
                    const escapedDesc = escapeHtml(item.description);
                    const escapedUnit = escapeHtml(item.unit);
                    const rate = parseFloat(item.rate) || 0;

                    html += `
                        <div class="search-item d-flex justify-content-between align-items-center">
                            <div class="me-2 flex-grow-1" style="cursor: pointer;" onclick="onSelectDsrItem('${item.item_code}')">
                                <span class="badge bg-secondary font-monospace me-1">${escapedCode}</span>
                                <span class="small fw-semibold text-dark">${escapedDesc.substring(0, 85)}${escapedDesc.length > 85 ? '...' : ''}</span>
                                <div class="text-muted" style="font-size: 0.72rem;">Sub-head ${item.sub_head_no}: ${escapeHtml(item.sub_head_name)}</div>
                            </div>
                            <div class="text-end text-nowrap ms-2 d-flex align-items-center gap-2">
                                <div>
                                    <span class="fw-bold font-monospace text-success">₹${rate.toFixed(2)}</span>
                                    <span class="badge bg-light text-dark border">/ ${escapedUnit}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary py-1 px-2" onclick="onSelectDsrItem('${item.item_code}')" title="Add directly with standard rate">
                                    + Add
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info py-1 px-2" onclick="openDarAnalysis('${item.item_code}')" title="Customize constituent rates/quantities">
                                    <i class="bi bi-sliders"></i> Analyze
                                </button>
                            </div>
                        </div>
                    `;
                });
                dropdown.innerHTML = html;
                dropdown.style.display = 'block';
            })
            .catch(err => {
                console.error(err);
            });
    }, 250);
});

function onSelectDsrItem(code) {
    const item = searchResultsCache[code];
    if (item) {
        addItemToBoQ(item.item_code, item.description, item.unit, parseFloat(item.rate) || 0, 1.0);
    }
}

// Close dropdown on click outside
document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && e.target !== searchInput) {
        dropdown.style.display = 'none';
    }
});

function clearSearch() {
    searchInput.value = '';
    dropdown.style.display = 'none';
    dropdown.innerHTML = '';
}

function addMarketItem() {
    const placeholder = document.getElementById('emptyRowPlaceholder');
    if (placeholder) placeholder.remove();

    const idx = rowCounter++;
    const tr = document.createElement('tr');
    tr.id = `row-${idx}`;
    tr.className = 'table-warning bg-opacity-10';

    tr.innerHTML = `
        <td class="text-center text-muted small row-num"></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-warning text-dark fw-bold" style="font-size: 0.7rem;">MR</span>
                <input type="text" name="items[${idx}][item_code]" class="form-control form-control-sm font-monospace fw-bold" value="MR-${idx + 1}" required>
            </div>
            <input type="hidden" name="items[${idx}][is_market_item]" value="1">
        </td>
        <td>
            <textarea name="items[${idx}][description]" class="form-control form-control-sm" rows="2" placeholder="Enter market rate item description, specifications, and scope..." required></textarea>
        </td>
        <td class="text-center">
            <select name="items[${idx}][unit]" class="form-select form-select-sm text-center">
                <option value="nos">nos</option>
                <option value="sqm">sqm</option>
                <option value="cum">cum</option>
                <option value="metre">metre</option>
                <option value="kg">kg</option>
                <option value="tonne">tonne</option>
                <option value="ls" selected>ls</option>
                <option value="job">job</option>
                <option value="set">set</option>
            </select>
        </td>
        <td class="text-end">
            <input type="number" step="0.01" min="0" name="items[${idx}][rate]" class="form-control form-control-sm text-end font-monospace item-rate" value="0.00" placeholder="0.00" required oninput="calculateRow(${idx})">
        </td>
        <td class="text-end">
            <input type="number" step="0.0001" min="0.0001" name="items[${idx}][qty]" class="form-control form-control-sm text-end font-monospace fw-bold item-qty" value="1.00" required oninput="calculateRow(${idx})">
        </td>
        <td class="text-end font-monospace fw-bold item-amount" id="amount-${idx}">
            ₹0.00
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(${idx})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    boqBody.appendChild(tr);
    recalculateAll();

    // Focus on description textarea
    tr.querySelector('textarea').focus();
}

function addItemToBoQ(code, description, unit, rate, defaultQty = 1.0, analysisBreakup = null) {
    const placeholder = document.getElementById('emptyRowPlaceholder');
    if (placeholder) placeholder.remove();

    const idx = rowCounter++;
    const tr = document.createElement('tr');
    tr.id = `row-${idx}`;
    const isAnalyzed = (analysisBreakup !== null || code.includes('Analyzed'));

    tr.innerHTML = `
        <td class="text-center text-muted small row-num"></td>
        <td>
            <input type="text" name="items[${idx}][item_code]" class="form-control form-control-sm font-monospace fw-bold ${isAnalyzed ? 'bg-info bg-opacity-10 text-primary border-primary' : 'bg-light'}" value="${code}" readonly>
            <input type="hidden" name="items[${idx}][analysis_breakup]" id="analysis-payload-${idx}" value="${analysisBreakup ? escapeAttr(JSON.stringify(analysisBreakup)) : ''}">
        </td>
        <td>
            <textarea name="items[${idx}][description]" class="form-control form-control-sm" rows="2" required>${description}</textarea>
        </td>
        <td class="text-center">
            <input type="text" name="items[${idx}][unit]" class="form-control form-control-sm text-center bg-light" value="${unit}" readonly>
        </td>
        <td class="text-end">
            <input type="number" step="0.01" name="items[${idx}][rate]" class="form-control form-control-sm text-end font-monospace item-rate ${isAnalyzed ? 'fw-bold text-success' : ''}" value="${rate.toFixed(2)}" required oninput="calculateRow(${idx})">
        </td>
        <td class="text-end">
            <input type="number" step="0.0001" min="0.0001" name="items[${idx}][qty]" class="form-control form-control-sm text-end font-monospace fw-bold item-qty" value="${parseFloat(defaultQty).toFixed(2)}" required oninput="calculateRow(${idx})">
        </td>
        <td class="text-end font-monospace fw-bold item-amount" id="amount-${idx}">
            ₹${(rate * defaultQty).toFixed(2)}
        </td>
        <td class="text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-info p-1" onclick="openDarAnalysis('${code}', ${idx})" title="Analyze DAR constituents">
                    <i class="bi bi-sliders"></i>
                </button>
                <button type="button" class="btn btn-outline-danger p-1" onclick="removeRow(${idx})" title="Remove item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    `;

    boqBody.appendChild(tr);
    clearSearch();
    recalculateAll();

    // Focus on qty input for quick entry
    tr.querySelector('.item-qty').focus();
    tr.querySelector('.item-qty').select();
}

function removeRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (row) {
        row.remove();
        recalculateAll();
    }
}

function calculateRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (!row) return;

    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
    const amount = qty * rate;

    document.getElementById(`amount-${idx}`).textContent = '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    recalculateAll();
}

function recalculateAll() {
    let totalItems = 0;
    let grandTotal = 0;

    const rows = boqBody.querySelectorAll('tr[id^="row-"]');
    rows.forEach((row, i) => {
        totalItems++;
        row.querySelector('.row-num').textContent = i + 1;

        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
        grandTotal += (qty * rate);
    });

    document.getElementById('itemCountBadge').textContent = `${totalItems} Items`;
    document.getElementById('summaryTotalItems').textContent = totalItems;
    document.getElementById('summaryTotalAmount').textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Enable/disable next step
    btnNextStep.disabled = (totalItems === 0);

    if (totalItems === 0 && !document.getElementById('emptyRowPlaceholder')) {
        boqBody.innerHTML = `
            <tr id="emptyRowPlaceholder">
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    No items added to this BoQ yet.<br>
                    <small>Use the search bar above to find DSR items, or click <strong>+ Add Market Item</strong> to add non-schedule items.</small>
                </td>
            </tr>
        `;
    }
}

// -------------------------------------------------------------
// DAR Rate Analysis Modal Logic
// -------------------------------------------------------------
let currentModalTargetRowIdx = null;
let currentModalComponents = [];
let darModalInstance = null;

function openDarAnalysis(itemCode, targetRowIdx = null) {
    currentModalTargetRowIdx = targetRowIdx;
    
    // Clean item code of suffixes like (Analyzed)
    const baseCode = itemCode.replace(/\s*\(Analyzed\)/i, '').trim();

    if (!darModalInstance) {
        darModalInstance = new bootstrap.Modal(document.getElementById('darAnalysisModal'));
    }

    document.getElementById('modalItemCode').textContent = baseCode;

    // Check if target row already has a hardcoded snapshot attached
    let existingSnapshot = null;
    if (targetRowIdx !== null) {
        const payloadInput = document.getElementById(`analysis-payload-${targetRowIdx}`);
        if (payloadInput && payloadInput.value) {
            try {
                existingSnapshot = JSON.parse(payloadInput.value);
            } catch(e) {}
        }
    }

    if (existingSnapshot && Array.isArray(existingSnapshot.components) && existingSnapshot.components.length > 0) {
        // Load directly from the immutable/hardcoded snapshot
        const targetRow = document.getElementById(`row-${targetRowIdx}`);
        const currentDesc = targetRow ? targetRow.querySelector('textarea[name$="[description]"]').value : (existingSnapshot.description || '');
        const currentUnit = targetRow ? targetRow.querySelector('input[name$="[unit]"]').value : (existingSnapshot.cost_basis_unit || 'cum');

        document.getElementById('modalItemDesc').value = currentDesc;
        document.getElementById('modalCostBasisQty').value = existingSnapshot.cost_basis_qty || 1.0;
        document.getElementById('modalCostBasisUnit').textContent = existingSnapshot.cost_basis_unit || currentUnit;
        document.getElementById('modalUnitLabel').textContent = currentUnit;
        document.getElementById('calcUnitBasis').textContent = currentUnit;
        document.getElementById('calcFactor').value = parseFloat(existingSnapshot.dar_factor) || 1.4085;

        currentModalComponents = JSON.parse(JSON.stringify(existingSnapshot.components));
        renderModalComponents();
        recalcModalAnalysis();
        darModalInstance.show();
        return;
    }

    document.getElementById('modalComponentsBody').innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading DAR breakdown & market rates...</td></tr>';
    darModalInstance.show();

    fetch(`?action=getDarAnalysis&item_code=${encodeURIComponent(baseCode)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Could not fetch DAR breakdown: ' + data.error);
                darModalInstance.hide();
                return;
            }

            document.getElementById('modalItemDesc').value = data.description || '';
            document.getElementById('modalCostBasisQty').value = data.cost_basis_qty || 1.0;
            document.getElementById('modalCostBasisUnit').textContent = data.cost_basis_unit || 'cum';
            document.getElementById('modalUnitLabel').textContent = data.unit || data.cost_basis_unit || 'cum';
            document.getElementById('calcUnitBasis').textContent = data.unit || data.cost_basis_unit || 'cum';
            document.getElementById('calcFactor').value = parseFloat(data.markup_factor) || 1.4085;

            currentModalComponents = data.components || [];
            renderModalComponents();
            recalcModalAnalysis();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load DAR breakdown.');
            darModalInstance.hide();
        });
}

function renderModalComponents() {
    const tbody = document.getElementById('modalComponentsBody');
    tbody.innerHTML = '';

    if (currentModalComponents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3 text-muted">No constituents found. Use "+ Add Constituent" to build your custom rate.</td></tr>';
        return;
    }

    currentModalComponents.forEach((comp, idx) => {
        const tr = document.createElement('tr');
        const qty = parseFloat(comp.quantity) || 0;
        const rate = parseFloat(comp.market_rate ?? comp.standard_rate) || 0;
        const amount = qty * rate;

        tr.innerHTML = `
            <td class="text-center text-muted small">${idx + 1}</td>
            <td>
                <select class="form-select form-select-sm py-0 comp-category" onchange="updateModalComp(${idx}, 'category', this.value)">
                    <option value="MATERIAL" ${comp.category === 'MATERIAL' ? 'selected' : ''}>Material</option>
                    <option value="LABOUR" ${comp.category === 'LABOUR' ? 'selected' : ''}>Labour</option>
                    <option value="MACHINERY" ${comp.category === 'MACHINERY' ? 'selected' : ''}>Machinery</option>
                    <option value="OTHER" ${comp.category === 'OTHER' ? 'selected' : ''}>Other</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm font-monospace p-1" value="${escapeHtml(comp.resource_code || '')}" onchange="updateModalComp(${idx}, 'resource_code', this.value)">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm p-1" value="${escapeHtml(comp.description || '')}" onchange="updateModalComp(${idx}, 'description', this.value)">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-center p-1" value="${escapeHtml(comp.unit || '')}" onchange="updateModalComp(${idx}, 'unit', this.value)">
            </td>
            <td class="text-end">
                <input type="number" step="0.0001" class="form-control form-control-sm text-end font-monospace p-1" value="${qty}" oninput="updateModalComp(${idx}, 'quantity', this.value)">
            </td>
            <td class="text-end">
                <input type="number" step="0.01" class="form-control form-control-sm text-end font-monospace p-1" value="${rate.toFixed(2)}" oninput="updateModalComp(${idx}, 'market_rate', this.value)">
            </td>
            <td class="text-end font-monospace fw-bold" id="modal-comp-amt-${idx}">
                ₹${amount.toFixed(2)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1" onclick="removeModalComponentRow(${idx})">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function updateModalComp(idx, field, value) {
    if (!currentModalComponents[idx]) return;
    if (field === 'quantity' || field === 'market_rate') {
        currentModalComponents[idx][field] = parseFloat(value) || 0;
        const qty = parseFloat(currentModalComponents[idx]['quantity']) || 0;
        const rate = parseFloat(currentModalComponents[idx]['market_rate']) || 0;
        const amt = qty * rate;
        currentModalComponents[idx]['amount'] = amt;
        const cell = document.getElementById(`modal-comp-amt-${idx}`);
        if (cell) cell.textContent = '₹' + amt.toFixed(2);
    } else {
        currentModalComponents[idx][field] = value;
    }
    recalcModalAnalysis();
}

function addModalComponentRow() {
    currentModalComponents.push({
        resource_code: '',
        category: 'MATERIAL',
        description: 'New Component Resource',
        unit: 'cum',
        quantity: 1.0,
        standard_rate: 0.0,
        market_rate: 0.0,
        amount: 0.0
    });
    renderModalComponents();
    recalcModalAnalysis();
}

function removeModalComponentRow(idx) {
    currentModalComponents.splice(idx, 1);
    renderModalComponents();
    recalcModalAnalysis();
}

function recalcModalAnalysis() {
    let sumW = 0;
    currentModalComponents.forEach(c => {
        const q = parseFloat(c.quantity) || 0;
        const r = parseFloat(c.market_rate ?? c.standard_rate) || 0;
        sumW += (q * r);
    });

    const sumX = sumW * 1.01; // +1% Water Charges
    const factor = parseFloat(document.getElementById('calcFactor').value) || 1.4085;
    const basisQty = parseFloat(document.getElementById('modalCostBasisQty').value) || 1.0;

    // Derived Rate = (Basic Sum * Composite DAR Factor) / Cost Basis Quantity
    const analyzedBasisCost = sumW * factor;
    const derivedUnitRate = basisQty > 0 ? (analyzedBasisCost / basisQty) : analyzedBasisCost;

    document.getElementById('calcW').textContent = '₹' + sumW.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('calcX').textContent = '₹' + sumX.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('calcDerivedRate').textContent = '₹' + derivedUnitRate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    return derivedUnitRate;
}

function applyModalAnalysisToBoQ() {
    const derivedRate = recalcModalAnalysis();
    const customDesc = document.getElementById('modalItemDesc').value.trim();
    const baseCode = document.getElementById('modalItemCode').textContent.trim();
    const analyzedCode = `${baseCode} (Analyzed)`;
    const basisUnit = document.getElementById('modalCostBasisUnit').textContent.trim();

    const analysisPayload = {
        base_item_code: baseCode,
        analyzed_at: new Date().toISOString(),
        cost_basis_qty: parseFloat(document.getElementById('modalCostBasisQty').value) || 1.0,
        cost_basis_unit: basisUnit,
        dar_factor: parseFloat(document.getElementById('calcFactor').value) || 1.4085,
        calculated_rate: derivedRate,
        components: currentModalComponents
    };

    if (currentModalTargetRowIdx !== null) {
        // Update existing row
        const row = document.getElementById(`row-${currentModalTargetRowIdx}`);
        if (row) {
            row.querySelector('input[name$="[item_code]"]').value = analyzedCode;
            row.querySelector('textarea[name$="[description]"]').value = customDesc;
            row.querySelector('input[name$="[unit]"]').value = basisUnit;
            row.querySelector('input[name$="[rate]"]').value = derivedRate.toFixed(2);
            document.getElementById(`analysis-payload-${currentModalTargetRowIdx}`).value = JSON.stringify(analysisPayload);
            calculateRow(currentModalTargetRowIdx);
        }
    } else {
        // Add as a new analyzed row
        addItemToBoQ(analyzedCode, customDesc, basisUnit, derivedRate, 1.0, analysisPayload);
    }

    if (darModalInstance) darModalInstance.hide();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function escapeAttr(text) {
    return (text || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

// Pre-load existing items from session if returning back to this step
const initialBoq = <?= json_encode($data['boq'] ?? []) ?>;
if (Array.isArray(initialBoq) && initialBoq.length > 0) {
    initialBoq.forEach(item => {
        addItemToBoQ(
            item.item_code || '', 
            item.description || '', 
            item.unit || '', 
            parseFloat(item.rate) || 0, 
            parseFloat(item.qty) || 1.0,
            item.analysis_breakup || null
        );
    });
}
</script>

</body>
</html>
