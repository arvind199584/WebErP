<?php
/** @var array $agreements */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recalculate Wages</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; }
        .warning { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; border: 1px solid #ffeeba; }
    </style>
</head>
<body>
<div class="container">
    <h1>Recalculate Wages</h1>
    <p><a href="?action=index">&laquo; Back to Menu</a></p>

    <div class="warning">
        <strong>⚠️ Warning:</strong> This operation will <strong>DELETE</strong> all existing wage credit entries for the selected period and agreement, and then regenerate them based on current attendance and rates.
    </div>

    <form method="POST" action="?action=recalculate">
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

        <div class="form-group">
            <label>From Date:</label>
            <input type="date" name="from_date" required>
        </div>

        <div class="form-group">
            <label>To Date:</label>
            <input type="date" name="to_date" required>
        </div>

        <button type="submit" class="btn" onclick="return confirm('Are you absolutely sure? This will overwrite existing wage data.')">Start Recalculation</button>
    </form>
</div>
</body>
</html>
