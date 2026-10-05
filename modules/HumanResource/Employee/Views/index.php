<?php
/**
 * @var array $employees
 * @var array $agreements
 * @var int|null $selectedAgreementId
 * @var string|null $message
 * @var string|null $error
 */
$currentUser = \App\Modules\Superadmin\Users\Services\UserService::getCurrentUser();
$canEdit = in_array($currentUser['role'], ['superuser', 'manager']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; font-size: 14px; border: none; cursor: pointer; }
        .btn-primary { background-color: #007bff; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #ffc107; color: black; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }

        .filter-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }

        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Employee Management</h1>
        <?php if ($canEdit): ?>
            <a href="?action=showCreateForm" class="btn btn-primary">+ New Employee</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="filter-section">
        <form method="GET">
            <input type="hidden" name="action" value="list">
            <label>Select Agreement:</label>
            <select name="agreement_id" onchange="this.form.submit()">
                <option value="">-- Select --</option>
                <?php foreach ($agreements as $ag): ?>
                    <option value="<?= $ag['id'] ?>" <?= $selectedAgreementId == $ag['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ag['agreement_no']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedAgreementId && $canEdit): ?>
            <a href="?action=deleteAll&agreement_id=<?= $selectedAgreementId ?>"
               class="btn btn-danger"
               onclick="return confirm('WARNING: This will delete ALL employees for this agreement. Are you sure?')">Delete All Employees</a>
        <?php endif; ?>
    </div>

    <?php if ($selectedAgreementId): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Joining Date</th>
                    <th>Reliever?</th>
                    <th>Rest Day</th>
                    <th>Deployed Office</th>
                    <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                    <td><?= htmlspecialchars($emp['designation']) ?></td>
                    <td><?= date('d.m.Y', strtotime($emp['joining_date'])) ?></td>
                    <td><?= $emp['is_reliever'] ? 'Yes' : 'No' ?></td>
                    <td><?= htmlspecialchars($emp['default_rest_day'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($emp['deployed_office_name'] ?? 'Same Office') ?></td>
                    <?php if ($canEdit): ?>
                    <td>
                        <a href="?action=showEditForm&id=<?= $emp['id'] ?>"
                           class="btn btn-warning btn-sm">Edit</a>
                        <a href="?action=delete&id=<?= $emp['id'] ?>&agreement_id=<?= $selectedAgreementId ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this employee?')">Delete</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Please select an agreement to view employees.</p>
    <?php endif; ?>
</div>
</body>
</html>
