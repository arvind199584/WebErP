<?php
/**
 * @var array $justifications
 * @var array $estimates
 * @var string|null $message
 * @var string|null $error
 */
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item">Civil Engineering</li>
                    <li class="breadcrumb-item active" aria-current="page">Rate Justification</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-graph-up-arrow text-primary me-2"></i>Rate Justification & Analysis Statements
            </h2>
            <p class="text-muted small mb-0">Evaluate CPWD DAR analytical unit costs against prevailing market rates to justify tender quotes and extra work items.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/modules/Civil/GlobalRates/Controller/GlobalRatesController.php?action=list" class="btn btn-outline-secondary">
                <i class="bi bi-currency-rupee me-1"></i> Market Rates Registry
            </a>
            <a href="/modules/Civil/Estimates/Controller/EstimateController.php?action=list" class="btn btn-outline-primary">
                <i class="bi bi-calculator me-1"></i> Estimates
            </a>
            <a href="?action=calculate" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Justification Calculation
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div><?= htmlspecialchars($message) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Table of Saved Justifications -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 150px;">Statement No</th>
                        <th>Work / Estimate</th>
                        <th style="width: 140px;">Office</th>
                        <th style="width: 160px;" class="text-end">Estimated Cost (₹)</th>
                        <th style="width: 160px;" class="text-end">Justified Cost (₹)</th>
                        <th style="width: 140px;" class="text-center">Justification %</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 120px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($justifications)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-bar-graph fs-1 d-block mb-2 text-secondary"></i>
                                No rate justification statements saved yet.<br>
                                <a href="?action=calculate" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-calculator me-1"></i>Calculate Justification from an Estimate
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($justifications as $j): 
                            $pct = (float)$j['justification_percentage'];
                            $badgeClass = ($pct >= 0) ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                        ?>
                            <tr>
                                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($j['justification_no']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($j['title']) ?></div>
                                    <small class="text-muted font-monospace">Ref Estimate: <?= htmlspecialchars($j['estimate_no'] ?? 'N/A') ?></small>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($j['officename'] ?? 'Office') ?></small></td>
                                <td class="text-end font-monospace text-muted">
                                    ₹<?= number_format((float)$j['estimated_cost'], 2) ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">
                                    ₹<?= number_format((float)$j['justified_cost'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> font-monospace fs-6">
                                        <?= ($pct >= 0) ? '+' : '' ?><?= number_format($pct, 2) ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        <?= htmlspecialchars($j['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="?action=viewStatement&id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Official Statement">
                                        <i class="bi bi-file-text me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
