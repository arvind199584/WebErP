<?php $isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Work Orders</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 1200px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .btn-create { background-color: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        thead { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Work Orders</h1>
        <a href="?action=showCreateForm" class="btn-create">New Work Order</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>S.No.</th>
                <th>Work Order No</th>
                <th>Agency</th>
                <th>AA&ES Alias</th>
                <th>Tendered Amount</th>
                <th>Status</th>
                <?php if ($isSuperuser): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($workOrders)): ?>
                <tr><td colspan="<?= $isSuperuser ? 7 : 6 ?>">No records found.</td></tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($workOrders as $order): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($order['work_order_no']) ?></td>
                        <td><?= htmlspecialchars($order['agency_name']) ?></td>
                        <td><?= htmlspecialchars($order['aa_es_alias']) ?></td>
                        <td><?= number_format((float)$order['tendered_amount'], 2) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <?php if ($isSuperuser): ?><td>Edit | Delete</td><?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
