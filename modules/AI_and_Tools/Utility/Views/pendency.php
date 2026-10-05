<?php
/** @var array $pendencies */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pendency Report</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 20px; text-align: center; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { border: 1px solid #dee2e6; padding: 15px; text-align: left; vertical-align: top; }
        th { background-color: #f8f9fa; font-weight: 600; color: #333; text-transform: uppercase; font-size: 12px; }

        .severity-high { border-left: 5px solid #e74c3c; }
        .severity-medium { border-left: 5px solid #f39c12; }

        .responsible-text { font-weight: bold; }
        .responsible-Manager { color: #0056b3; }
        .responsible-Agency { color: #d35400; }

        .description-list { margin: 0; padding-left: 20px; list-style-type: disc; }
        .description-list li { margin-bottom: 5px; color: #444; }

        .empty-state { text-align: center; padding: 50px; color: #27ae60; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-left: 10px; vertical-align: middle; }
        .badge-high { background: #f8d7da; color: #721c24; }
        .badge-medium { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
<div class="container">
    <h1>Pendency & Timeline Alerts</h1>
    <p style="text-align: center; color: #666;">Hierarchical tracking of manpower agreement workflows.</p>

    <?php if (empty($pendencies)): ?>
        <div class="empty-state">
            <span style="font-size: 50px; display: block; margin-bottom: 10px;">✅</span>
            <h3>Excellent!</h3>
            <p>All actions are up to date for your office.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">S. No.</th>
                    <th style="width: 180px;">Category</th>
                    <th>Description (Agreement Details)</th>
                    <th style="width: 150px;">Responsibility</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($pendencies as $p):
                    // Split the message into parts for bullet points
                    // Format was: "Agmt: X, Agency: Y, Alias: Z"
                    $parts = explode(', ', $p['msg']);
                ?>
                <tr class="severity-<?= $p['severity'] ?>">
                    <td style="text-align: center; font-weight: bold;"><?= $sno++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['category']) ?></strong>
                        <span class="badge badge-<?= $p['severity'] ?>"><?= strtoupper($p['severity']) ?></span>
                    </td>
                    <td>
                        <ul class="description-list">
                            <?php foreach ($parts as $part): ?>
                                <li><?= htmlspecialchars($part) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <span class="responsible-text responsible-<?= $p['responsible'] ?>">
                            <?= htmlspecialchars($p['responsible']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
