<?php
/**
 * @var array $aa_es_list List of approved AA&ES.
 * @var array $agencies List of agencies.
 * @var array $activeAgreements List of active agreements for predecessor selection.
 * @var array $data Previous session data.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Agreement - Step 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 30px; }
        .card { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .step-header { background-color: #0d6efd; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
        #predecessor-group { display: none; background-color: #e7f3ff; padding: 15px; border-radius: 5px; border: 1px solid #b6d4fe; }
    </style>
</head>
<body>

<div class="container" style="max-width: 800px;">
    <div class="card">
        <div class="step-header">
            <h4 class="mb-0">Step 1: Agreement Binding</h4>
            <small>Link AA & ES to an Agency</small>
        </div>
        <div class="card-body">
            <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <input type="hidden" name="step" value="1">

                <div class="mb-3">
                    <label for="aa_es_id" class="form-label">Select Administrative Approval (AA & ES)</label>
                    <select id="aa_es_id" name="aa_es_id" class="form-select" onchange="togglePredecessor(this)" required>
                        <option value="">Select AA & ES</option>
                        <?php foreach ($aa_es_list as $aa_es): ?>
                            <option value="<?= $aa_es['id'] ?>" 
                                    data-type="<?= htmlspecialchars($aa_es['type']) ?>"
                                    <?= (isset($data['aa_es_id']) && $data['aa_es_id'] == $aa_es['id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($aa_es['type']) ?>] <?= htmlspecialchars($aa_es['alias']) ?> - <?= htmlspecialchars(substr($aa_es['sub_head'], 0, 60)) ?>...
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="agency_id" class="form-label">Executing Agency</label>
                    <select id="agency_id" name="agency_id" class="form-select" required>
                        <option value="">Select Agency</option>
                        <?php foreach ($agencies as $agency): ?>
                            <option value="<?= $agency['id'] ?>" <?= (isset($data['agency_id']) && $data['agency_id'] == $agency['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agency['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="agreement_no" class="form-label">Agreement Number</label>
                    <input type="text" id="agreement_no" name="agreement_no" class="form-control" value="<?= htmlspecialchars($data['agreement_no'] ?? '') ?>" required>
                </div>

                <div id="predecessor-group" class="mb-3">
                    <label for="predecessor_id" class="form-label fw-bold">Is this a continuation of an existing Agreement?</label>
                    <select id="predecessor_id" name="predecessor_id" class="form-select">
                        <option value="">No - This is a Fresh/Independent Agreement</option>
                        <?php foreach ($activeAgreements as $agg): ?>
                            <option value="<?= $agg['id'] ?>" 
                                    data-end="<?= $agg['end_date'] ?>"
                                    <?= (isset($data['predecessor_id']) && $data['predecessor_id'] == $agg['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agg['agreement_no']) ?> (Ends: <?= date('d-M-Y', strtotime($agg['end_date'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-primary">
                        <strong>Note:</strong> Selecting a predecessor will automatically migrate all active employees and ensure zero-day gap continuity.
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?action=list" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Next: Financial Terms &rarr;</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePredecessor(select) {
        const type = select.options[select.selectedIndex].getAttribute('data-type');
        const group = document.getElementById('predecessor-group');
        if (type === 'Manpower') {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
            document.getElementById('predecessor_id').value = '';
        }
    }
    
    // Initial call on page load if data is already set
    window.onload = function() {
        togglePredecessor(document.getElementById('aa_es_id'));
    };
</script>

</body>
</html>
