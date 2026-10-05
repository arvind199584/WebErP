<?php
/** @var array|null $obData */
/** @var int $agreementId */
/** @var array $aaesBoq */
$itemwiseData = isset($obData['itemwise_expenditure']) ? (is_string($obData['itemwise_expenditure']) ? json_decode($obData['itemwise_expenditure'], true) : $obData['itemwise_expenditure']) : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit OB - Step 3: Item-wise Expenditure</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        label { font-weight: 600; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Step 3: Item-wise Expenditure</h1>
    <form method="POST" action="?action=edit">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="agreement_id" value="<?= $agreementId ?>">
        <fieldset>
            <legend>Expenditure per Item</legend>
            <div class="form-grid">
                <?php foreach ($aaesBoq['Items'] ?? [] as $item):
                    $itemName = $item['description'];
                ?>
                <div>
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
