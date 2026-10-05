<?php /** @var array $agreements */ ?>
<!DOCTYPE html>
<html>
<head>
    <title>Agreement Opening Balances</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        table.table2 { width: 100%; border-collapse: collapse; }
        .table2 th, .table2 td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    </style>
</head>
<body>
<div class="container">
    <h1>Agreement Opening Balances</h1>
    <table class="table2">
        <thead><tr><th>Agreement No.</th><th>Last Updated</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($agreements as $ag): ?>
            <tr>
                <td><?= htmlspecialchars($ag['agreement_no']) ?></td>
                <td><?= htmlspecialchars($ag['updated_at'] ?? 'Not Set') ?></td>
                <td><a href="?action=edit&agreement_id=<?= $ag['id'] ?>">View / Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
