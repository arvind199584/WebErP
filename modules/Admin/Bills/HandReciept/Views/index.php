<!DOCTYPE html>
<html>
<head>
    <title>Hand Receipts</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1100px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
        .right-align { text-align: right; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-yes { background-color: #d4edda; color: #155724; }
        .badge-no { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Hand Receipts</h1>
        <a href="?action=showCreateForm" class="btn">+ New Hand Receipt</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div style="padding: 10px; background: #d4edda; color: #155724; margin-bottom: 20px;"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Budget Code</th>
                <th>Description</th>
                <th class="right-align">Net Amount</th>
                <th>Budget Imp.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($receipts as $r): ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($r['created_at'])) ?></td>
                <td><?= htmlspecialchars($r['category']) ?></td>
                <td><?= htmlspecialchars($r['budget_code']) ?></td>
                <td><?= htmlspecialchars($r['description']) ?></td>
                <td class="right-align">₹ <?= number_format((float)$r['net_amount'], 2) ?></td>
                <td>
                    <span class="badge <?= $r['budget_implication'] ? 'badge-yes' : 'badge-no' ?>">
                        <?= $r['budget_implication'] ? 'YES' : 'NO' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
