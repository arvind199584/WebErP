<!DOCTYPE html>
<html>
<head>
    <title>Wages Verification</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>Wages Verification Report</h1>
    <p>Generate a Due/Drawn/Balance statement for an agreement.</p>
    <form method="GET" action="" target="_blank">
        <input type="hidden" name="action" value="print">
        <div class="form-group">
            <label>Select Agreement:</label>
            <select name="agreement_id" required>
                <option value="">-- Select Agreement --</option>
                <?php foreach ($agreements as $ag): ?>
                    <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['agreement_no']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Due From:</label>
            <input type="date" name="due_from" required>
        </div>
        <div class="form-group">
            <label>Due To:</label>
            <input type="date" name="due_to" required>
        </div>
        <div class="form-group">
            <label>Drawn Up To:</label>
            <input type="date" name="drawn_on" value="<?= date('Y-m-d') ?>" required>
        </div>
        <button type="submit" class="btn">Generate & Print Report</button>
    </form>
</div>
</body>
</html>
