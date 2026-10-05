<?php /** @var array $bill */ ?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Bill</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Bill Status</h1>
    <form action="?action=edit&id=<?= $bill['id'] ?>" method="POST">
        <?= \App\Core\CSRFManager::getTokenInput() ?>
        <div class="form-group">
            <label>Bill No:</label>
            <input type="text" value="<?= htmlspecialchars($bill['office_bill_no']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Status:</label>
            <select name="status">
                <option value="Draft" <?= $bill['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                <option value="Processed" <?= $bill['status'] === 'Processed' ? 'selected' : '' ?>>Processed</option>
                <option value="Paid" <?= $bill['status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
            </select>
        </div>
        <div class="form-group">
            <label>Process Date:</label>
            <input type="date" name="process_date" value="<?= htmlspecialchars($bill['process_date'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Paid Date:</label>
            <input type="date" name="paid_date" value="<?= htmlspecialchars($bill['paid_date'] ?? '') ?>">
        </div>
        <button type="submit" class="btn">Update Bill</button>
    </form>
</div>
</body>
</html>
