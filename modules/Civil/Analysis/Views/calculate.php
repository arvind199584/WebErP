<?php
/**
 * @var int $estimateId
 * @var array $estimates
 * @var array|null $analysis
 * @var float $waterPct
 * @var float $cpOhPct
 * @var float $gstPct
 * @var float $cessPct
 */
$summary = $analysis['summary'] ?? null;
$items = $analysis['items'] ?? [];
$estimate = $analysis['estimate'] ?? null;
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/modules/Civil/Estimates/Controller/EstimateController.php?action=list" class="text-decoration-none">Civil Estimates</a></li>
                    <li class="breadcrumb-item"><a href="?action=list" class="text-decoration-none">Rate Justification</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Calculator</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-calculator text-primary me-2"></i>Rate Justification Calculation Engine
            </h2>
            <p class="text-muted small mb-0">Calculates prevailing market-rate justified costs using CPWD Analysis of Rates (DAR) constants.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/modules/Civil/GlobalRates/Controller/GlobalRatesController.php?action=list" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-currency-rupee me-1"></i> Edit Global Market Rates
            </a>
            <a href="?action=list" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Configuration Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="?" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="calculate">

                <div class="col-md-4">
                    <label for="estimate_id" class="form-label small fw-semibold">Select Estimate to Evaluate</label>
                    <select id="estimate_id" name="estimate_id" class="form-select" onchange="this.form.submit()">
                        <?php if (empty($estimates)): ?>
                            <option value="">No estimates found</option>
                        <?php else: ?>
                            <?php foreach ($estimates as $est): ?>
                                <option value="<?= $est['id'] ?>" <?= ($est['id'] == $estimateId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($est['estimate_no']) ?> - <?= htmlspecialchars(mb_strimwidth($est['work_name'], 0, 45, '...')) ?> (₹<?= number_format((float)$est['total_amount'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label for="water_charges" class="form-label small fw-semibold">Water Charges</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.1" min="0" id="water_charges" name="water_charges" class="form-control text-end" value="<?= $waterPct ?>">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-6 col-md-2">
                    <label for="cp_oh" class="form-label small fw-semibold">CP & Overheads</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.5" min="0" id="cp_oh" name="cp_oh" class="form-control text-end" value="<?= $cpOhPct ?>">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-6 col-md-2">
                    <label for="gst" class="form-label small fw-semibold">GST on Works</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.5" min="0" id="gst" name="gst" class="form-control text-end" value="<?= $gstPct ?>">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i> Recalculate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($analysis && $summary): ?>
        <!-- Summary Dashboard Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold">DSR Estimated Cost</div>
                        <div class="fs-4 fw-bold text-dark font-monospace">₹<?= number_format($summary['total_estimated_cost'], 2) ?></div>
                        <small class="text-muted">As per DSR 2023 baseline rates</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold">Justified Cost (Market)</div>
                        <div class="fs-4 fw-bold text-primary font-monospace">₹<?= number_format($summary['total_justified_cost'], 2) ?></div>
                        <small class="text-muted">DAR constants × Current market rates</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold">Cost Variance</div>
                        <div class="fs-4 fw-bold font-monospace <?= ($summary['difference_amount'] >= 0) ? 'text-danger' : 'text-success' ?>">
                            <?= ($summary['difference_amount'] >= 0) ? '+' : '' ?>₹<?= number_format($summary['difference_amount'], 2) ?>
                        </div>
                        <small class="text-muted">Difference from baseline estimate</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold">Justification Percentage</div>
                        <div class="fs-4 fw-bold font-monospace <?= ($summary['justification_percentage'] >= 0) ? 'text-danger' : 'text-success' ?>">
                            <?= ($summary['justification_percentage'] >= 0) ? '+' : '' ?><?= number_format($summary['justification_percentage'], 2) ?>%
                        </div>
                        <small class="text-muted">Justified % over DSR 2023</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Line Item Justification Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fs-6 fw-bold">
                    <i class="bi bi-list-check text-primary me-2"></i>Item-Wise Rate Analysis & Comparison
                </h5>
                <span class="badge bg-secondary"><?= count($items) ?> items evaluated</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 100px;">Item Code</th>
                            <th>Description</th>
                            <th style="width: 90px;" class="text-end">Qty</th>
                            <th style="width: 70px;">Unit</th>
                            <th style="width: 120px;" class="text-end">DSR Rate (₹)</th>
                            <th style="width: 130px;" class="text-end">Justified Rate (₹)</th>
                            <th style="width: 140px;" class="text-end">Justified Cost (₹)</th>
                            <th style="width: 110px;" class="text-center">Variance %</th>
                            <th style="width: 70px;" class="text-center">DAR Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): 
                            $diffPct = (float)$it['difference_percent'];
                            $badgeColor = ($diffPct > 0) ? 'text-danger fw-bold' : (($diffPct < 0) ? 'text-success fw-bold' : 'text-muted');
                        ?>
                            <tr>
                                <td class="text-muted small"><?= $idx + 1 ?></td>
                                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($it['item_code']) ?></span></td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($it['description']) ?></div>
                                    <?php if (!$it['has_dar_analysis']): ?>
                                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>No DAR breakup constant found; using baseline DSR rate.</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace"><?= number_format((float)$it['quantity'], 2) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($it['unit']) ?></span></td>
                                <td class="text-end font-monospace text-muted"><?= number_format((float)$it['dsr_rate'], 2) ?></td>
                                <td class="text-end font-monospace fw-bold text-primary"><?= number_format((float)$it['justified_rate'], 2) ?></td>
                                <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format((float)$it['justified_amount'], 2) ?></td>
                                <td class="text-center font-monospace small <?= $badgeColor ?>">
                                    <?= ($diffPct > 0) ? '+' : '' ?><?= number_format($diffPct, 2) ?>%
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($it['resources'])): ?>
                                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#dar-details-<?= $idx ?>" aria-expanded="false" title="View Resource Breakdown">
                                            <i class="bi bi-diagram-3"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($it['resources'])): ?>
                                <tr class="collapse bg-light" id="dar-details-<?= $idx ?>">
                                    <td colspan="10" class="p-3">
                                        <div class="card border rounded-2 bg-white">
                                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                                <span class="small fw-bold text-dark">CPWD DAR Resource Breakdown for Item <?= htmlspecialchars($it['item_code']) ?></span>
                                                <span class="badge bg-secondary"><?= count($it['resources']) ?> resources</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0 small">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Resource Code</th>
                                                            <th>Category</th>
                                                            <th>Resource Description</th>
                                                            <th class="text-end">Consumption Constant</th>
                                                            <th>Unit</th>
                                                            <th class="text-end">DSR Rate (₹)</th>
                                                            <th class="text-end">Market Rate (₹)</th>
                                                            <th class="text-end">Amount (₹)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($it['resources'] as $res): ?>
                                                            <tr>
                                                                <td class="font-monospace"><?= htmlspecialchars($res['resource_code']) ?></td>
                                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($res['category']) ?></span></td>
                                                                <td><?= htmlspecialchars($res['description']) ?></td>
                                                                <td class="text-end font-monospace"><?= number_format((float)$res['constant_qty'], 4) ?></td>
                                                                <td><?= htmlspecialchars($res['unit']) ?></td>
                                                                <td class="text-end font-monospace text-muted"><?= number_format((float)$res['dsr_rate'], 2) ?></td>
                                                                <td class="text-end font-monospace fw-semibold"><?= number_format((float)$res['market_rate'], 2) ?></td>
                                                                <td class="text-end font-monospace">₹<?= number_format((float)$res['amount'], 2) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Save Justification Statement Form -->
            <div class="card-footer bg-white p-3 border-top">
                <form action="?action=save" method="POST" class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                    <input type="hidden" name="estimate_id" value="<?= $estimate['id'] ?>">
                    <input type="hidden" name="water_charges_percent" value="<?= $waterPct ?>">
                    <input type="hidden" name="cp_oh_percent" value="<?= $cpOhPct ?>">
                    <input type="hidden" name="gst_percent" value="<?= $gstPct ?>">
                    <input type="hidden" name="cess_percent" value="<?= $cessPct ?>">

                    <div class="d-flex align-items-center gap-2" style="max-width: 380px;">
                        <label for="justification_no" class="small fw-semibold text-nowrap">Statement Ref No:</label>
                        <input type="text" id="justification_no" name="justification_no" class="form-control form-control-sm font-monospace" 
                               value="JUST/<?= htmlspecialchars($estimate['estimate_no']) ?>/<?= date('Ymd') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-save me-1"></i> Save Formal Justification Statement
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-3 p-5 text-center text-muted">
            <i class="bi bi-calculator fs-1 mb-3 text-secondary"></i>
            <h5>Select an estimate above to perform Rate Justification.</h5>
            <p class="small">If you don't have an estimate yet, create one under <a href="/modules/Civil/Estimates/Controller/EstimateController.php?action=showCreateForm">Civil Estimates</a>.</p>
        </div>
    <?php endif; ?>
</div>
