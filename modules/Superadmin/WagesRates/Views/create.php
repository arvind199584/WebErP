<?php
/**
 * @var array $items An array of wage item records.
 */

// Group items by authority for the dynamic UI
$groupedItems = [];
foreach ($items as $item) {
    $groupedItems[$item['authority']][$item['json_key']] = $item['unit'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Wage Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 20px; }
        .container { max-width: 900px; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .unit-badge { font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Create New Wage Order</h4>
        </div>
        <div class="card-body">
            <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="authority" class="form-label">Authority</label>
                        <select id="authority" name="authority" class="form-select" onchange="showRates(this.value)" required>
                            <option value="">Select Authority</option>
                            <?php foreach (array_keys($groupedItems) as $auth): ?>
                                <option value="<?= htmlspecialchars($auth) ?>"><?= htmlspecialchars($auth) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="letter_no" class="form-label">Letter No</label>
                        <input type="text" id="letter_no" name="letter_no" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="letter_date" class="form-label">Letter Date</label>
                        <input type="date" id="letter_date" name="letter_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="valid_from" class="form-label">Effective From</label>
                        <input type="date" id="valid_from" name="valid_from" class="form-control" required>
                    </div>
                </div>

                <hr>

                <?php foreach ($groupedItems as $auth => $categories): ?>
                    <div id="rates-<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '', $auth)) ?>" class="rate-container d-none">
                        <h5>Rates for <?= htmlspecialchars($auth) ?></h5>
                        <div class="row">
                            <?php foreach ($categories as $key => $unit): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-flex justify-content-between">
                                        <?= htmlspecialchars($key) ?>
                                        <span class="badge bg-secondary unit-badge"><?= htmlspecialchars($unit) ?></span>
                                    </label>
                                    <input type="number" step="0.01" name="rates[<?= htmlspecialchars($key) ?>]" class="form-control rate-input" placeholder="Enter rate">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="mt-4 text-end">
                    <a href="?action=list" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Wage Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showRates(authority) {
        // Hide all containers
        document.querySelectorAll('.rate-container').forEach(el => el.classList.add('d-none'));
        // Disable all inputs to prevent submitting wrong authority data
        document.querySelectorAll('.rate-input').forEach(el => el.disabled = true);

        if (authority) {
            const id = 'rates-' + authority.replace(/[^a-zA-Z0-9]/g, '');
            const container = document.getElementById(id);
            if (container) {
                container.classList.remove('d-none');
                container.querySelectorAll('.rate-input').forEach(el => el.disabled = false);
            }
        }
    }
</script>

</body>
</html>
