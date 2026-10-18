<?php
/**
 * @var array $estimate
 * @var array $items
 */
?>
<div class="container-fluid py-4" style="max-width: 1000px;">
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="?action=list" class="text-decoration-none">Civil Estimates</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($estimate['estimate_no']) ?></li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">Detailed Estimate & Abstract of Cost</h2>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> Print / Export PDF
            </button>
            <a href="?action=boqBuilder&id=<?= $estimate['id'] ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit BoQ
            </a>
            <a href="/modules/Civil/Analysis/Controller/AnalysisController.php?action=calculate&estimate_id=<?= $estimate['id'] ?>" class="btn btn-info text-white">
                <i class="bi bi-graph-up-arrow me-1"></i> Rate Justification
            </a>
        </div>
    </div>

    <!-- Official Estimate Sheet Card -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white print-card">
        <!-- Title & Office Header -->
        <div class="text-center pb-3 border-bottom mb-4">
            <h4 class="fw-bold mb-1 text-uppercase">Central Public Works Department / Office Management</h4>
            <h5 class="text-muted mb-2"><?= htmlspecialchars($estimate['officename'] ?? 'Civil Division') ?></h5>
            <h5 class="fw-bold text-primary mt-2">ABSTRACT OF COST & BILL OF QUANTITIES</h5>
            <div class="font-monospace text-secondary">Estimate No: <strong><?= htmlspecialchars($estimate['estimate_no']) ?></strong> | Status: <strong><?= htmlspecialchars($estimate['status']) ?></strong></div>
        </div>

        <!-- Work Details Summary -->
        <div class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-8">
                <strong>Name of Work:</strong>
                <div><?= htmlspecialchars($estimate['work_name']) ?></div>
                <?php if (!empty($estimate['description'])): ?>
                    <div class="text-muted small mt-1"><?= htmlspecialchars($estimate['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end">
                <div><strong>Schedule:</strong> DSR 2023 (Civil)</div>
                <div><strong>Date:</strong> <?= date('d-m-Y', strtotime($estimate['created_at'])) ?></div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-secondary text-center">
                    <tr>
                        <th style="width: 40px;">Item No</th>
                        <th style="width: 100px;">DSR Item Code</th>
                        <th>Description of Work</th>
                        <th style="width: 90px;">Quantity</th>
                        <th style="width: 80px;">Unit</th>
                        <th style="width: 120px;">Rate (₹)</th>
                        <th style="width: 140px;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No items in this estimate.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $idx => $it): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td class="text-center font-monospace font-semibold"><?= htmlspecialchars($it['item_code']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($it['description']) ?></div>
                                    <?php if (!empty($it['remarks'])): ?>
                                        <div class="text-muted small"><em>Remarks: <?= htmlspecialchars($it['remarks']) ?></em></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace"><?= number_format((float)$it['quantity'], 2) ?></td>
                                <td class="text-center"><?= htmlspecialchars($it['unit']) ?></td>
                                <td class="text-end font-monospace"><?= number_format((float)$it['rate'], 2) ?></td>
                                <td class="text-end font-monospace fw-semibold"><?= number_format((float)$it['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Sub-Total (Net Items Cost):</th>
                        <th class="text-end font-monospace">₹<?= number_format((float)$estimate['estimated_amount'], 2) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end font-weight-normal text-muted">Add Contingencies @ <?= htmlspecialchars((string)$estimate['contingency_percent']) ?>%:</th>
                        <th class="text-end font-monospace text-muted">+ ₹<?= number_format((float)(($estimate['estimated_amount'] * $estimate['contingency_percent']) / 100), 2) ?></th>
                    </tr>
                    <tr class="table-primary">
                        <th colspan="6" class="text-end fs-6">GRAND TOTAL ESTIMATED COST:</th>
                        <th class="text-end font-monospace fs-5 fw-bold">₹<?= number_format((float)$estimate['total_amount'], 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Formal Signature Block -->
        <div class="row pt-5 mt-5">
            <div class="col-4 text-center">
                <div class="border-top pt-2">Junior Engineer (Civil)</div>
            </div>
            <div class="col-4 text-center">
                <div class="border-top pt-2">Assistant Engineer (Civil)</div>
            </div>
            <div class="col-4 text-center">
                <div class="border-top pt-2">Executive Engineer</div>
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
