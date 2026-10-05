<!DOCTYPE html>
<html>
<head>
    <title>Bill Printing</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>Bill Printing</h1>
    <p>Select a bill to generate the official 4-page document.</p>
    <form method="GET" action="" target="_blank">
        <input type="hidden" name="action" value="print">
        <div class="form-group">
            <label>Select Bill:</label>
            <select name="bill_id" required>
                <option value="">-- Select Bill --</option>
                <?php foreach ($bills as $bill): ?>
                    <option value="<?= $bill['id'] ?>"><?= htmlspecialchars($bill['office_bill_no']) ?> (<?= date('d.m.Y', strtotime($bill['bill_date'])) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Generate & Print Bill</button>
    </form>
</div>
</body>
</html>
