<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Machine Repair & Servicing</title>
</head>
<body>
<div class="container-fluid py-3" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">✏️ Edit Machine Repair & Servicing</h1>
            <p class="text-muted mb-0">Modify details of an existing machine repair entry or job card.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=jobCards" class="btn btn-outline-primary">📋 Job Cards Register</a>
            <a href="?action=list" class="btn btn-outline-secondary">← Back to List</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="?action=update">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$repair['id']); ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="machine_id" class="form-label fw-bold">Machine <span class="text-danger">*</span></label>
                        <select name="machine_id" id="machine_id" class="form-select" required>
                            <option value="">-- Choose Machine --</option>
                            <?php foreach ($machines as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($repair['machine_id'] == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="work_type" class="form-label fw-bold">Work Category</label>
                        <select name="work_type" id="work_type" class="form-select">
                            <option value="service_done" <?php echo (($repair['work_type'] ?? '') === 'service_done') ? 'selected' : ''; ?>>🛠️ Service Done (Routine)</option>
                            <option value="repair" <?php echo (($repair['work_type'] ?? 'repair') === 'repair') ? 'selected' : ''; ?>>➕ General Repair / Breakdown</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="job_card_no" class="form-label fw-bold text-primary">Job Card Number</label>
                        <input type="text" name="job_card_no" id="job_card_no" class="form-control" value="<?php echo htmlspecialchars((string)($repair['job_card_no'] ?? '')); ?>" placeholder="e.g. JC-2026-001">
                    </div>

                    <div class="col-md-6">
                        <label for="repair_date" class="form-label fw-bold">Date of Work <span class="text-danger">*</span></label>
                        <input type="date" name="repair_date" id="repair_date" class="form-control" value="<?php echo htmlspecialchars((string)$repair['repair_date']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="running_hours" class="form-label fw-bold">Meter Reading (Running Hours)</label>
                        <input type="number" step="0.1" name="running_hours" id="running_hours" class="form-control" value="<?php echo htmlspecialchars((string)($repair['running_hours'] ?? '')); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="cost" class="form-label fw-bold">Total Work Cost (₹)</label>
                        <input type="number" step="0.01" name="cost" id="cost" class="form-control" value="<?php echo htmlspecialchars((string)($repair['cost'] ?? '0.00')); ?>">
                    </div>

                    <div class="col-md-12">
                        <label for="work_done" class="form-label fw-bold">Work Done / Details <span class="text-danger">*</span></label>
                        <textarea name="work_done" id="work_done" rows="3" class="form-control" required><?php echo htmlspecialchars($repair['work_done']); ?></textarea>
                    </div>

                    <!-- Line Items Display (If parts were issued under this repair) -->
                    <?php if (!empty($repair['items'])): ?>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark">⚙️ Spare Parts Issued under this Job Card</label>
                            <div class="table-responsive bg-light rounded border p-2">
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Part Number & Nomenclature</th>
                                            <th style="width: 150px;" class="text-center">Quantity Issued</th>
                                            <th style="width: 150px;" class="text-center">Current Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($repair['items'] as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(($item['part_no'] ? $item['part_no'] . ' - ' : '') . $item['nomenclature']); ?></strong>
                                                </td>
                                                <td class="text-center fw-bold">
                                                    <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'Pcs'); ?>
                                                </td>
                                                <td class="text-center text-muted">
                                                    <?php echo $item['current_stock']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'Pcs'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-12">
                        <label for="spare_parts_used" class="form-label fw-bold">Spare Parts Summary / Notes</label>
                        <textarea name="spare_parts_used" id="spare_parts_used" rows="2" class="form-control"><?php echo htmlspecialchars($repair['spare_parts_used'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="repaired_by" class="form-label fw-bold">Technician / Mechanic Name</label>
                        <input type="text" name="repaired_by" id="repaired_by" class="form-control" value="<?php echo htmlspecialchars($repair['repaired_by'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">Repair Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Completed" <?php echo ($repair['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="In Progress" <?php echo ($repair['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Pending Parts" <?php echo ($repair['status'] === 'Pending Parts') ? 'selected' : ''; ?>>Pending Parts</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label for="remarks" class="form-label fw-bold">Remarks</label>
                        <input type="text" name="remarks" id="remarks" class="form-control" value="<?php echo htmlspecialchars($repair['remarks'] ?? ''); ?>">
                    </div>

                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Repair Record</button>
                </div>

            </form>
        </div>
    </div>

</div>
</body>
</html>
