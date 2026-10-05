<?php
/**
 * @var array $ledger
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Full Wage Ledger</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 900px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .month-section { margin-bottom: 30px; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .total-row { font-weight: bold; }
        .net-pay { color: #28a745; }
    </style>
</head>
<body>
<div class="container">
    <h1>Full Wage Ledger</h1>
    <a href="?action=list">Back to Wage Processing</a>

    <?php foreach ($ledger as $month => $entries): ?>
        <div class="month-section">
            <h2><?= date('F Y', strtotime($month . '-01')) ?></h2>
            <table>
                <thead><tr><th>Date</th><th>Remark</th><th>Credit</th><th>Debit</th></tr></thead>
                <tbody>
                    <?php
                    $totalCredit = 0; $totalDebit = 0;
                    foreach ($entries as $entry):
                        $totalCredit += (float)$entry['cr_amount'];
                        $totalDebit += (float)$entry['dr_amount'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['transaction_date']) ?></td>
                        <td><?= htmlspecialchars($entry['remark']) ?></td>
                        <td style="text-align:right;"><?= number_format((float)$entry['cr_amount'], 2) ?></td>
                        <td style="text-align:right;"><?= number_format((float)$entry['dr_amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Net Pay for Month</td>
                        <td colspan="2" style="text-align:right;" class="net-pay"><?= number_format($totalCredit - $totalDebit, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
