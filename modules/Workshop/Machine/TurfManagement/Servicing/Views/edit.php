<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Servicing Log</title>
</head>
<body>
<div class="container-fluid py-3" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">✏️ Edit Servicing Log</h1>
            <p class="text-muted mb-0">Update servicing details for <?php echo htmlspecialchars($servicing['machine_name']); ?>.</p>
        </div>
        <a href="?action=list" class="btn btn-outline-secondary">← Back to List</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="?action=update">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <input type="hidden" name="id" value="<?php echo $servicing['id']; ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="machine_id" class="form-label fw-bold">Select Machine <span class="text-danger">*</span></label>
                        <select name="machine_id" id="machine_id" class="form-select" required>
                            <?php foreach ($machines as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($servicing['machine_id'] == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="service_date" class="form-label fw-bold">Service Date <span class="text-danger">*</span></label>
                        <input type="date" name="service_date" id="service_date" class="form-control" value="<?php echo htmlspecialchars($servicing['service_date']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="service_type" class="form-label fw-bold">Service Type</label>
                        <select name="service_type" id="service_type" class="form-select">
                            <?php 
                            $types = ['Routine Maintenance', 'Oil & Filter Change', 'Blade Sharpening', 'Engine Overhaul', 'Hydraulic System Service', 'Tire & Belt Repair', 'Breakdown Repair', 'Other'];
                            foreach ($types as $t): 
                            ?>
                                <option value="<?php echo $t; ?>" <?php echo ($servicing['service_type'] === $t) ? 'selected' : ''; ?>>
                                    <?php echo $t; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="hours_at_service" class="form-label fw-bold">Machine Hours at Service <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" name="hours_at_service" id="hours_at_service" class="form-control" value="<?php echo htmlspecialchars((string)$servicing['hours_at_service']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cost" class="form-label fw-bold">Servicing Cost (₹)</label>
                        <input type="number" step="0.01" name="cost" id="cost" class="form-control" value="<?php echo htmlspecialchars((string)($servicing['cost'] ?? '')); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="job_card_no" class="form-label fw-bold">Job Card / Indent No. <span class="text-primary">*</span></label>
                        <input type="text" name="job_card_no" id="job_card_no" class="form-control" value="<?php echo htmlspecialchars($servicing['job_card_no'] ?? ''); ?>">
                        <div class="form-text">
                            Job card tracks store inventory items used for servicing. 
                            <a href="/modules/Workshop/Machine/TurfManagement/Indents/Controller/IndentController.php?action=showCreateForm" target="_blank" class="fw-bold text-decoration-none">
                                📋 Issue Store Inventory Items (Create Indent) ↗
                            </a>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="remarks" class="form-label fw-bold">Service Details / Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3" class="form-control"><?php echo htmlspecialchars($servicing['remarks'] ?? ''); ?></textarea>
                    </div>

                </div>

                <div class="mt-4 text-end">
                    <a href="?action=list" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Update Servicing Record</button>
                </div>
            </form>
        </div>
    </div>

</div>
</body>
</html>
