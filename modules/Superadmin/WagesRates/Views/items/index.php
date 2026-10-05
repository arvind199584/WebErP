<?php
/**
 * @var array $items An array of items with 'current_wage'.
 * @var string $selectedDate The date used for calculation.
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
    <title>Manage Wage Items</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 5px; color: white; display: inline-block; margin-left: 10px; }
        .btn-create { background-color: #28a745; }
        .btn-edit { background-color: #007bff; }
        .btn-delete { background-color: #dc3545; border: none; font-size: 14px; cursor: pointer; }
        .btn-secondary { background-color: #6c757d; }
        .btn-dashboard { background-color: #343a40; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; padding: 20px; }
        table.table2 { width: 100%; border-collapse: collapse; }
        .table2 th, .table2 td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table2 thead { background-color: #f2f2f2; }
        .table2 tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .table2 tbody tr:hover { background-color: #f1f1f1; }
        .actions form { display: inline; }
        .actions a, .actions .btn-delete { margin-right: 8px; }
        .top-actions { display: flex; align-items: center; }
        .filter-bar { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; align-items: center; }
        .filter-bar label { margin-right: 10px; font-weight: bold; }
        .filter-bar input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-right: 10px; }
        .filter-bar button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Wage Items (Designations)</h1>
        <div class="top-actions">
            <!-- Back to Dashboard is visible to everyone -->
            <!-- <a href="/index.php" class="btn btn-dashboard">Back to Dashboard</a> -->
            <!-- Sidebar handles navigation now, but keeping this if needed or removing as per previous request to rely on sidebar -->

            <?php if ($isSuperuser): ?>
                <a href="/modules/WagesRates/Controller/WageRateController.php" class="btn btn-secondary">Manage Wage Orders</a>
                <a href="?action=showCreateForm" class="btn btn-create">Create New Item</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="GET" action="?" class="filter-bar">
        <input type="hidden" name="action" value="list">
        <label for="date">Check Rates for Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
        <button type="submit">Update Rates</button>
    </form>

    <table class="table2">
        <thead>
            <tr>
                <th style="width: 60px;">S. No.</th>
                <th>Designation</th>
                <th>Authority</th>
                <th>Current Wages (<?= htmlspecialchars($selectedDate) ?>)</th>
                <?php if ($isSuperuser): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?= $isSuperuser ? 5 : 4 ?>" class="no-data">No items found.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($items as $item): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['authority']) ?></td>
                        <td>
                            <?php if ($item['current_wage'] !== null): ?>
                                <strong><?= number_format((float)$item['current_wage'], 2) ?></strong>
                            <?php else: ?>
                                <span style="color: #999;">N/A (No active order)</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isSuperuser): ?>
                            <td class="actions">
                                <a href="?action=showEditForm&id=<?= $item['id'] ?>" class="btn btn-edit">Edit</a>
                                <form action="?action=delete&id=<?= $item['id'] ?>" method="POST" onsubmit="return confirm('Are you sure?');">
                                    <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                                    <button type="submit" class="btn btn-delete">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
