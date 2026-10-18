<?php
/**
 * @var \App\Modules\Admin\AA_ES\DTO\AA_ES_DTO $aa_es
 * @var string|null $message
 * @var string|null $error
 */
$boq = is_array($aa_es->boq) ? $aa_es->boq : [];
$estimatedTotal = (float)$aa_es->estimated_cost;
$justifiedTotal = (float)$aa_es->justified_amount;
if ($justifiedTotal <= 0) {
    $justifiedTotal = $estimatedTotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Justification — <?= htmlspecialchars($aa_es->alias) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 25px 0; }
        .page-wrapper { max-width: 1400px; margin: auto; padding: 0 20px; }
        .card-main { background: #fff; border-radius: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); padding: 25px; border: none; }
        table.justification-table th { background: #1f2937; color: #fff; font-size: 0.85rem; vertical-align: middle; }
        table.justification-table td { vertical-align: middle; font-size: 0.88rem; }
        .sticky-summary { position: sticky; bottom: 0; background: #fff; border-top: 2px solid #dee2e6; box-shadow: 0 -4px 10px rgba(0,0,0,0.05); padding: 15px; margin: 0 -25px -25px -25px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; z-index: 100; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="card-main">
        <!-- Top Nav & Header -->
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
            <div>
                <a href="?action=view&id=<?= $aa_es->id ?>" class="btn btn-sm btn-outline-secondary mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to AA & ES Details
                </a>
                <h1 class="h3 fw-bold text-dark mb-0">
                    <i class="bi bi-clipboard2-check text-primary me-2"></i>Rate Justification & Analysis
                </h1>
                <div class="text-muted small mt-1">
                    AA & ES: <strong class="text-dark"><?= htmlspecialchars($aa_es->alias) ?></strong> | 
                    Scope: <?= htmlspecialchars(mb_strimwidth($aa_es->sub_head, 0, 80, '...')) ?> | 
                    Type: <span class="badge bg-primary"><?= htmlspecialchars($aa_es->type) ?></span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="text-muted small">Sanctioned Estimate:</div>
                    <div class="fs-5 fw-bold font-monospace text-secondary">₹<?= number_format($estimatedTotal, 2) ?></div>
                </div>
                <div class="text-end border-start ps-3">
                    <div class="text-muted small">Justified Amount:</div>
                    <div class="fs-4 fw-bold font-monospace text-success" id="headerJustifiedAmount">₹<?= number_format($justifiedTotal, 2) ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="?action=saveJustification" method="POST" id="justificationForm">
            <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="id" value="<?= $aa_es->id ?>">

            <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                <i class="bi bi-info-circle fs-5 me-2 text-primary"></i>
                <div>
                    Click <strong>"Analyze Rate"</strong> on any DSR item to load its constituents from the CPWD DAR database and market rates from <code>civil_global_rates</code>. Modify any constituent rates or quantities to calculate the justified rate. For Market Items (MR), adjust rates directly.
                </div>
            </div>

            <!-- BoQ Justification Table -->
            <div class="table-responsive border rounded-3 mb-4">
                <table class="table table-bordered table-hover justification-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 120px;">Item Code</th>
                            <th>Item Description (Nomenclature)</th>
                            <th style="width: 80px;" class="text-center">Unit</th>
                            <th style="width: 90px;" class="text-end">Qty</th>
                            <th style="width: 110px;" class="text-end">Estimate Rate</th>
                            <th style="width: 120px;" class="text-end">Estimate Total</th>
                            <th style="width: 140px;" class="text-end bg-success bg-opacity-25 text-dark fw-bold">Justified Rate (₹)</th>
                            <th style="width: 140px;" class="text-end bg-success bg-opacity-25 text-dark fw-bold">Justified Total (₹)</th>
                            <th style="width: 110px;" class="text-center">Rate Analysis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalEstimateCalc = 0.0;
                        $totalJustifiedCalc = 0.0;
                        foreach ($boq as $i => $item): 
                            $qty = (float)($item['qty'] ?? 1);
                            $estRate = (float)($item['rate'] ?? 0);
                            $estAmount = round($qty * $estRate, 2);
                            $justRate = isset($item['justified_rate']) ? (float)$item['justified_rate'] : $estRate;
                            $justAmount = round($qty * $justRate, 2);
                            
                            $totalEstimateCalc += $estAmount;
                            $totalJustifiedCalc += $justAmount;

                            $isMR = str_starts_with($item['item_code'] ?? '', 'MR');
                            $hasAnalysis = !empty($item['analysis_breakup']);
                        ?>
                            <tr id="just-row-<?= $i ?>" class="<?= $hasAnalysis ? 'table-info bg-opacity-10' : '' ?>">
                                <td class="text-center text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <span class="font-monospace fw-bold <?= $isMR ? 'text-warning' : 'text-primary' ?>">
                                        <?= htmlspecialchars($item['item_code'] ?? '') ?>
                                    </span>
                                    <?php if ($isMR): ?>
                                        <span class="badge bg-warning text-dark d-block mt-1" style="font-size: 0.65rem;">Market Item</span>
                                    <?php elseif ($hasAnalysis): ?>
                                        <span class="badge bg-info text-dark d-block mt-1" style="font-size: 0.65rem;">Analyzed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= nl2br(htmlspecialchars($item['description'] ?? '')) ?></div>
                                    <input type="hidden" name="items[<?= $i ?>][analysis_breakup]" id="analysis-json-<?= $i ?>" value="<?= htmlspecialchars(json_encode($item['analysis_breakup'] ?? null)) ?>">
                                </td>
                                <td class="text-center font-monospace small"><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                <td class="text-end font-monospace" id="qty-<?= $i ?>" data-qty="<?= $qty ?>"><?= number_format($qty, 2) ?></td>
                                <td class="text-end font-monospace text-muted">₹<?= number_format($estRate, 2) ?></td>
                                <td class="text-end font-monospace text-muted">₹<?= number_format($estAmount, 2) ?></td>
                                <td class="text-end bg-success bg-opacity-10">
                                    <input type="number" step="0.01" min="0" 
                                           name="items[<?= $i ?>][justified_rate]" 
                                           id="just-rate-<?= $i ?>" 
                                           class="form-control form-control-sm text-end font-monospace fw-bold border-success" 
                                           value="<?= number_format($justRate, 2, '.', '') ?>" 
                                           oninput="recalcJustRow(<?= $i ?>)" required>
                                </td>
                                <td class="text-end font-monospace fw-bold text-success bg-success bg-opacity-10" id="just-amount-<?= $i ?>">
                                    ₹<?= number_format($justAmount, 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!$isMR): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openDarAnalysisModal(<?= $i ?>, '<?= htmlspecialchars($item['item_code'] ?? '') ?>')" title="Analyze / adjust DAR breakdown">
                                            <i class="bi bi-sliders me-1"></i> Analyze
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Direct MR</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sticky Summary Bar -->
            <div class="sticky-summary">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <span class="text-muted small">Total Sanctioned Estimate:</span>
                            <div class="fs-6 fw-bold font-monospace text-secondary">₹<?= number_format($totalEstimateCalc, 2) ?></div>
                        </div>
                        <div class="border-start ps-3">
                            <span class="text-muted small">Total Justified Amount:</span>
                            <div class="fs-4 fw-bold font-monospace text-success" id="footerJustifiedTotal">₹<?= number_format($totalJustifiedCalc, 2) ?></div>
                        </div>
                        <div class="border-start ps-3">
                            <span class="text-muted small">Variation / Percentage:</span>
                            <div class="fs-6 fw-bold font-monospace" id="variationBadge">0.00%</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?action=view&id=<?= $aa_es->id ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Justified Rates & Amount
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- DAR Rate Analysis & Customizer Modal -->
<div class="modal fade" id="darAnalysisModal" tabindex="-1" aria-labelledby="darAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title fs-6 fw-bold" id="darAnalysisModalLabel">
                    <i class="bi bi-calculator me-1 text-warning"></i> CPWD DAR Rate Analysis: <span id="modalItemCode" class="font-monospace text-warning"></span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning text-warning py-0 px-2" style="font-size: 0.75rem;" onclick="pullFreshGlobalRates(document.getElementById('modalItemCode').textContent.trim())" title="Pull latest rates from civil_global_rates">
                        <i class="bi bi-arrow-repeat me-1"></i> Pull Fresh Rates
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-9">
                        <label class="form-label small fw-bold mb-1">Item Description / Nomenclature:</label>
                        <div id="modalItemDesc" class="p-2 border rounded bg-light small text-dark" style="max-height: 80px; overflow-y: auto;"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Output / Cost Basis:</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="modalCostBasisQty" class="form-control text-center font-monospace" value="1" step="0.01" oninput="recalcModalAnalysis()">
                            <span class="input-group-text font-monospace" id="modalCostBasisUnit">cum</span>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Derived unit: 1 <span id="modalUnitLabel">cum</span></small>
                    </div>
                </div>

                <!-- Components Table -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-dark"><i class="bi bi-stack me-1"></i> Constituent Resources (Loaded from DAR 2023 & Global Rates)</span>
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
                                <th style="width: 120px;" class="text-end">Market Rate (₹)</th>
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
                            <span class="text-success fw-bold">Derived Justified Rate:</span>
                            <div class="fs-4 fw-bold font-monospace text-success" id="calcDerivedRate">₹0.00</div>
                            <small class="text-muted">per <span id="calcUnitBasis">cum</span></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-3" onclick="applyModalJustifiedRate()">
                    <i class="bi bi-check-lg me-1"></i> Apply Justified Rate
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let activeRowIndex = null;
let currentModalComponents = [];
let darModalInstance = null;
const estimatedGrandTotal = <?= (float)$totalEstimateCalc ?>;

function recalcJustRow(idx) {
    const qtyElem = document.getElementById(`qty-${idx}`);
    if (!qtyElem) return;

    const qty = parseFloat(qtyElem.getAttribute('data-qty')) || 0;
    const rate = parseFloat(document.getElementById(`just-rate-${idx}`).value) || 0;
    const lineTotal = qty * rate;

    document.getElementById(`just-amount-${idx}`).textContent = '₹' + lineTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    recalcAllJustification();
}

function recalcAllJustification() {
    let grandJustified = 0;
    const rows = document.querySelectorAll('tr[id^="just-row-"]');

    rows.forEach((row, i) => {
        const qtyElem = document.getElementById(`qty-${i}`);
        if (!qtyElem) return;
        const qty = parseFloat(qtyElem.getAttribute('data-qty')) || 0;
        const rate = parseFloat(document.getElementById(`just-rate-${i}`).value) || 0;
        grandJustified += (qty * rate);
    });

    const formatted = '₹' + grandJustified.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('headerJustifiedAmount').textContent = formatted;
    document.getElementById('footerJustifiedTotal').textContent = formatted;

    // Variation calculation
    const diff = grandJustified - estimatedGrandTotal;
    const pct = estimatedGrandTotal > 0 ? ((diff / estimatedGrandTotal) * 100) : 0;
    const varBadge = document.getElementById('variationBadge');
    
    if (diff > 0) {
        varBadge.className = 'fs-6 fw-bold font-monospace text-danger';
        varBadge.textContent = `+${pct.toFixed(2)}% (+₹${diff.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
    } else if (diff < 0) {
        varBadge.className = 'fs-6 fw-bold font-monospace text-success';
        varBadge.textContent = `${pct.toFixed(2)}% (-₹${Math.abs(diff).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
    } else {
        varBadge.className = 'fs-6 fw-bold font-monospace text-muted';
        varBadge.textContent = '0.00% (At Par)';
    }
}

// -------------------------------------------------------------
// Modal DAR Analysis
// -------------------------------------------------------------
function openDarAnalysisModal(rowIdx, rawItemCode) {
    activeRowIndex = rowIdx;
    const baseCode = rawItemCode.replace(/\s*\(Analyzed\)/i, '').trim();

    if (!darModalInstance) {
        darModalInstance = new bootstrap.Modal(document.getElementById('darAnalysisModal'));
    }

    document.getElementById('modalItemCode').textContent = baseCode;
    document.getElementById('modalComponentsBody').innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading DAR breakdown & market rates...</td></tr>';
    darModalInstance.show();

    // Check if hardcoded analysis snapshot exists on this row
    const savedJson = document.getElementById(`analysis-json-${rowIdx}`).value;
    let savedAnalysis = null;
    try {
        if (savedJson && savedJson !== 'null') {
            savedAnalysis = JSON.parse(savedJson);
        }
    } catch(e) {}

    if (savedAnalysis && Array.isArray(savedAnalysis.components) && savedAnalysis.components.length > 0) {
        // Load directly from the hardcoded snapshot in BoQ
        const row = document.getElementById(`just-row-${rowIdx}`);
        const rowDesc = row ? row.querySelector('.fw-semibold').textContent.trim() : (savedAnalysis.description || '');
        const rowUnit = row ? row.querySelector('td:nth-child(4)').textContent.trim() : (savedAnalysis.cost_basis_unit || 'cum');

        document.getElementById('modalItemDesc').textContent = rowDesc;
        document.getElementById('modalCostBasisQty').value = savedAnalysis.cost_basis_qty || 1.0;
        document.getElementById('modalCostBasisUnit').textContent = savedAnalysis.cost_basis_unit || rowUnit;
        document.getElementById('modalUnitLabel').textContent = rowUnit;
        document.getElementById('calcUnitBasis').textContent = rowUnit;
        document.getElementById('calcFactor').value = parseFloat(savedAnalysis.dar_factor) || 1.4085;

        currentModalComponents = JSON.parse(JSON.stringify(savedAnalysis.components));
        renderModalComponents();
        recalcModalAnalysis();
        darModalInstance.show();
        return;
    }

    pullFreshGlobalRates(baseCode);
}

function pullFreshGlobalRates(baseCode) {
    document.getElementById('modalComponentsBody').innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading DAR breakdown & market rates...</td></tr>';
    if (darModalInstance) darModalInstance.show();

    fetch(`?action=getDarAnalysis&item_code=${encodeURIComponent(baseCode)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Could not fetch DAR breakdown: ' + data.error);
                darModalInstance.hide();
                return;
            }

            document.getElementById('modalItemDesc').textContent = data.description || '';
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
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3 text-muted">No constituents found. Use "+ Add Constituent" to customize.</td></tr>';
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
        description: 'New Constituent Item',
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

    const sumX = sumW * 1.01;
    const factor = parseFloat(document.getElementById('calcFactor').value) || 1.4085;
    const basisQty = parseFloat(document.getElementById('modalCostBasisQty').value) || 1.0;

    const analyzedBasisCost = sumW * factor;
    const derivedUnitRate = basisQty > 0 ? (analyzedBasisCost / basisQty) : analyzedBasisCost;

    document.getElementById('calcW').textContent = '₹' + sumW.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('calcX').textContent = '₹' + sumX.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('calcDerivedRate').textContent = '₹' + derivedUnitRate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    return derivedUnitRate;
}

function applyModalJustifiedRate() {
    const derivedRate = recalcModalAnalysis();
    if (activeRowIndex === null) return;

    // Update input in table
    const rateInput = document.getElementById(`just-rate-${activeRowIndex}`);
    if (rateInput) {
        rateInput.value = derivedRate.toFixed(2);
        recalcJustRow(activeRowIndex);
    }

    const sumW = currentModalComponents.reduce((acc, c) => acc + ((parseFloat(c.quantity) || 0) * (parseFloat(c.market_rate ?? c.standard_rate) || 0)), 0);
    const sumX = sumW * 1.01;

    const payload = {
        base_item_code: baseCode,
        justified_at: new Date().toISOString(),
        cost_basis_qty: parseFloat(document.getElementById('modalCostBasisQty').value) || 1.0,
        cost_basis_unit: document.getElementById('modalCostBasisUnit').textContent.trim(),
        basic_sum_w: parseFloat(sumW.toFixed(2)),
        water_charges_x: parseFloat(sumX.toFixed(2)),
        dar_factor: parseFloat(document.getElementById('calcFactor').value) || 1.4085,
        calculated_rate: derivedRate,
        components: currentModalComponents
    };

    const hiddenJson = document.getElementById(`analysis-json-${activeRowIndex}`);
    if (hiddenJson) {
        hiddenJson.value = JSON.stringify(payload);
    }

    // Mark row visual state
    const row = document.getElementById(`just-row-${activeRowIndex}`);
    if (row) {
        row.classList.add('table-info');
    }

    if (darModalInstance) darModalInstance.hide();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// Initial calculation on page load
recalcAllJustification();
</script>

</body>
</html>
