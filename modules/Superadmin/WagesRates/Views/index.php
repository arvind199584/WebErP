<?php
/**
 * @var array $orders An array of WageOrderDTO objects.
 * @var array $currentRates An array of current effective rates.
 * @var string|null $message A flash message from the session.
 * @var string|null $error A flash error message from the session.
 */
$isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Wage Rates</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 5px; color: white; display: inline-block; margin-left: 10px; }
        .btn-create { background-color: #28a745; }
        .btn-view { background-color: #007bff; }
        .btn-secondary { background-color: #6c757d; }
        .btn-dashboard { background-color: #343a40; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; padding: 20px; }
        table.table2 { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table2 th, .table2 td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table2 thead { background-color: #f2f2f2; }
        .table2 tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .table2 tbody tr:hover { background-color: #f1f1f1; }
        .top-actions { display: flex; align-items: center; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Wage Rates Management (Orders)</h1>
        <div class="top-actions">
            <!-- Back to Dashboard is visible to everyone -->
            <!-- <a href="/index.php" class="btn btn-dashboard">Back to Dashboard</a> -->

            <?php if ($isSuperuser): ?>
                <a href="/modules/WagesRates/Controller/WageItemController.php" class="btn btn-secondary">Manage Designations</a>
                <a href="?action=showCreateForm" class="btn btn-create">Create New Wage Order</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h2>Current Effective Rates</h2>
    <table class="table2">
        <thead>
            <tr>
                <th>Authority</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th>Base Rate</th>
                <th>Allowance</th>
                <th>Total Rate</th>
                <th>Effective From</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($currentRates)): ?>
                <tr>
                    <td colspan="7" class="no-data">No current rates found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($currentRates as $rate): ?>
                    <tr>
                        <td><?= htmlspecialchars($rate['authority']) ?></td>
                        <td><?= htmlspecialchars($rate['item_name']) ?></td>
                        <td><?= htmlspecialchars($rate['unit']) ?></td>
                        <td><?= htmlspecialchars($rate['base_rate']) ?></td>
                        <td><?= htmlspecialchars($rate['fixed_allowance']) ?></td>
                        <td><strong><?= htmlspecialchars($rate['total_rate']) ?></strong></td>
                        <td><?= htmlspecialchars($rate['effective_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Wage Order History</h2>
    <table class="table2">
        <thead>
            <tr>
                <th style="width: 60px;">S. No.</th>
                <th>Authority</th>
                <th>Letter No</th>
                <th>Letter Date</th>
                <th>Valid From</th>
                <th>Valid To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" class="no-data">No wage orders found.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($order['authority']) ?></td>
                        <td><?= htmlspecialchars($order['letter_no']) ?></td>
                        <td><?= htmlspecialchars($order['letter_date']) ?></td>
                        <td><?= htmlspecialchars($order['valid_from']) ?></td>
                        <td><?= htmlspecialchars($order['valid_to']) ?></td>
                        <td>
                            <a href="?action=view&id=<?= $order['id'] ?>" class="btn btn-view">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
