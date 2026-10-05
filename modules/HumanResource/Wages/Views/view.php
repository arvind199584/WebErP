<?php
/**
 * @var array $ledger
 * @var string $month
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wage Ledger</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        thead th { background-color: #e9ecef; }
        .amount { text-align: right; font-family: monospace; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .net-pay { font-size: 1.2em; color: #28a745; }
        .back-link { display: inline-block; margin-top: 20px; text-decoration: none; padding: 10px 15px; background-color: #6c757d; color: white; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Wage Ledger for <?= htmlspecialchars(date('F Y', strtotime($month))) ?></h1>
    <table>
        <thead><tr><th>Date</th><th>Remark</th><th style="text-align:right;">Credit</th><th style="text-align:right;">Debit</th></tr></thead>
        <tbody>
            <?php
            $totalCredit = 0; $totalDebit = 0;
            foreach ($ledger as $entry):
                $totalCredit += (float)$entry['cr_amount'];
                $totalDebit += (float)$entry['dr_amount'];
            ?>
            <tr>
                <td><?= htmlspecialchars($entry['transaction_date']) ?></td>
                <td><?= htmlspecialchars($entry['remark']) ?></td>
                <td class="amount"><?= number_format((float)$entry['cr_amount'], 2) ?></td>
                <td class="amount"><?= number_format((float)$entry['dr_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">Total</td>
                <td class="amount"><?= number_format($totalCredit, 2) ?></td>
                <td class="amount"><?= number_format($totalDebit, 2) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="2">Net Pay</td>
                <td colspan="2" class="amount net-pay"><?= number_format($totalCredit - $totalDebit, 2) ?></td>
            </tr>
        </tfoot>
    </table>
    <a href="javascript:history.back()" class="back-link">Back</a>
</div>
</body>
</html>
