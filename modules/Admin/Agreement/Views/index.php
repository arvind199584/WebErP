<?php
/**
 * @var array $agreements An array of agreement data.
 */
$isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agreements</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; color: white; display: inline-block; font-size: 13px; margin-right: 5px; }
        .btn-create { background-color: #28a745; padding: 10px 15px; font-size: 14px; }
        .btn-edit { background-color: #ffc107; color: #212529; }
        .btn-scope { background-color: #17a2b8; }
        .btn-view { background-color: #6c757d; }
        table.table2 { width: 100%; border-collapse: collapse; }
        .table2 th, .table2 td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table2 thead { background-color: #f2f2f2; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Agreement Management</h1>
        <a href="?action=showCreateForm" class="btn btn-create">Create New Agreement</a>
    </div>

    <table class="table2">
        <thead>
            <tr>
                <th>S. No.</th>
                <th>Agreement No</th>
                <th>Agency</th>
                <th>AA & ES Alias</th>
                <th>Period</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($agreements)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">No agreements found.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($agreements as $agreement): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($agreement['agreement_no']) ?></td>
                        <td><?= htmlspecialchars($agreement['agency_name']) ?></td>
                        <td><?= htmlspecialchars($agreement['aa_es_alias']) ?></td>
                        <td><?= htmlspecialchars($agreement['period_from']) ?> to <?= htmlspecialchars($agreement['period_to']) ?></td>
                        <td><?= htmlspecialchars($agreement['status']) ?></td>
                        <td>
                            <a href="?action=view&id=<?= $agreement['id'] ?>" class="btn btn-view">View</a>
                            <a href="?action=showEditScopeForm&id=<?= $agreement['id'] ?>" class="btn btn-scope">Edit Scope</a>
                            <?php if ($isSuperuser): ?>
                                <a href="?action=showEditForm&id=<?= $agreement['id'] ?>" class="btn btn-edit">Edit Agreement</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
