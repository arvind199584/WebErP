<!DOCTYPE html>
<html>
<head>
    <title>Temporary Members</title>
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px; }
        .btn-primary { background: #007bff; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border: 1px solid #dee2e6; text-align: left; }
        th { background: #f8f9fa; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Temporary Members (Revenue)</h1>
        <a href="?action=showCreate" class="btn btn-primary">+ Add New Member</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>TM No</th>
                <th>Name</th>
                <th>Validity</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['tm_no']) ?></td>
                <td><?= htmlspecialchars($m['name']) ?></td>
                <td><?= htmlspecialchars($m['validity'] ?? 'N/A') ?></td>
                <td>₹<?= number_format((float)$m['payment_amount'], 2) ?></td>
                <td><?= htmlspecialchars($m['payment_reference'] ?? '-') ?></td>
                <td class="actions">
                    <a href="?action=print_bill&id=<?= $m['id'] ?>" target="_blank">Print</a>
                    <a href="?action=showEdit&id=<?= $m['id'] ?>">Edit</a>
                    <a href="?action=delete&id=<?= $m['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($members)): ?>
                <tr><td colspan="6" style="text-align: center;">No members found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
