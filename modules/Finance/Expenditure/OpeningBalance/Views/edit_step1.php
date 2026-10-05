<?php
/** @var array|null $obData */
/** @var int $agreementId */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit OB - Step 1: Financials</title>
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
    <h1>Step 1: Financials & Dates Upto</h1>
    <form method="POST" action="?action=edit">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="1">
        <input type="hidden" name="agreement_id" value="<?= $agreementId ?>">
        <fieldset>
            <legend>Total Paid</legend>
            <div class="form-grid">
                <div><label>Items Paid:</label><input type="number" step="0.01" name="items_paid" value="<?= htmlspecialchars($obData['items_paid'] ?? '0.00') ?>"></div>
                <div><label>GST Paid:</label><input type="number" step="0.01" name="gst_paid" value="<?= htmlspecialchars($obData['gst_paid'] ?? '0.00') ?>"></div>
                <div><label>ESIC Paid:</label><input type="number" step="0.01" name="esic_paid" value="<?= htmlspecialchars($obData['esic_paid'] ?? '0.00') ?>"></div>
                <div><label>EPF Paid:</label><input type="number" step="0.01" name="epf_paid" value="<?= htmlspecialchars($obData['epf_paid'] ?? '0.00') ?>"></div>
            </div>
        </fieldset>
        <fieldset>
            <legend>Dates Upto</legend>
            <div class="form-grid">
                <div><label>Items Upto:</label><input type="date" name="items_upto" value="<?= htmlspecialchars($obData['items_upto'] ?? '') ?>"></div>
                <div><label>GST Upto:</label><input type="date" name="gst_upto" value="<?= htmlspecialchars($obData['gst_upto'] ?? '') ?>"></div>
                <div><label>ESIC Upto:</label><input type="date" name="esic_upto" value="<?= htmlspecialchars($obData['esic_upto'] ?? '') ?>"></div>
                <div><label>EPF Upto:</label><input type="date" name="epf_upto" value="<?= htmlspecialchars($obData['epf_upto'] ?? '') ?>"></div>
            </div>
        </fieldset>
        <button type="submit">Next &rarr;</button>
    </form>
</div>
</body>
</html>
