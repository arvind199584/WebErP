<?php
/**
 * @var \App\Modules\AA_ES\DTO\AA_ES_DTO $aa_es The AA_ES object.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View AA & ES</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 20px; }
        .detail-row { display: flex; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .detail-label { font-weight: bold; width: 200px; }
        .detail-value { flex: 1; }
        .amount-row { font-size: 1.1em; color: #2c3e50; background-color: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
        .btn-download { display: inline-block; padding: 5px 10px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <h1>AA & ES Details</h1>

    <div class="detail-row">
        <span class="detail-label">Alias:</span>
        <span class="detail-value"><strong><?= htmlspecialchars($aa_es->alias) ?></strong></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Sub Head:</span>
        <span class="detail-value"><?= nl2br(htmlspecialchars($aa_es->sub_head)) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Type:</span>
        <span class="detail-value"><?= htmlspecialchars($aa_es->type) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Status:</span>
        <span class="detail-value">
            <?= htmlspecialchars($aa_es->status) ?>
            <?php if ($aa_es->status === 'Approved' && $aa_es->approval_pdf): ?>
                <a href="<?= htmlspecialchars($aa_es->approval_pdf) ?>" target="_blank" class="btn-download">Download Order</a>
            <?php endif; ?>
        </span>
    </div>
    <?php if ($aa_es->approved_at): ?>
    <div class="detail-row">
        <span class="detail-label">Approved At:</span>
        <span class="detail-value"><?= htmlspecialchars($aa_es->approved_at) ?></span>
    </div>
    <?php endif; ?>

    <h3>Financials</h3>
    <div class="detail-row amount-row">
        <span class="detail-label">Estimated Cost:</span>
        <span class="detail-value">Rs. <?= number_format($aa_es->estimated_cost, 2) ?></span>
    </div>
    <div class="detail-row amount-row">
        <span class="detail-label">Justified Amount:</span>
        <span class="detail-value">Rs. <?= number_format($aa_es->justified_amount, 2) ?></span>
    </div>
    <div class="detail-row amount-row" style="background-color: #e8f5e9; border: 1px solid #c3e6cb;">
        <span class="detail-label">AA & ES Amount:</span>
        <span class="detail-value"><strong>Rs. <?= number_format($aa_es->aa_es_amount, 2) ?></strong></span>
    </div>

    <h3>BOQ Details</h3>
    <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto;"><?= htmlspecialchars(json_encode($aa_es->boq, JSON_PRETTY_PRINT)) ?></pre>

    <a href="?action=list" class="btn-back">Back to List</a>
</div>

</body>
</html>
