<?php
/**
 * @var array $items
 * @var int $total
 * @var int $page
 * @var int $totalPages
 * @var array $categories
 * @var array $stats
 * @var string|null $query
 * @var string|null $selectedCategory
 * @var string|null $message
 * @var string|null $error
 */
?>
<div class="container-fluid py-4">
    <!-- Header with Breadcrumbs & Action -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item">Civil Engineering</li>
                    <li class="breadcrumb-item active" aria-current="page">Global Rates Registry</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-currency-rupee text-primary me-2"></i>Centralized Market Rates Registry
            </h2>
            <p class="text-muted small mb-0">Master schedule of basic material, labour, and machinery rates applied dynamically across all justification statements.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#uploadExcelModal">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Bulk Upload Excel
            </button>
            <a href="/modules/Civil/Estimates/Controller/EstimateController.php?action=list" class="btn btn-primary">
                <i class="bi bi-calculator me-1"></i> Go to Estimates
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

    <!-- Stat Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-boxes fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Total Resources</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format((int)($stats['total_resources'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-hammer fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Labour Types</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format((int)($stats['total_labour'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-bricks fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Materials</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format((int)($stats['total_materials'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-pencil-square fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Market Overridden</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format((int)($stats['total_overridden'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="?" class="row g-2 align-items-center">
                <input type="hidden" name="action" value="list">
                
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" 
                               placeholder="Search resource by code (e.g. 0367) or name (e.g. Portland Cement, Sand, Beldar)..." 
                               value="<?= htmlspecialchars((string)$query) ?>">
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <select name="category" class="form-select">
                        <option value="All">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= ($selectedCategory === $cat['category']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?> (<?= $cat['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if (!empty($query) || (!empty($selectedCategory) && $selectedCategory !== 'All')): ?>
                        <a href="?action=list" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Rates Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <?php require __DIR__ . '/partials/rates_table.php'; ?>
    </div>
</div>

<!-- Upload Excel Modal -->
<div class="modal fade" id="uploadExcelModal" tabindex="-1" aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="?action=importExcel" method="POST" enctype="multipart/form-data">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="uploadExcelModalLabel"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Bulk Update Market Rates via Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small">
                        Upload an Excel file (<code>.xlsx</code>) containing columns for <strong>Code</strong> (Column A) and <strong>Rate</strong> (Column E). Matching resources in the registry will have their <code>current_market_rate</code> updated instantly.
                    </p>
                    <div class="mb-3">
                        <label for="excel_file" class="form-label fw-semibold">Select Excel File (.xlsx)</label>
                        <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Upload & Update Rates</button>
                </div>
            </form>
        </div>
    </div>
</div>
