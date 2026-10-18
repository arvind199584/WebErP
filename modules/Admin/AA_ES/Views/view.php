<?php
/**
 * @var \App\Modules\Admin\AA_ES\DTO\AA_ES_DTO $aa_es The AA_ES object.
 */
$boq = is_array($aa_es->boq) ? $aa_es->boq : [];
$isDsr = ($aa_es->type === 'DSR');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AA & ES Details — <?= htmlspecialchars($aa_es->alias ?? 'View') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f7f6; padding: 30px 0; }
        .page-wrapper { max-width: 1350px; margin: auto; padding: 0 20px; }
        .card-main { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); padding: 30px; border: none; }
        .detail-label { font-weight: 600; width: 180px; color: #495057; font-size: 0.9rem; }
        .detail-value { flex: 1; color: #212529; font-size: 0.95rem; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f1f3f5; align-items: baseline; }
        .info-row:last-child { border-bottom: none; }
        
        table.boq-table th { background-color: #1f2937; color: #fff; font-size: 0.85rem; vertical-align: middle; }
        table.boq-table td { vertical-align: middle; font-size: 0.88rem; }
        .stat-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px 20px; }
        .stat-box.highlight { background: #e8f5e9; border-color: #c3e6cb; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="card-main">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom flex-wrap gap-2">
            <div>
                <a href="?action=list" class="btn btn-sm btn-outline-secondary mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to AA & ES List
                </a>
                <h1 class="h3 fw-bold text-dark mb-0">
                    <i class="bi bi-file-earmark-text text-primary me-2"></i>AA & ES: <?= htmlspecialchars($aa_es->alias) ?>
                </h1>
                <div class="text-muted small mt-1">
                    System ID: <span class="font-monospace">#<?= $aa_es->id ?></span> | 
                    Type: <span class="badge bg-primary"><?= htmlspecialchars($aa_es->type) ?></span> | 
                    Status: <span class="badge <?= $aa_es->status === 'Approved' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars($aa_es->status) ?></span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($isDsr): ?>
                    <a href="?action=showJustification&id=<?= $aa_es->id ?>" class="btn btn-primary fw-semibold">
                        <i class="bi bi-clipboard2-check me-1"></i> Rate Justification
                    </a>
                <?php endif; ?>
                <?php if ($aa_es->status === 'Approved' && $aa_es->approval_pdf): ?>
                    <a href="<?= htmlspecialchars($aa_es->approval_pdf) ?>" target="_blank" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Approval Order
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scope & Details -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card border h-100 p-3">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-info-circle me-1"></i> Sanction Metadata</h6>
                    <div class="info-row">
                        <span class="detail-label">Work Scope (Sub-Head):</span>
                        <span class="detail-value fw-semibold"><?= nl2br(htmlspecialchars($aa_es->sub_head)) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="detail-label">Schedule Basis:</span>
                        <span class="detail-value">
                            <?= $isDsr ? 'CPWD DSR 2023 & Hardcoded DAR Analysis' : htmlspecialchars($aa_es->type) ?>
                        </span>
                    </div>
                    <?php if ($aa_es->approved_at): ?>
                        <div class="info-row">
                            <span class="detail-label">Approved At:</span>
                            <span class="detail-value font-monospace"><?= htmlspecialchars($aa_es->approved_at) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="detail-label">Data Integrity:</span>
                        <span class="detail-value text-success small">
                            <i class="bi bi-shield-lock-fill me-1"></i> <strong>Hardcoded Snapshot</strong> — Rates and constituent analyses are frozen and immutable.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financials Summary Cards -->
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="stat-box">
                            <div class="text-muted small">Sanctioned Estimate Cost:</div>
                            <div class="fs-4 fw-bold font-monospace text-secondary">
                                ₹<?= number_format($aa_es->estimated_cost, 2) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="stat-box">
                            <div class="text-muted small">Justified Amount:</div>
                            <div class="fs-4 fw-bold font-monospace text-primary">
                                ₹<?= number_format($aa_es->justified_amount > 0 ? $aa_es->justified_amount : $aa_es->estimated_cost, 2) ?>
                            </div>
                            <?php if ($aa_es->justified_amount > 0 && $aa_es->estimated_cost > 0): 
                                $diff = $aa_es->justified_amount - $aa_es->estimated_cost;
                                $pct = ($diff / $aa_es->estimated_cost) * 100;
                            ?>
                                <small class="fw-semibold <?= $diff > 0 ? 'text-danger' : ($diff < 0 ? 'text-success' : 'text-muted') ?>">
                                    <?= $diff > 0 ? '+' : '' ?><?= number_format($pct, 2) ?>% vs estimate
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="stat-box highlight">
                            <div class="fw-bold text-success small">Total AA & ES Amount (Sanctioned):</div>
                            <div class="fs-3 fw-bold font-monospace text-success">
                                ₹<?= number_format($aa_es->aa_es_amount, 2) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BoQ Details Table -->
        <div class="card border rounded-3 overflow-hidden mb-3">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold text-dark"><i class="bi bi-table me-1"></i> Bill of Quantities (BoQ Schedule of Items)</span>
                <span class="badge bg-secondary font-monospace"><?= count($boq) ?> Items</span>
            </div>

            <?php if ($isDsr): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover boq-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 120px;">Item Code</th>
                                <th>Item Description (Nomenclature)</th>
                                <th style="width: 80px;" class="text-center">Unit</th>
                                <th style="width: 90px;" class="text-end">Qty</th>
                                <th style="width: 110px;" class="text-end">Est. Rate (₹)</th>
                                <th style="width: 120px;" class="text-end">Est. Total (₹)</th>
                                <th style="width: 110px;" class="text-end bg-success bg-opacity-10">Just. Rate (₹)</th>
                                <th style="width: 120px;" class="text-end bg-success bg-opacity-10">Just. Total (₹)</th>
                                <th style="width: 110px;" class="text-center">Rate Analysis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grandEst = 0;
                            $grandJust = 0;
                            foreach ($boq as $i => $item): 
                                $code = $item['item_code'] ?? '';
                                $qty = (float)($item['qty'] ?? 1);
                                $estRate = (float)($item['rate'] ?? 0);
                                $estAmt = (float)($item['amount'] ?? round($qty * $estRate, 2));
                                $justRate = (float)($item['justified_rate'] ?? $estRate);
                                $justAmt = (float)($item['justified_amount'] ?? round($qty * $justRate, 2));
                                $grandEst += $estAmt;
                                $grandJust += $justAmt;

                                $isMR = !empty($item['is_market_item']) || str_starts_with($code, 'MR');
                                $hasAnalysis = !empty($item['analysis_breakup']) || !empty($item['estimate_analysis_breakup']);
                                $isAnalyzed = $hasAnalysis && str_contains($code, 'Analyzed');
                            ?>
                                <tr>
                                    <td class="text-center text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <span class="font-monospace fw-bold <?= $isMR ? 'text-warning' : 'text-primary' ?>">
                                            <?= htmlspecialchars($code) ?>
                                        </span>
                                        <div class="mt-1">
                                            <?php if ($isMR): ?>
                                                <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Market Item</span>
                                            <?php elseif ($isAnalyzed): ?>
                                                <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Analyzed DAR</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">DSR 2023</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= nl2br(htmlspecialchars($item['description'] ?? '')) ?></div>
                                        <?php if (!empty($item['justification_remarks'])): ?>
                                            <div class="small text-muted mt-1 fst-italic">
                                                <strong>Remarks:</strong> <?= htmlspecialchars($item['justification_remarks']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace small"><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                    <td class="text-end font-monospace"><?= number_format($qty, 2) ?></td>
                                    <td class="text-end font-monospace text-muted">₹<?= number_format($estRate, 2) ?></td>
                                    <td class="text-end font-monospace text-muted">₹<?= number_format($estAmt, 2) ?></td>
                                    <td class="text-end font-monospace fw-bold text-success bg-success bg-opacity-10">
                                        ₹<?= number_format($justRate, 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success bg-success bg-opacity-10">
                                        ₹<?= number_format($justAmt, 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($hasAnalysis): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" style="font-size: 0.75rem;" onclick="viewRateAnalysisModal(<?= $i ?>)" title="View permanent constituent rate analysis">
                                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Breakdown
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold font-monospace">
                            <tr>
                                <td colspan="6" class="text-end text-dark">Grand Totals:</td>
                                <td class="text-end text-secondary">₹<?= number_format($grandEst, 2) ?></td>
                                <td></td>
                                <td class="text-end text-success">₹<?= number_format($grandJust, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-3">
                    <pre class="bg-light p-3 border rounded small"><?= htmlspecialchars(json_encode($aa_es->boq, JSON_PRETTY_PRINT)) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Read-Only Hardcoded Rate Analysis Inspector -->
<div class="modal fade" id="viewAnalysisModal" tabindex="-1" aria-labelledby="viewAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title fs-6 fw-bold" id="viewAnalysisModalLabel">
                    <i class="bi bi-shield-lock-fill text-warning me-1"></i> Hardcoded Rate Analysis Snapshot: <span id="inspectorItemCode" class="font-monospace text-warning"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-success py-2 px-3 small d-flex align-items-center mb-3">
                    <i class="bi bi-shield-check fs-4 me-2 text-success"></i>
                    <div>
                        <strong>Immutable Audit Record:</strong> This rate analysis is permanently hardcoded in the BoQ JSON database record. It was frozen on <span id="inspectorSnapshotTime" class="fw-bold font-monospace"></span> and will never drift or be affected by future rate revisions.
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-9">
                        <label class="form-label small fw-bold mb-1">Nomenclature / Item Description:</label>
                        <div id="inspectorDesc" class="p-2 border rounded bg-light small text-dark" style="max-height: 80px; overflow-y: auto;"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Output Cost Basis:</label>
                        <div class="p-2 border rounded bg-light small text-dark font-monospace text-center fw-bold">
                            <span id="inspectorCostBasisQty">1.0</span> <span id="inspectorCostBasisUnit">cum</span>
                        </div>
                    </div>
                </div>

                <!-- Analysis Type Switcher (if both estimate and justified analysis exist) -->
                <ul class="nav nav-pills nav-fill mb-3" id="analysisTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 fw-semibold" id="tab-estimate" data-bs-toggle="pill" type="button" onclick="switchAnalysisTab('estimate')">
                            <i class="bi bi-file-earmark-check me-1"></i> Estimate Rate Analysis (Baseline)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 fw-semibold" id="tab-justified" data-bs-toggle="pill" type="button" onclick="switchAnalysisTab('justified')">
                            <i class="bi bi-clipboard2-check me-1"></i> Justified Rate Analysis
                        </button>
                    </li>
                </ul>

                <!-- Constituents Table -->
                <div class="table-responsive border rounded-2 mb-3">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 90px;">Category</th>
                                <th style="width: 90px;">Res Code</th>
                                <th>Constituent Description</th>
                                <th style="width: 80px;" class="text-center">Unit</th>
                                <th style="width: 100px;" class="text-end">Quantity</th>
                                <th style="width: 120px;" class="text-end">Rate Used (₹)</th>
                                <th style="width: 130px;" class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="inspectorComponentsBody"></tbody>
                    </table>
                </div>

                <!-- Calculation Summary Card -->
                <div class="card bg-light border p-3">
                    <div class="row g-2 align-items-center small">
                        <div class="col-md-3">
                            <span class="text-muted">Basic Cost Sum (W):</span>
                            <div class="fw-bold font-monospace fs-6" id="inspectorW">₹0.00</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Water Charges (+1%):</span>
                            <div class="fw-semibold font-monospace" id="inspectorX">₹0.00</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">DAR Factor (CPOH + Taxes):</span>
                            <div class="fw-bold font-monospace text-primary" id="inspectorFactor">1.4085</div>
                        </div>
                        <div class="col-md-3 text-end">
                            <span class="text-success fw-bold">Derived Rate:</span>
                            <div class="fs-4 fw-bold font-monospace text-success" id="inspectorDerivedRate">₹0.00</div>
                            <small class="text-muted">per <span id="inspectorRateUnit">unit</span></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const boqData = <?= json_encode($boq) ?>;
let currentItemIndex = null;
let currentAnalysisTab = 'estimate';
let inspectorModalInstance = null;

function viewRateAnalysisModal(idx) {
    currentItemIndex = idx;
    const item = boqData[idx];
    if (!item) return;

    if (!inspectorModalInstance) {
        inspectorModalInstance = new bootstrap.Modal(document.getElementById('viewAnalysisModal'));
    }

    document.getElementById('inspectorItemCode').textContent = item.item_code || '';
    document.getElementById('inspectorDesc').textContent = item.description || '';
    
    // Choose initial tab: if justified analysis exists, user can switch; default to estimate
    currentAnalysisTab = (item.justified_analysis_breakup) ? 'justified' : 'estimate';
    renderInspectorTab();
    inspectorModalInstance.show();
}

function switchAnalysisTab(tab) {
    currentAnalysisTab = tab;
    document.getElementById('tab-estimate').classList.toggle('active', tab === 'estimate');
    document.getElementById('tab-justified').classList.toggle('active', tab === 'justified');
    renderInspectorTab();
}

function renderInspectorTab() {
    const item = boqData[currentItemIndex];
    if (!item) return;

    const analysis = (currentAnalysisTab === 'justified' && item.justified_analysis_breakup)
        ? item.justified_analysis_breakup
        : (item.estimate_analysis_breakup || item.analysis_breakup || {});

    const comps = analysis.components || [];
    const basisQty = parseFloat(analysis.cost_basis_qty) || 1.0;
    const basisUnit = analysis.cost_basis_unit || item.unit || 'unit';
    const factor = parseFloat(analysis.dar_factor) || 1.4085;
    const snapshotTime = analysis.snapshotted_at || analysis.justified_at || item.snapshotted_at || 'At Estimate Preparation';

    document.getElementById('inspectorSnapshotTime').textContent = snapshotTime;
    document.getElementById('inspectorCostBasisQty').textContent = basisQty;
    document.getElementById('inspectorCostBasisUnit').textContent = basisUnit;
    document.getElementById('inspectorRateUnit').textContent = basisUnit;
    document.getElementById('inspectorFactor').textContent = factor;

    const tbody = document.getElementById('inspectorComponentsBody');
    tbody.innerHTML = '';

    let sumW = 0.0;
    comps.forEach((c, i) => {
        const qty = parseFloat(c.quantity) || 0;
        const rate = parseFloat(c.market_rate ?? c.standard_rate) || 0;
        const amt = parseFloat(c.amount) || (qty * rate);
        sumW += amt;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center text-muted small">${i + 1}</td>
            <td><span class="badge bg-light text-dark border">${c.category || 'MATERIAL'}</span></td>
            <td class="font-monospace text-primary small">${c.resource_code || ''}</td>
            <td class="small">${c.description || ''}</td>
            <td class="text-center small font-monospace">${c.unit || ''}</td>
            <td class="text-end font-monospace">${qty.toFixed(4)}</td>
            <td class="text-end font-monospace">₹${rate.toFixed(2)}</td>
            <td class="text-end font-monospace fw-bold">₹${amt.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });

    const sumX = sumW * 1.01;
    const derivedRate = basisQty > 0 ? ((sumW * factor) / basisQty) : (sumW * factor);

    document.getElementById('inspectorW').textContent = '₹' + sumW.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inspectorX').textContent = '₹' + sumX.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inspectorDerivedRate').textContent = '₹' + derivedRate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

</body>
</html>
