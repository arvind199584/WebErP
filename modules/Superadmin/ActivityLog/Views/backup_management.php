<?php
/**
 * View: Database Backup, Restore & Neon Refresh Special Management Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Backup & Cloud Maintenance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 25px; }
        h1 { margin: 0; font-size: 24px; color: #2c3e50; }
        
        .card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; }
        .card h2 { margin-top: 0; font-size: 18px; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .card p { color: #64748b; font-size: 13px; line-height: 1.5; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #dee2e6; padding: 10px 12px; text-align: left; }
        th { background-color: #f1f5f9; color: #334155; font-weight: 600; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-backup { background-color: #2563eb; color: white; }
        .btn-backup:hover { background-color: #1d4ed8; }
        .btn-refresh { background-color: #0d9488; color: white; }
        .btn-refresh:hover { background-color: #0f766e; }
        .btn-restore { background-color: #dc2626; color: white; padding: 5px 12px; font-size: 12px; }
        .btn-restore:hover { background-color: #b91c1c; }
        
        .nav-links { display: flex; gap: 10px; }
        .nav-btn { text-decoration: none; padding: 8px 16px; background: #e2e8f0; color: #475569; border-radius: 6px; font-weight: 600; font-size: 13px; }
        .nav-btn.active { background: #2563eb; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>💾 Database Backup & Cloud Refresh Studio</h1>
            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Superuser utilities for Local PostgreSQL backups, restorations, and Neon Cloud DB refreshes.</p>
        </div>
        <div class="nav-links">
            <a href="?action=index" class="nav-btn">System Audit Logs</a>
            <a href="?action=cloudDiff" class="nav-btn">Cloud Data Diffs</a>
            <a href="?action=backupManagement" class="nav-btn active">Backup & Cloud Refresh</a>
        </div>
    </div>

    <div class="card-grid">
        <!-- Local DB Backup Panel -->
        <div class="card">
            <h2>💾 Local Database Backup</h2>
            <p>Generate a complete SQL snapshot dump of the local PostgreSQL database (`modpyphp`). The dump is safely stored in `storage/backups/`.</p>
            <form action="?action=backupLocalDb" method="POST">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <button type="submit" class="btn btn-backup">📦 Create New Local Backup Dump</button>
            </form>
        </div>

        <!-- Neon Cloud DB Refresh Panel -->
        <div class="card">
            <h2>☁️ Neon Cloud Data Refresh</h2>
            <p>Wipe Neon Cloud database objects and re-upload/sync 100% of local PostgreSQL state onto Neon Cloud DB for mobile application users.</p>
            <form action="?action=refreshNeonDb" method="POST" onsubmit="return confirm('⚠️ Are you sure you want to refresh Neon Cloud DB with exact local data? This will overwrite existing cloud data.');">
                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <button type="submit" class="btn btn-refresh">⚡ Refresh Neon DB from Local</button>
            </form>
        </div>
    </div>

    <!-- Available Local Backups Table -->
    <h3 style="color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">📂 Available Local Database Backups</h3>
    <?php if (empty($backups)): ?>
        <p style="color: #94a3b8; font-style: italic;">No backup files found in `storage/backups/`.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Backup File Name</th>
                    <th>Date Created</th>
                    <th>File Size</th>
                    <th style="width: 150px;">Restore Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $b): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($b['filename']) ?></strong></td>
                        <td><?= htmlspecialchars($b['created_at']) ?></td>
                        <td><?= number_format($b['size'] / 1024, 2) ?> KB</td>
                        <td>
                            <form action="?action=restoreLocalDb" method="POST" onsubmit="return confirm('⚠️ WARNING: Restoring will overwrite current local database state with this backup dump. Continue?');">
                                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                                <input type="hidden" name="filename" value="<?= htmlspecialchars($b['filename']) ?>">
                                <button type="submit" class="btn btn-restore">🔄 Restore Dump</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
