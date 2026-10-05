<?php
/** @var array $agreements */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Batch Payment Entry</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, input, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .row { display: flex; gap: 15px; }
        .row > div { flex: 1; }
        .btn { width: 100%; padding: 12px; background-color: #ffc107; color: #212529; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; }
        .info { background-color: #e7f3ff; color: #0c5460; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
<div class="container">
    <h1>Batch Payment Entry</h1>
    <p><a href="?action=index">&laquo; Back to Menu</a></p>

    <div class="info">
        <strong>ℹ️ Info:</strong> This tool calculates the total wages due for the selected period and posts a single payment (debit) for each employee on the specified payment date.
    </div>

    <form method="POST" action="?action=processBatchDebit">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label>Select Agreement:</label>
            <select name="agreement_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($agreements as $ag): ?>
                    <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['agreement_no']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Wages From:</label>
                <input type="date" name="wages_from" required>
            </div>
            <div class="form-group">
                <label>Wages To:</label>
                <input type="date" name="wages_to" required>
            </div>
        </div>

        <div class="form-group">
            <label>Actual Payment Date:</label>
            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
            <label>Remark:</label>
            <textarea name="remark" rows="2" placeholder="e.g. Salary disbursed for Oct-Dec 2025"></textarea>
        </div>

        <button type="submit" class="btn" onclick="return confirm('This will calculate and post payments for the selected period. Continue?')">Process Batch Payment</button>
    </form>
</div>
</body>
</html>
