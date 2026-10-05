<?php
$data = $_SESSION['payment_create_data'] ?? [];
$calcs = $calculations ?? ['gross_amount' => 0, 'total_deduction' => 0, 'net_amount' => 0, 'deductions' => []];
$prev = $previous ?? ['prev_gross' => 0, 'prev_deductions' => 0, 'prev_net' => 0];

/**
 * Formats a number into Indian Currency format with Rupee symbol.
 */
function formatIndianCurrency($num) {
    $num = (float)$num;
    $isNegative = $num < 0;
    $num = abs($num);
    $explrestunits = "";
    $decimal = strpos($num, ".");
    if ($decimal !== false) {
        $decimalpart = substr($num, $decimal + 1);
        $num = substr($num, 0, $decimal);
    } else { $decimalpart = "00"; }
    if (strlen($num) > 3) {
        $lastthree = substr($num, strlen($num) - 3);
        $restunits = substr($num, 0, strlen($num) - 3);
        $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
        $expunit = str_split($restunits, 2);
        for ($i = 0; $i < sizeof($expunit); $i++) {
            if ($i == 0) { $explrestunits .= (int)$expunit[$i] . ","; }
            else { $explrestunits .= $expunit[$i] . ","; }
        }
        $thecash = $explrestunits . $lastthree;
    } else { $thecash = $num; }
    $result = "₹ " . $thecash . "." . substr($decimalpart, 0, 2);
    return $isNegative ? "-" . $result : $result;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Bill - Step 3: Review</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .review-section { margin-bottom: 30px; }
        .review-section h3 { border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff; }
        .btn { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-back { background-color: #6c757d; text-decoration: none; }
        .amount-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f8f9fa; text-align: left; }
        .total-row { font-weight: bold; background-color: #e9ecef; }
        .penalty { color: #dc3545; }
        .highlight { color: #007bff; font-weight: bold; }
        .cumulative-box { background: #f1f8ff; border: 1px solid #cce5ff; padding: 15px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Step 3: Review and Confirm</h1>

    <div class="review-section">
        <h3>1. Current Bill Calculation</h3>
        <table class="amount-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty (P/26)</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['bill_items'] as $item): ?>
                <tr>
                    <td style="text-align:left;"><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= number_format((float)($item['qty'] ?? 0), 2) ?></td>
                    <td><?= formatIndianCurrency($item['rate'] ?? 0) ?></td>
                    <td><?= formatIndianCurrency(($item['qty'] ?? 0) * ($item['rate'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">Total Billed Amount (A)</td>
                    <td><?= formatIndianCurrency($calcs['total_billed'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td colspan="3">Less: Absentee Penalty (B)</td>
                    <td class="penalty">- <?= formatIndianCurrency($calcs['absentee_penalty'] ?? 0) ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3">Amount After Penalty (C = A - B)</td>
                    <td><?= formatIndianCurrency($calcs['after_penalty'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td colspan="3">Add: Service Charge @ <?= $calcs['service_charge_percent'] ?? 0 ?>% (D)</td>
                    <td class="highlight">+ <?= formatIndianCurrency($calcs['service_charge'] ?? 0) ?></td>
                </tr>
                <tr class="total-row" style="background-color: #d1ecf1;">
                    <td colspan="3">Gross Amount (E = C + D)</td>
                    <td style="font-size: 1.1em;"><?= formatIndianCurrency($calcs['gross_amount'] ?? 0) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="review-section">
        <h3>2. Deductions & Net Payable</h3>
        <table class="amount-table" style="width: 60%;">
            <?php foreach (($calcs['deductions'] ?? []) as $name => $amount): ?>
            <tr>
                <th style="font-weight: normal;"><?= htmlspecialchars($name) ?></th>
                <td class="penalty">- <?= formatIndianCurrency($amount) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row" style="background-color: #d4edda;">
                <th>Net Amount Payable for this Bill</th>
                <td style="font-size: 1.2em;"><?= formatIndianCurrency($calcs['net_amount'] ?? 0) ?></td>
            </tr>
        </table>
    </div>

    <div class="cumulative-box">
        <h3>3. Cumulative Progress (Agreement-wise)</h3>
        <table class="amount-table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Previous (Paid)</th>
                    <th>Current Bill</th>
                    <th>Total to Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th style="font-weight: normal;">Gross Expenditure</th>
                    <td><?= formatIndianCurrency($prev['prev_gross']) ?></td>
                    <td><?= formatIndianCurrency($calcs['gross_amount']) ?></td>
                    <td class="highlight"><?= formatIndianCurrency($prev['prev_gross'] + $calcs['gross_amount']) ?></td>
                </tr>
                <tr>
                    <th style="font-weight: normal;">Total Deductions</th>
                    <td><?= formatIndianCurrency($prev['prev_deductions']) ?></td>
                    <td><?= formatIndianCurrency($calcs['total_deduction']) ?></td>
                    <td><?= formatIndianCurrency($prev['prev_deductions'] + $calcs['total_deduction']) ?></td>
                </tr>
                <tr class="total-row">
                    <th>Net Payment</th>
                    <td><?= formatIndianCurrency($prev['prev_net']) ?></td>
                    <td><?= formatIndianCurrency($calcs['net_amount']) ?></td>
                    <td style="color: #28a745;"><?= formatIndianCurrency($prev['prev_net'] + $calcs['net_amount']) ?></td>
                </tr>
            </tbody>
        </table>
        <p style="font-size: 11px; color: #666; margin-top: 10px;">* Previous totals include all bills marked as 'Paid' before <?= date('d.m.Y', strtotime($data['bill_date'])) ?>.</p>
    </div>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="3">
        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
            <a href="?action=showCreateForm&step=2" class="btn btn-back">&larr; Back</a>
            <button type="submit" class="btn">Confirm & Create Bill</button>
        </div>
    </form>
</div>
</body>
</html>
