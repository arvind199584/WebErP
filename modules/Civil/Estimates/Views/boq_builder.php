<?php
/**
 * @var array $estimate
 * @var array $items
 */
?>
<div class="container-fluid py-4">
    <!-- Breadcrumb & Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="?action=list" class="text-decoration-none">Civil Estimates</a></li>
                    <li class="breadcrumb-item active" aria-current="page">BoQ Builder: <?= htmlspecialchars($estimate['estimate_no']) ?></li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-list-task text-primary me-2"></i>Bill of Quantities (BoQ) Builder
            </h2>
            <div class="text-muted small">
                <strong>Work:</strong> <?= htmlspecialchars($estimate['work_name']) ?> | 
                <strong>Office:</strong> <?= htmlspecialchars($estimate['officename'] ?? 'Office') ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=view&id=<?= $estimate['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Preview / Print BoQ
            </a>
            <a href="/modules/Civil/Analysis/Controller/AnalysisController.php?action=calculate&estimate_id=<?= $estimate['id'] ?>" class="btn btn-outline-info">
                <i class="bi bi-graph-up-arrow me-1"></i> Justify Rates
            </a>
            <a href="?action=list" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Done
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Add DSR Item Form & Live Search -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi bi-search me-2"></i>Search & Pick DSR 2023 Item
                    </h5>
                </div>
                <div class="card-body p-3">
                    <!-- Live Search Box with HTMX -->
                    <div class="mb-3">
                        <label for="dsr_search_input" class="form-label small fw-semibold text-muted">Type Item Code or Keyword</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="dsr_search_input" class="form-control border-start-0 ps-0" 
                                   placeholder="e.g. 4.1.2, Earth work, RCC, Brick masonry, Flooring..."
                                   hx-get="?action=searchDsr"
                                   hx-trigger="keyup changed delay:300ms, search"
                                   hx-target="#dsr-search-results"
                                   name="q" autocomplete="off">
                        </div>
                    </div>

                    <!-- Search Results Box -->
                    <div id="dsr-search-results" class="list-group mb-3 border rounded-3" style="max-height: 220px; overflow-y: auto;">
                        <div class="list-group-item text-muted text-center py-3 small">
                            Type above to search across 3,039 DSR 2023 civil items.
                        </div>
                    </div>

                    <!-- Item Entry Form -->
                    <form hx-post="?action=addItem" 
                          hx-target="#boq-table-container" 
                          hx-on::after-request="this.reset(); document.getElementById('selected_item_info').classList.add('d-none');">
                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                        <input type="hidden" name="estimate_id" value="<?= $estimate['id'] ?>">

                        <div id="selected_item_info" class="p-3 mb-3 bg-light rounded-3 border d-none">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary font-monospace fs-6" id="display_item_code">-</span>
                                <span class="fw-bold font-monospace text-success fs-6">₹<span id="display_rate">0.00</span> / <span id="display_unit">unit</span></span>
                            </div>
                            <div class="small text-dark fw-semibold" id="display_description">-</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="form_item_code" class="form-label small fw-semibold">Item Code <span class="text-danger">*</span></label>
                                <input type="text" id="form_item_code" name="item_code" class="form-control font-monospace form-control-sm" required placeholder="e.g. 4.1.3">
                            </div>
                            <div class="col-6">
                                <label for="quantity" class="form-label small fw-semibold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" step="0.0001" min="0.0001" id="quantity" name="quantity" class="form-control font-monospace form-control-sm" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label small fw-semibold">Remarks / Location Reference</label>
                            <input type="text" id="remarks" name="remarks" class="form-control form-control-sm" placeholder="e.g. Plinth beam, South wall">
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-sm fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i> Add Item to BoQ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Current BoQ Table -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi bi-table text-primary me-2"></i>Estimate Line Items
                    </h5>
                    <span class="badge bg-secondary">Total: <?= count($items) ?> items</span>
                </div>
                <div id="boq-table-container">
                    <?php require __DIR__ . '/partials/boq_table.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectDsrItem(code, desc, unit, rate) {
    document.getElementById('form_item_code').value = code;
    document.getElementById('display_item_code').innerText = code;
    document.getElementById('display_description').innerText = desc;
    document.getElementById('display_unit').innerText = unit;
    document.getElementById('display_rate').innerText = parseFloat(rate).toFixed(2);
    document.getElementById('selected_item_info').classList.remove('d-none');
    document.getElementById('quantity').focus();
}
</script>
