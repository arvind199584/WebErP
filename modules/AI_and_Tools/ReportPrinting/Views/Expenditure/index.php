<!DOCTYPE html>
<html>
<head>
    <title>Expenditure Report</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>Agreement Expenditure Report</h1>
    <p>Generate a chronological list of all payments made for an agreement.</p>

    <!-- ADDED target="_blank" TO OPEN IN NEW WINDOW -->
    <form method="GET" action="" target="_blank">
        <input type="hidden" name="action" value="print">
        <div class="form-group">
            <label>Select Agreement:</label>
            <select name="agreement_id" required>
                <option value="">-- Select Agreement --</option>
                <?php foreach ($agreements as $ag): ?>
                    <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['agreement_no']) ?> (<?= htmlspecialchars($ag['agency_name']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Generate & Print Report</button>
    </form>
</div>
</body>
</html>
