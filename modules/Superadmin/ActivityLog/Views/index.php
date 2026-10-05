<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #e9ecef; }
        .badge { padding: 3px 6px; border-radius: 4px; color: white; font-size: 11px; }
        .badge-INSERT { background-color: #28a745; }
        .badge-UPDATE { background-color: #ffc107; color: black; }
        .badge-DELETE { background-color: #dc3545; }
        pre { background: #f8f9fa; padding: 5px; border-radius: 4px; max-height: 100px; overflow: auto; margin: 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>System Activity Logs</h1>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Table</th>
                <th>ID</th>
                <th>Changes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= date('d.m.y H:i:s', strtotime($log['changed_at'])) ?></td>
                <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                <td><span class="badge badge-<?= $log['action'] ?>"><?= $log['action'] ?></span></td>
                <td><?= htmlspecialchars($log['table_name']) ?></td>
                <td><?= $log['record_id'] ?></td>
                <td>
                    <?php if ($log['action'] === 'UPDATE'): ?>
                        <pre><?= htmlspecialchars(json_encode(json_decode($log['new_data']), JSON_PRETTY_PRINT)) ?></pre>
                    <?php elseif ($log['action'] === 'INSERT'): ?>
                        <pre><?= htmlspecialchars(json_encode(json_decode($log['new_data']), JSON_PRETTY_PRINT)) ?></pre>
                    <?php else: ?>
                        <pre><?= htmlspecialchars(json_encode(json_decode($log['old_data']), JSON_PRETTY_PRINT)) ?></pre>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
