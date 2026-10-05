<?php
/**
 * @var array $reportData
 * @var object $agreement
 * @var string $agencyName
 */
function formatIndianCurrency($num) {
    $num = (float)$num;
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
    return "₹ " . $thecash . "." . substr($decimalpart, 0, 2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expenditure Statement</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; font-size: 12px; padding: 40px; color: #000; background: #fff; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; text-decoration: underline; font-size: 20px; }
        .meta-box { margin-bottom: 20px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .bold { font-weight: bold; }

        .no-print-zone { text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-print { padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }

        @media print {
            .no-print-zone { display: none; }
            body { padding: 0; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="no-print-zone">
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn-print" style="background:#6c757d;" onclick="window.close()">Close Window</button>
    </div>

    <div class="header">
        <h1>EXPENDITURE STATEMENT</h1>
        <p>DELHI DEVELOPMENT AUTHORITY</p>
    </div>

    <div class="meta-box">
        <p><strong>Agreement No:</strong> <?= htmlspecialchars($agreement->agreement_no) ?></p>
        <p><strong>Agency:</strong> <?= htmlspecialchars($agencyName) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">S.No.</th>
                <th>Bill Reference (Office Bill No.)</th>
                <th>Bill Type</th>
                <th>Bill Date</th>
                <th class="right">Gross Amount</th>
                <th class="right">Net Amount</th>
                <th class="right">Cumulative Total (Gross)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportData)): ?>
                <tr><td colspan="7" style="text-align:center;">No processed/paid bills found for this agreement.</td></tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($reportData as $bill): ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td><?= htmlspecialchars($bill['office_bill_no'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($bill['bill_type']) ?></td>
                    <td><?= date('d.m.Y', strtotime($bill['bill_date'])) ?></td>
                    <td class="right"><?= formatIndianCurrency($bill['gross_amount']) ?></td>
                    <td class="right"><?= formatIndianCurrency($bill['net_amount']) ?></td>
                    <td class="right bold"><?= formatIndianCurrency($bill['cumulative_gross']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
                // Optional: Close window after print dialog is closed
                // window.onafterprint = function() { window.close(); };
            }, 500);
        };
    </script>
</body>
</html>
