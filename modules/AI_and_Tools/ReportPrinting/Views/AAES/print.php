<?php
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AA & ES Calculation - <?= htmlspecialchars($sub_head) ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; line-height: 1.4; color: #000; padding: 20px; background-color: white; }
        .document { max-width: 1000px; margin: 0 auto; }
        .meta-info { margin-bottom: 10px; font-size: 13px; }
        .meta-info p { margin: 2px 0; }
        .intro-line { margin-bottom: 10px; font-weight: bold; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .left-align { text-align: left; }
        .right-align { text-align: right; }
        .bold { font-weight: bold; }
        .notes-header { margin-top: 30px; font-weight: bold; font-size: 14px; text-decoration: underline; }
        .notes { margin-top: 10px; font-size: 13px; line-height: 1.6; }
        .notes p { margin: 5px 0; }
        @media print { body { padding: 0; } .no-print { display: none; } @page { size: portrait; margin: 1.5cm; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Calculation Sheet</button>
    </div>

    <div class="document">
        <div class="meta-info">
            <p><strong>Name of Work:</strong> <?= htmlspecialchars($name_of_work) ?></p>
            <p><strong>Sub-Head:</strong> <?= htmlspecialchars($sub_head) ?></p>
            <p><strong>Budget Code:</strong> <?= htmlspecialchars($budget_code) ?></p>
        </div>

        <div class="intro-line">
            The calculation of A/A & E/S is as under:
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">S.No</th>
                    <th class="left-align">Description</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 15%;">Rate</th>
                    <th class="right-align" style="width: 15%;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno=1; foreach ($calculations['items'] as $item): ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td class="left-align"><?= $item['description'] ?></td>
                    <td><?= number_format($item['qty'], 2) ?></td>
                    <td><?= formatIndianCurrency($item['rate']) ?></td>
                    <td class="right-align"><?= formatIndianCurrency($item['amount']) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php foreach ($calculations['summary'] as $label => $val):
                    $isBold = in_array($label, ['Total Estimated Cost', 'Total', 'Grand Total (AA & ES)']);
                ?>
                <tr class="<?= $isBold ? 'bold' : '' ?>">
                    <td colspan="4" class="right-align"><?= $label ?></td>
                    <td class="right-align"><?= formatIndianCurrency($val) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="notes-header">Notes:-</div>
        <div class="notes">
            <?php if (empty($wage_orders)): ?>
                <p>1. The Rates for the items are adopted from relevant Government Orders.</p>
            <?php else: ?>
                <?php foreach ($wage_orders as $index => $order): ?>
                    <p><?= ($index === 0) ? '1. ' : '' ?>The Rates for the items are adopted from the <b><?= htmlspecialchars($order['authority']) ?></b> Order No. <b><?= htmlspecialchars($order['letter_no']) ?></b> dated <b><?= date('d.m.Y', strtotime($order['letter_date'])) ?></b>.</p>
                <?php endforeach; ?>
            <?php endif; ?>
            <p>2. 8.33% Bonus is applied for upto a ceiling of Rs. 21000/-.</p>
            <p>3. 13% EPF is applied for upto a ceiling of Rs. 15000/-.</p>
            <p>4. 3.25% ESIC is applied for upto a ceiling of Rs. 21000/-.</p>
        </div>
    </div>
</body>
</html>
