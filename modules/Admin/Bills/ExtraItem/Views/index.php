<!DOCTYPE html>
<html>
<head>
    <title>Extra Items</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1100px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
        .right-align { text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Extra Items</h1>
        <a href="?action=showCreateForm" class="btn">+ Add Extra Item</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div style="padding: 10px; background: #d4edda; color: #155724; margin-bottom: 20px;"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Agreement No</th>
                <th>Sub Head</th>
                <th>Type</th>
                <th class="right-align">Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($extraItems as $ei): ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($ei['created_at'])) ?></td>
                <td><?= htmlspecialchars($ei['agreement_no']) ?></td>
                <td><?= htmlspecialchars($ei['sub_head']) ?></td>
                <td><?= htmlspecialchars($ei['type']) ?></td>
                <td class="right-align">₹ <?= number_format((float)$ei['extra_item_amount'], 2) ?></td>
                <td><?= htmlspecialchars($ei['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
