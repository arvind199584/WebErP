<?php
/**
 * @var array $budgets An array of budget data.
 * @var string|null $message Success message.
 * @var string|null $error Error message.
 */
$isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budget</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 5px; color: white; display: inline-block; }
        .btn-create { background-color: #28a745; }
        .btn-edit { background-color: #007bff; }
        .btn-delete { background-color: #dc3545; border: none; font-size: 14px; cursor: pointer; }
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
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Budget Management</h1>
        <a href="?action=showCreateForm" class="btn btn-create">Create New Budget Entry</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table class="table2">
        <thead>
            <tr>
                <th style="width: 60px;">S. No.</th>
                <th>Office</th>
                <th>Financial Year</th>
                <th>Code</th>
                <th>Name of Work</th>
                <th>Provision (Lacs)</th>
                <?php if ($isSuperuser): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($budgets)): ?>
                <tr>
                    <td colspan="<?= $isSuperuser ? 7 : 6 ?>" class="no-data">No budget entries found.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($budgets as $budget): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($budget['officename']) ?></td>
                        <td><?= htmlspecialchars($budget['fy']) ?></td>
                        <td><?= htmlspecialchars($budget['code']) ?></td>
                        <td><?= htmlspecialchars($budget['name_of_work']) ?></td>
                        <td><?= number_format((float)$budget['provision'], 2) ?></td>
                        <?php if ($isSuperuser): ?>
                            <td class="actions">
                                <a href="?action=showEditForm&id=<?= $budget['id'] ?>" class="btn btn-edit">Edit</a>
                                <form action="?action=delete&id=<?= $budget['id'] ?>" method="POST" onsubmit="return confirm('Are you sure?');">
                                    <?= \App\Core\CSRFManager::getTokenInput() ?>
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
