<?php
/** @var array|null $obData */
/** @var int $agreementId */
/** @var array $aaesBoq */
$itemwiseData = isset($obData['itemwise_expenditure']) ? json_decode($obData['itemwise_expenditure'], true) : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Opening Balance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; }
        fieldset { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        legend { font-weight: 600; padding: 0 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: 600; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Opening Balance</h1>
    <form method="POST" action="?action=edit">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="agreement_id" value="<?= $agreementId ?>">

        <fieldset>
            <legend>Financials (Total Paid)</legend>
            <div class="form-grid">
                <div class="form-group"><label>Items Paid:</label><input type="number" step="0.01" name="items_paid" value="<?= htmlspecialchars($obData['items_paid'] ?? '0.00') ?>"></div>
                <div class="form-group"><label>GST Paid:</label><input type="number" step="0.01" name="gst_paid" value="<?= htmlspecialchars($obData['gst_paid'] ?? '0.00') ?>"></div>
                <div class="form-group"><label>ESIC Paid:</label><input type="number" step="0.01" name="esic_paid" value="<?= htmlspecialchars($obData['esic_paid'] ?? '0.00') ?>"></div>
                <div class="form-group"><label>EPF Paid:</label><input type="number" step="0.01" name="epf_paid" value="<?= htmlspecialchars($obData['epf_paid'] ?? '0.00') ?>"></div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Dates Upto</legend>
            <div class="form-grid">
                <div class="form-group"><label>Items Upto:</label><input type="date" name="items_upto" value="<?= htmlspecialchars($obData['items_upto'] ?? '') ?>"></div>
                <div class="form-group"><label>GST Upto:</label><input type="date" name="gst_upto" value="<?= htmlspecialchars($obData['gst_upto'] ?? '') ?>"></div>
                <div class="form-group"><label>ESIC Upto:</label><input type="date" name="esic_upto" value="<?= htmlspecialchars($obData['esic_upto'] ?? '') ?>"></div>
                <div class="form-group"><label>EPF Upto:</label><input type="date" name="epf_upto" value="<?= htmlspecialchars($obData['epf_upto'] ?? '') ?>"></div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Gap Periods</legend>
            <div class="form-grid">
                <div class="form-group"><label>Items Gap (From/To):</label><input type="text" name="items_gap" placeholder="e.g., 2025-01-01,2025-03-31" value="<?= htmlspecialchars($obData['items_gap'] ?? '') ?>"></div>
                <div class="form-group"><label>GST Gap (From/To):</label><input type="text" name="gst_gap" value="<?= htmlspecialchars($obData['gst_gap'] ?? '') ?>"></div>
                <div class="form-group"><label>ESIC Gap (From/To):</label><input type="text" name="esic_gap" value="<?= htmlspecialchars($obData['esic_gap'] ?? '') ?>"></div>
                <div class="form-group"><label>EPF Gap (From/To):</label><input type="text" name="epf_gap" value="<?= htmlspecialchars($obData['epf_gap'] ?? '') ?>"></div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Item-wise Expenditure</legend>
            <div class="form-grid">
                <?php foreach ($aaesBoq['Items'] ?? [] as $item):
                    $itemName = $item['description'];
                ?>
                <div class="form-group">
                    <label><?= htmlspecialchars($itemName) ?>:</label>
                    <input type="number" step="0.01" name="itemwise_expenditure[<?= htmlspecialchars($itemName) ?>]" value="<?= htmlspecialchars($itemwiseData[$itemName] ?? '0.00') ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="submit">Save Opening Balance</button>
    </form>
</div>
</body>
</html>
