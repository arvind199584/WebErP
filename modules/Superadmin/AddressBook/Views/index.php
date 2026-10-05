<!DOCTYPE html>
<html>
<head>
    <title>Address Book</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; border: none; cursor: pointer; }
        .btn-primary { background-color: #007bff; }
        .btn-danger { background-color: #dc3545; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: #eee; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Address Book</h1>
        <a href="?action=create" class="btn btn-primary">+ Add New Contact</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name / Designation</th>
                <th>Department</th>
                <th>Category</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($addresses as $addr): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($addr['name']) ?></strong><br>
                    <small><?= htmlspecialchars($addr['designation']) ?></small>
                </td>
                <td><?= htmlspecialchars($addr['department']) ?></td>
                <td><span class="badge"><?= htmlspecialchars($addr['category']) ?></span></td>
                <td><?= nl2br(htmlspecialchars($addr['address'])) ?></td>
                <td>
                    <a href="?action=delete&id=<?= $addr['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this contact?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
