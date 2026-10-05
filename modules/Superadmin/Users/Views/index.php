<?php
/**
 * @var array $users An array of user data from the model.
 * @var string|null $message A flash message from the session.
 * @var string|null $error A flash error message from the session.
 * @var string|null $searchTerm The current search term.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 5px; color: white; display: inline-block; }
        .btn-create { background-color: #28a745; }
        .btn-edit { background-color: #007bff; }
        .btn-delete { background-color: #dc3545; border: none; font-size: 14px; cursor: pointer; }
        .search-bar { display: flex; margin-bottom: 20px; }
        .search-bar input { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px 0 0 5px; }
        .search-bar button { padding: 10px; border: 1px solid #ccc; border-left: none; background: #f8f9fa; border-radius: 0 5px 5px 0; cursor: pointer; }
        table.table2 { width: 100%; border-collapse: collapse; }
        .table2 th, .table2 td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table2 thead { background-color: #f2f2f2; }
        .table2 tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .table2 tbody tr:hover { background-color: #f1f1f1; }
        .actions form { display: inline; }
        .actions a, .actions .btn-delete { margin-right: 8px; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; padding: 20px; }
        .logout-link { float: right; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>User Management</h1>
        <a href="?action=showCreateForm" class="btn btn-create">Create New User</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="GET" action="?" class="search-bar">
        <input type="hidden" name="action" value="list">
        <input type="text" name="search" placeholder="Search by name, username, or email..." value="<?= htmlspecialchars($searchTerm ?? '') ?>">
        <button type="submit">Search</button>
    </form>

    <table class="table2">
        <thead>
            <tr>
                <th style="width: 60px;">S. No.</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Office</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="no-data">No users found.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($users as $user): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></td>
                        <td><?= htmlspecialchars($user['usrname']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['officename']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td class="actions">
                            <a href="?action=showEditForm&id=<?= $user['id'] ?>" class="btn btn-edit">Edit</a>
                            <form action="?action=delete&id=<?= $user['id'] ?>" method="POST" onsubmit="return confirm('Are you sure?');">
                                <?= \App\Core\CSRFManager::getTokenInput() ?>
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
