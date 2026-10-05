<!DOCTYPE html>
<html>
<head>
    <title>AA & ES Printing</title>
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
    <h1>AA & ES Calculation Sheet</h1>
    <p>Select an AA & ES record to generate the calculation sheet.</p>
    <form method="GET" action="" target="_blank">
        <input type="hidden" name="action" value="print">
        <div class="form-group">
            <label>Select AA & ES:</label>
            <select name="id" required>
                <option value="">-- Select --</option>
                <?php foreach ($aa_es_list as $ae): ?>
                    <option value="<?= $ae['id'] ?>"><?= htmlspecialchars($ae['sub_head']) ?> (<?= htmlspecialchars($ae['budget_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Generate & Print Sheet</button>
    </form>
</div>
</body>
</html>
