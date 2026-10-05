<?php /** @var array $agreements */ ?>
<!DOCTYPE html>
<html>
<head>
    <title>Propose Deviation - Step 1</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <h1>Propose Deviation (Step 1/2)</h1>
    <form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="1">

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
            <label>Sub Head:</label>
            <input type="text" name="sub_head" required placeholder="e.g., Increase in Security Guard quantity">
        </div>

        <div class="form-group">
            <label>Justification:</label>
            <textarea name="justification" rows="3"></textarea>
        </div>

        <button type="submit" class="btn">Next: Build BOQ</button>
    </form>
</div>
</body>
</html>
