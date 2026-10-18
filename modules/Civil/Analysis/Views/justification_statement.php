<?php
/**
 * @var array $statement
 */
$breakdowns = $statement['breakdowns'] ?? [];
$justPct = (float)$statement['justification_percentage'];
$isHigher = ($justPct >= 0);
?>
<div class="container-fluid py-4" style="max-width: 1100px;">
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="?action=list" class="text-decoration-none">Rate Justification</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($statement['justification_no']) ?></li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">Statement of Justification of Rates</h2>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> Print / Export PDF
            </button>
            <a href="?action=calculate&estimate_id=<?= $statement['estimate_id'] ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Recalculate
            </a>
            <a href="?action=list" class="btn btn-primary">
                <i class="bi bi-list me-1"></i> Statements List
            </a>
        </div>
    </div>

    <!-- Official Printable Sheet Card -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white print-card">
        <!-- Title & Header -->
        <div class="text-center pb-3 border-bottom mb-4">
            <h4 class="fw-bold mb-1 text-uppercase">Central Public Works Department / Office Management</h4>
            <h5 class="text-muted mb-2"><?= htmlspecialchars($statement['officename'] ?? 'Civil Division') ?></h5>
            <h5 class="fw-bold text-primary mt-2">STATEMENT SHOWING JUSTIFICATION OF RATES</h5>
            <div class="font-monospace text-secondary">
                Statement Ref: <strong><?= htmlspecialchars($statement['justification_no']) ?></strong> | 
                Date: <strong><?= date('d-m-Y', strtotime($statement['created_at'])) ?></strong>
            </div>
        </div>

        <!-- Meta Details Box -->
        <div class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-7">
                <strong>Name of Work:</strong>
                <div><?= htmlspecialchars($statement['work_name'] ?? $statement['title']) ?></div>
                <div class="text-muted small mt-1">Estimate Ref: <strong><?= htmlspecialchars($statement['estimate_no'] ?? 'N/A') ?></strong></div>
            </div>
            <div class="col-md-5 text-md-end">
                <div><strong>Standard Schedule:</strong> CPWD DSR 2023 (Civil)</div>
                <div><strong>Analysis Source:</strong> CPWD DAR 2023 Constants</div>
                <div><strong>Parameters Applied:</strong> Water 1%, CP&OH 15%, GST 18%, Cess 1%</div>
            </div>
        </div>

        <!-- Comparative Justification Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-secondary text-center">
                    <tr>
                        <th style="width: 35px;">Item</th>
                        <th style="width: 90px;">DSR Code</th>
                        <th>Nomenclature of Item</th>
                        <th style="width: 75px;">Qty</th>
                        <th style="width: 65px;">Unit</th>
                        <th style="width: 110px;">DSR Rate (₹)</th>
                        <th style="width: 125px;">Estimated Cost (₹)</th>
                        <th style="width: 115px;">Justified Rate (₹)</th>
                        <th style="width: 130px;">Justified Cost (₹)</th>
                        <th style="width: 90px;">Variation %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($breakdowns)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No items found in statement.</td></tr>
                    <?php else: ?>
                        <?php foreach ($breakdowns as $idx => $it): 
                            $diffPct = (float)($it['difference_percent'] ?? 0);
                        ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td class="text-center font-monospace font-semibold"><?= htmlspecialchars($it['item_code']) ?></td>
                                <td class="small"><?= htmlspecialchars($it['description']) ?></td>
                                <td class="text-end font-monospace"><?= number_format((float)$it['quantity'], 2) ?></td>
                                <td class="text-center"><?= htmlspecialchars($it['unit']) ?></td>
                                <td class="text-end font-monospace"><?= number_format((float)$it['dsr_rate'], 2) ?></td>
                                <td class="text-end font-monospace">₹<?= number_format((float)$it['estimated_amount'], 2) ?></td>
                                <td class="text-end font-monospace fw-semibold text-primary"><?= number_format((float)$it['justified_rate'], 2) ?></td>
                                <td class="text-end font-monospace fw-semibold">₹<?= number_format((float)$it['justified_amount'], 2) ?></td>
                                <td class="text-center font-monospace small <?= ($diffPct >= 0) ? 'text-danger' : 'text-success' ?>">
                                    <?= ($diffPct >= 0) ? '+' : '' ?><?= number_format($diffPct, 2) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="6" class="text-end">Total Net Amount:</th>
                        <th class="text-end font-monospace">₹<?= number_format((float)$statement['estimated_cost'], 2) ?></th>
                        <th></th>
                        <th class="text-end font-monospace text-primary">₹<?= number_format((float)$statement['justified_cost'], 2) ?></th>
                        <th class="text-center font-monospace <?= ($isHigher ? 'text-danger' : 'text-success') ?>">
                            <?= ($isHigher ? '+' : '') ?><?= number_format($justPct, 2) ?>%
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Official Executive Conclusion Banner -->
        <div class="card bg-light border p-3 mb-4">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-clipboard2-check me-2 text-primary"></i>Justification Assessment & Recommendation:</h6>
            <p class="mb-0 small">
                On the basis of prevailing market rates of basic materials (cement, steel, coarse & fine aggregate, bricks) and labor wages analyzed in accordance with CPWD Delhi Analysis of Rates (DAR) 2023 with statutory allowances (1% Water charges, 15% CP & OH, 18% GST and 1% Labour cess):
                <br>
                The total <strong>Justified Cost</strong> works out to <strong>₹<?= number_format((float)$statement['justified_cost'], 2) ?></strong> against the <strong>Estimated Cost</strong> of <strong>₹<?= number_format((float)$statement['estimated_cost'], 2) ?></strong>, which is 
                <span class="badge <?= ($isHigher ? 'bg-danger' : 'bg-success') ?> font-monospace fs-6">
                    <?= ($isHigher ? '+' : '') ?><?= number_format($justPct, 2) ?>%
                </span>
                <?= ($isHigher ? 'ABOVE' : 'BELOW') ?> the estimated cost based on DSR 2023.
            </p>
        </div>

        <!-- Official Signatures Block -->
        <div class="row pt-5 mt-5">
            <div class="col-4 text-center">
                <div class="border-top pt-2">Prepared By:<br><strong>Junior Engineer (QS&C)</strong></div>
            </div>
            <div class="col-4 text-center">
                <div class="border-top pt-2">Checked By:<br><strong>Assistant Engineer (P&D)</strong></div>
            </div>
            <div class="col-4 text-center">
                <div class="border-top pt-2">Approved By:<br><strong>Executive Engineer</strong></div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, nav, .sidebar, header, footer { display: none !important; }
    body { background-color: #fff !important; }
    .print-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
}
</style>
