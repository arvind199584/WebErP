<?php
/**
 * @var array $agreements
 * @var array $reportData
 * @var int|null $selectedAgreementId
 * @var string $dueFrom
 * @var string $dueTo
 * @var string $drawnOn
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wage Summary Report</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .filter-bar { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <h1>Wage Summary Report</h1>
    <form method="GET" action="" class="filter-bar">
        <input type="hidden" name="action" value="showReport">
        <div><label>Agreement:</label> <select name="agreement_id">
            <option value="">-- Select --</option>
            <?php foreach ($agreements as $agreement): ?>
                <option value="<?= $agreement['id'] ?>" <?= ($selectedAgreementId == $agreement['id']) ? 'selected' : '' ?>><?= htmlspecialchars($agreement['agreement_no']) ?></option>
            <?php endforeach; ?>
        </select></div>
        <div><label>Due From:</label> <input type="date" name="due_from" value="<?= htmlspecialchars($dueFrom) ?>"></div>
        <div><label>Due To:</label> <input type="date" name="due_to" value="<?= htmlspecialchars($dueTo) ?>"></div>
        <div><label>Drawn As On:</label> <input type="date" name="drawn_on" value="<?= htmlspecialchars($drawnOn) ?>"></div>
        <div><button type="submit">Generate Report</button></div>
    </form>

    <?php if ($selectedAgreementId): ?>
        <table>
            <thead><tr><th>S.No.</th><th>Name</th><th>Due</th><th>Drawn</th><th>Balance</th></tr></thead>
            <tbody>
                <?php
                $totalDue = 0; $totalDrawn = 0; $totalBalance = 0;
                $sno = 1;
                foreach ($reportData as $row):
                    $totalDue += $row['due'];
                    $totalDrawn += $row['drawn'];
                    $totalBalance += $row['balance'];
                ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td style="text-align:right;"><?= number_format((float)$row['due'], 2) ?></td>
                    <td style="text-align:right;"><?= number_format((float)$row['drawn'], 2) ?></td>
                    <td style="text-align:right;"><?= number_format((float)$row['balance'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td style="text-align:right;"><?= number_format($totalDue, 2) ?></td>
                    <td style="text-align:right;"><?= number_format($totalDrawn, 2) ?></td>
                    <td style="text-align:right;"><?= number_format($totalBalance, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
