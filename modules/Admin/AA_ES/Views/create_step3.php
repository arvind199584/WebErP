<?php
/**
 * @var array $data All collected data.
 * @var float $calculatedAmount The calculated AA_ES amount.
 * @var float $availableBudget The available budget amount.
 * @var string|null $error Error message.
 */
$isBudgetSufficient = $availableBudget >= $calculatedAmount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create AA & ES - Step 3</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .step-indicator { text-align: center; margin-bottom: 30px; color: #777; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }

        .review-card { background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        .review-row { display: flex; margin-bottom: 12px; }
        .review-label { font-weight: 600; width: 200px; color: #555; }
        .review-value { flex: 1; color: #333; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box; }

        .amount-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #ddd; }
        .amount-box.success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .amount-box.danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }

        .form-actions { text-align: right; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; display: inline-block; margin-left: 10px; font-size: 14px; transition: background 0.2s; }
        .btn-submit { background-color: #28a745; }
        .btn-submit:disabled { background-color: #6c757d; cursor: not-allowed; opacity: 0.7; }
        .btn-back { background-color: #6c757d; }

        .error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        pre { background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <h1>Review & Submit</h1>
    <div class="step-indicator">Step 3 of 3</div>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="3">

        <div class="review-card">
            <div class="form-group">
                <label for="alias">Generated Alias (You can edit this):</label>
                <input type="text" id="alias" name="alias" value="<?= htmlspecialchars($data['alias']) ?>" required>
                <small style="color: #666;">This short name will be used to identify the AA & ES.</small>
            </div>
        </div>

        <div class="review-card">
            <div class="review-row">
                <span class="review-label">Type:</span>
                <span class="review-value"><?= htmlspecialchars($data['type']) ?></span>
            </div>
            <div class="review-row">
                <span class="review-label">Sub Head:</span>
                <span class="review-value"><?= nl2br(htmlspecialchars($data['sub_head'])) ?></span>
            </div>
            <div class="review-row">
                <span class="review-label">BOQ Structure:</span>
                <span class="review-value" style="color: green;">✔ Valid</span>
            </div>
        </div>

        <div class="amount-box <?= $isBudgetSufficient ? 'success' : 'danger' ?>">
            <div class="review-row">
                <span class="review-label">Available Budget:</span>
                <span class="review-value">Rs. <?= number_format($availableBudget, 2) ?></span>
            </div>
            <div class="review-row">
                <span class="review-label">This AA & ES Amount:</span>
                <span class="review-value"><strong>Rs. <?= number_format($calculatedAmount, 2) ?></strong></span>
            </div>
            <?php if (!$isBudgetSufficient): ?>
                <div style="margin-top: 10px; font-weight: bold;">
                    ⚠ Insufficient Budget! You cannot proceed.
                </div>
            <?php endif; ?>
        </div>

        <div class="review-card">
            <span class="review-label" style="display:block; margin-bottom:10px;">BOQ Summary:</span>
            <pre class="review-value"><?= htmlspecialchars(json_encode($data['boq'], JSON_PRETTY_PRINT)) ?></pre>
        </div>

        <div class="form-actions">
            <a href="?action=showCreateForm&step=2" class="btn btn-back">Back to BOQ</a>
            <button type="submit" class="btn btn-submit" <?= !$isBudgetSufficient ? 'disabled' : '' ?>>Confirm & Create AA & ES</button>
        </div>
    </form>
</div>

</body>
</html>
