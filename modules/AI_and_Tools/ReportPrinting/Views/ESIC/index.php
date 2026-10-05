<?php
/** @var array $reportData */
/** @var array $agreements */
/** @var array $filters */
?>
<!DOCTYPE html>
<html>
<head>
    <title>ESIC Verification</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .filter-section { margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; }
        .form-group { display: inline-block; margin-right: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
        .right-align { text-align: right; }
        .btn { padding: 6px 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="container">
    <h1 class="no-print">ESIC Verification</h1>

    <form class="filter-section no-print" method="GET">
        <div class="form-group">
            <label>Agreement:</label>
            <select name="agreement_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($agreements as $ag): ?>
                    <option value="<?= $ag['id'] ?>" <?= ($filters['agreement_id'] ?? '') == $ag['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ag['agreement_no']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>From:</label><input type="date" name="from_date" value="<?= htmlspecialchars($filters['from_date'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>To:</label><input type="date" name="to_date" value="<?= htmlspecialchars($filters['to_date'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn">Generate</button>
        <button type="button" class="btn" style="background-color: #6c757d;" onclick="window.print()">Print</button>
    </form>

    <?php if (!empty($reportData)): ?>
        <h2>ESIC Summary (<?= htmlspecialchars($filters['from_date']) ?> to <?= htmlspecialchars($filters['to_date']) ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Employee Name</th>
                    <th class="right-align">Due (₹)</th>
                    <th class="right-align">Drawn (₹)</th>
                    <th class="right-align">Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno=1; foreach ($reportData as $row): ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td class="right-align"><?= number_format((float)$row['due'], 2) ?></td>
                    <td class="right-align"><?= number_format((float)$row['drawn'], 2) ?></td>
                    <td class="right-align" style="font-weight: bold; color: <?= $row['balance'] < 0 ? 'red' : 'green' ?>;">
                        <?= number_format((float)$row['balance'], 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
