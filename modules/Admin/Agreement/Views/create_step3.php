<?php
/**
 * @var array $data
 * @var \App\Modules\AA_ES\DTO\AA_ES_DTO $aa_es
 * @var string|null $predecessorEndDate
 */
$manpowerPeriod = $aa_es->type === 'Manpower' ? ($aa_es->boq['Period'] ?? 0) : 0;

// Logic for continuity
$suggestedFrom = $data['period_from'] ?? '';
$isLocked = false;

if ($predecessorEndDate) {
    $suggestedFrom = $predecessorEndDate;
    $isLocked = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Agreement - Step 3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 30px; }
        .card { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .step-header { background-color: #198754; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
        .locked-info { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
    <script>
        const manpowerPeriod = <?= $manpowerPeriod ?>;

        function predictToDate() {
            if (manpowerPeriod > 0) {
                const fromInput = document.getElementById('period_from');
                const fromDate = new Date(fromInput.value);
                
                if (!isNaN(fromDate.getTime())) {
                    // Add months
                    fromDate.setMonth(fromDate.getMonth() + manpowerPeriod);
                    // Subtract one day
                    fromDate.setDate(fromDate.getDate() - 1);

                    // Format to YYYY-MM-DD
                    const year = fromDate.getFullYear();
                    const month = String(fromDate.getMonth() + 1).padStart(2, '0');
                    const day = String(fromDate.getDate()).padStart(2, '0');

                    document.getElementById('period_to').value = `${year}-${month}-${day}`;
                }
            }
        }

        window.onload = function() {
            if (manpowerPeriod > 0 && document.getElementById('period_from').value) {
                predictToDate();
            }
        };
    </script>
</head>
<body>

<div class="container" style="max-width: 800px;">
    <div class="card">
        <div class="step-header">
            <h4 class="mb-0">Step 3: Final Settings</h4>
            <small>Define Period and Statutory Compliance</small>
        </div>
        <div class="card-body">
            
            <?php if ($isLocked): ?>
                <div class="locked-info">
                    <strong>Continuity Mode:</strong> Start date is locked to 1 day after the predecessor agreement ends (<?= date('d-M-Y', strtotime($predecessorEndDate)) ?>).
                </div>
            <?php endif; ?>

            <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <input type="hidden" name="step" value="3">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="period_from" class="form-label">Period From</label>
                        <input type="date" id="period_from" name="period_from" 
                               value="<?= htmlspecialchars($suggestedFrom) ?>" 
                               class="form-control"
                               onchange="predictToDate()"
                               <?= $isLocked ? 'readonly tabindex="-1"' : '' ?>
                               required>
                        <?php if ($isLocked): ?>
                            <!-- Hidden input because readonly fields might be bypassed, but more importantly to ensure POST data -->
                            <input type="hidden" name="period_from" value="<?= htmlspecialchars($suggestedFrom) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="period_to" class="form-label">Period To</label>
                        <input type="date" id="period_to" name="period_to" 
                               value="<?= htmlspecialchars($data['period_to'] ?? '') ?>" 
                               class="form-control" 
                               required>
                        <?php if ($manpowerPeriod > 0): ?>
                            <div class="form-text text-success">Auto-calculated for <?= $manpowerPeriod ?> months.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label d-block">Statutory Compliances</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="esic" name="bill_type_esic" <?= (isset($data['bill_type_esic'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="esic">ESIC Applicable</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="epf" name="bill_type_epf" <?= (isset($data['bill_type_epf'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="epf">EPF Applicable</label>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="?action=showCreateForm&step=2" class="btn btn-outline-secondary">Back</a>
                    <button type="submit" class="btn btn-success">Complete & Create Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
