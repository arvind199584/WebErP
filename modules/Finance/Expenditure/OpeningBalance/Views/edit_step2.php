<?php
/** @var array|null $obData */
/** @var int $agreementId */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit OB - Step 2: Gap Periods</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { font-weight: 600; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Step 2: Gap Periods</h1>
    <form method="POST" action="?action=edit">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="agreement_id" value="<?= $agreementId ?>">
        <fieldset>
            <legend>Items Gap Period</legend>
            <div class="form-grid">
                <div><label>From:</label><input type="date" name="items_gap_from" value="<?= htmlspecialchars(explode(',', $obData['items_gap'] ?? ',')[0]) ?>"></div>
                <div><label>To:</label><input type="date" name="items_gap_to" value="<?= htmlspecialchars(explode(',', $obData['items_gap'] ?? ',')[1]) ?>"></div>
            </div>
        </fieldset>
        <fieldset>
            <legend>GST Gap Period</legend>
            <div class="form-grid">
                <div><label>From:</label><input type="date" name="gst_gap_from" value="<?= htmlspecialchars(explode(',', $obData['gst_gap'] ?? ',')[0]) ?>"></div>
                <div><label>To:</label><input type="date" name="gst_gap_to" value="<?= htmlspecialchars(explode(',', $obData['gst_gap'] ?? ',')[1]) ?>"></div>
            </div>
        </fieldset>
        <fieldset>
            <legend>ESIC Gap Period</legend>
            <div class="form-grid">
                <div><label>From:</label><input type="date" name="esic_gap_from" value="<?= htmlspecialchars(explode(',', $obData['esic_gap'] ?? ',')[0]) ?>"></div>
                <div><label>To:</label><input type="date" name="esic_gap_to" value="<?= htmlspecialchars(explode(',', $obData['esic_gap'] ?? ',')[1]) ?>"></div>
            </div>
        </fieldset>
        <fieldset>
            <legend>EPF Gap Period</legend>
            <div class="form-grid">
                <div><label>From:</label><input type="date" name="epf_gap_from" value="<?= htmlspecialchars(explode(',', $obData['epf_gap'] ?? ',')[0]) ?>"></div>
                <div><label>To:</label><input type="date" name="epf_gap_to" value="<?= htmlspecialchars(explode(',', $obData['epf_gap'] ?? ',')[1]) ?>"></div>
            </div>
        </fieldset>
        <button type="submit">Next &rarr;</button>
    </form>
</div>
</body>
</html>
