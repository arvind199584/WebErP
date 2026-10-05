<?php
/**
 * View: Neon Cloud Data Diffs & Reconciliation Panel for Superusers
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mobile Cloud Data Reconciliation</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 24px; color: #2c3e50; }
        .badge-insert { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-update { background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; font-size: 13px; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; vertical-align: top; }
        th { background-color: #f8f9fa; color: #495057; font-weight: 600; }
        
        pre { background: #f8f9fa; padding: 8px; border-radius: 6px; max-height: 200px; overflow: auto; margin: 0; font-size: 12px; border: 1px solid #e2e8f0; }
        
        .btn { padding: 6px 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .btn-commit { background-color: #28a745; color: white; margin-right: 6px; }
        .btn-commit:hover { background-color: #218838; }
        .btn-cancel { background-color: #dc3545; color: white; }
        .btn-cancel:hover { background-color: #c82333; }
        
        .no-data { text-align: center; padding: 30px; color: #28a745; font-weight: 600; font-size: 16px; background-color: #f0fff4; border-radius: 8px; border: 1px solid #c6f6d5; }
        .nav-links { display: flex; gap: 10px; }
        .nav-btn { text-decoration: none; padding: 8px 16px; background: #e2e8f0; color: #475569; border-radius: 6px; font-weight: 600; font-size: 13px; }
        .nav-btn.active { background: #2563eb; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>⚡ Mobile Cloud Data Reconciliation</h1>
            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Review mobile user inputs on Neon Cloud DB and commit to Local PostgreSQL or reject.</p>
        </div>
        <div class="nav-links">
            <a href="?action=index" class="nav-btn">System Audit Logs</a>
            <a href="?action=cloudDiff" class="nav-btn active">Cloud Data Diffs</a>
        </div>
    </div>

    <?php if (empty($diffs)): ?>
        <div class="no-data">
            ✅ All Neon Cloud data is 100% in sync with Local PostgreSQL! No pending uncommitted mobile inputs.
        </div>
    <?php else: ?>
        <?php foreach ($diffs as $tableName => $records): ?>
            <h3 style="margin-top: 25px; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">
                📋 Table: <span style="color: #2563eb;"><?= htmlspecialchars($tableName) ?></span> 
                <span style="font-size: 13px; color: #64748b; font-weight: normal;">(<?= count($records) ?> uncommitted records)</span>
            </h3>

            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Type</th>
                        <th style="width: 70px;">Record ID</th>
                        <th>Cloud Data (Neon)</th>
                        <th>Local Data (PostgreSQL)</th>
                        <th style="width: 200px;">Superuser Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['type'] === 'NEW_INSERT'): ?>
                                    <span class="badge-insert">⚡ NEW ENTRY</span>
                                <?php else: ?>
                                    <span class="badge-update">📝 MODIFIED</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>#<?= $item['id'] ?></strong></td>
                            <td>
                                <pre><?= htmlspecialchars(json_encode($item['cloud_data'], JSON_PRETTY_PRINT)) ?></pre>
                            </td>
                            <td>
                                <?php if ($item['local_data']): ?>
                                    <pre><?= htmlspecialchars(json_encode($item['local_data'], JSON_PRETTY_PRINT)) ?></pre>
                                <?php else: ?>
                                    <em style="color: #94a3b8;">None (Not present in local DB)</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex;">
                                    <form action="?action=commitCloudChange" method="POST" onsubmit="return confirm('Commit this record to Local PostgreSQL?');">
                                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($tableName) ?>">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-commit">✔ Commit</button>
                                    </form>

                                    <form action="?action=cancelCloudChange" method="POST" onsubmit="return confirm('Reject and cancel this change on Cloud?');">
                                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($tableName) ?>">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-cancel">✖ Cancel</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
