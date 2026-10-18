<?php
/**
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
                    <li class="breadcrumb-item active" aria-current="page">Estimates</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-calculator text-primary me-2"></i>Civil Works Estimates (DSR 2023)
            </h2>
            <p class="text-muted small mb-0">Prepare detailed estimates and Bills of Quantities using the official CPWD Delhi Schedule of Rates 2023.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/modules/Civil/GlobalRates/Controller/GlobalRatesController.php?action=list" class="btn btn-outline-secondary">
                <i class="bi bi-currency-rupee me-1"></i> Market Rates Registry
            </a>
            <a href="/modules/Civil/Analysis/Controller/AnalysisController.php?action=list" class="btn btn-outline-info">
                <i class="bi bi-graph-up-arrow me-1"></i> Rate Justification
            </a>
            <a href="?action=showCreateForm" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create New Estimate
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

    <!-- Table of Estimates -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 130px;">Estimate No</th>
                        <th>Work Name</th>
                        <th style="width: 140px;">Office</th>
                        <th style="width: 80px;" class="text-center">Items</th>
                        <th style="width: 160px;" class="text-end">Total Amount (₹)</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 200px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estimates)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-plus fs-1 d-block mb-2 text-secondary"></i>
                                No civil estimates found.<br>
                                <a href="?action=showCreateForm" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Create First Estimate
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estimates as $est): ?>
                            <tr>
                                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($est['estimate_no']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($est['work_name']) ?></div>
                                    <?php if (!empty($est['description'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($est['description'], 0, 75, '...')) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($est['officename'] ?? 'Main Office') ?></small></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= (int)($est['item_count'] ?? 0) ?></span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">
                                    ₹<?= number_format((float)$est['total_amount'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        <?= htmlspecialchars($est['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?action=boqBuilder&id=<?= $est['id'] ?>" class="btn btn-outline-primary" title="Add / Edit BoQ Items">
                                            <i class="bi bi-pencil-square me-1"></i>BoQ
                                        </a>
                                        <a href="?action=view&id=<?= $est['id'] ?>" class="btn btn-outline-secondary" title="View Full Estimate">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/modules/Civil/Analysis/Controller/AnalysisController.php?action=calculate&estimate_id=<?= $est['id'] ?>" class="btn btn-outline-info" title="Calculate Rate Justification">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
