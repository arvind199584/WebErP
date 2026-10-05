<!DOCTYPE html>
<html>
<head>
    <title>Add Extra Item</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Add Extra Item</h1>
    <form method="POST" action="?action=create">
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
            <label>Sub Head:</label>
            <input type="text" name="sub_head" required placeholder="e.g., Providing additional security personnel">
        </div>

        <div class="form-group">
            <label>Justification:</label>
            <textarea name="justification" rows="4"></textarea>
        </div>

        <p style="color: #666; font-style: italic;">Note: Extra Item BOQ Builder will be integrated in the next step.</p>

        <button type="submit" class="btn">Submit Proposal</button>
    </form>
</div>
</body>
</html>
