<!DOCTYPE html>
<html>
<head>
    <title>Members</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Membership Management</h1>
        <a href="?action=showCreateForm" class="btn">+ New Member</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Membership No</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Joining Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['membership_no']) ?></td>
                <td><?= htmlspecialchars($m['full_name']) ?></td>
                <td><?= htmlspecialchars($m['phone']) ?></td>
                <td><?= date('d.m.Y', strtotime($m['joining_date'])) ?></td>
                <td><?= $m['leaving_date'] ? 'Inactive' : 'Active' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
