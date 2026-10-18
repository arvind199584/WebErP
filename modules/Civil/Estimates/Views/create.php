<?php
/**
 * @var array $offices
 * @var int $defaultOfficeId
 */
$isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser();
?>
<div class="container-fluid py-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="?action=list" class="text-decoration-none">Civil Estimates</a></li>
                    <li class="breadcrumb-item active" aria-current="page">New Estimate</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-file-earmark-plus text-primary me-2"></i>Create New Civil Estimate
            </h2>
        </div>
        <a href="?action=list" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fw-bold">Estimate Header Details</h5>
        </div>
        <div class="card-body p-4">
            <form action="?action=create" method="POST">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>

                <?php if ($isSuperuser && !empty($offices)): ?>
                    <div class="mb-3">
                        <label for="officeid" class="form-label fw-semibold">Office <span class="text-danger">*</span></label>
                        <select id="officeid" name="officeid" class="form-select" required>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?= $office->Officeid ?>" <?= ($office->Officeid == $defaultOfficeId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($office->OfficeName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="estimate_no" class="form-label fw-semibold">Estimate Number <span class="text-danger">*</span></label>
                        <input type="text" id="estimate_no" name="estimate_no" class="form-control font-monospace" 
                               placeholder="e.g. EST/CIVIL/2026/001" required>
                        <div class="form-text">Unique identification reference for this estimate.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="contingency_percent" class="form-label fw-semibold">Contingencies % <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.5" min="0" max="25" id="contingency_percent" name="contingency_percent" 
                                   class="form-control text-end" value="3.00" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Standard government works contingency allowance (typically 3%).</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="work_name" class="form-label fw-semibold">Name of Work <span class="text-danger">*</span></label>
                    <input type="text" id="work_name" name="work_name" class="form-control" 
                           placeholder="e.g. Construction of Boundary Wall and Pathway at Sector 4" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Scope / Work Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" 
                              placeholder="Brief scope, location specifications, site details..."></textarea>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="?action=list" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-arrow-right-circle me-1"></i> Save & Build BoQ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
