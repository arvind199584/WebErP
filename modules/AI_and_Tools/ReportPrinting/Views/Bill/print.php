<?php
/**
 * @var array $report Contains 'data', 'calcs', and 'netWords'
 */
$bill = $report['data'];
$calcs = $report['calcs'];
$netWords = $report['netWords'];

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill Print - <?= htmlspecialchars($bill['office_bill_no']) ?></title>
    <style>
        body { font-family: 'Segoe UI', serif; font-size: 12px; color: #000; margin: 0; padding: 20px; background: #fff; }
        .no-print-zone { text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-print { padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: 10px; }

        .page { padding: 40px; border: 1px solid #eee; margin-bottom: 50px; min-height: 1000px; position: relative; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 18px; text-decoration: underline; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig-box { text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px; }

        @media print {
            .no-print-zone { display: none; }
            body { padding: 0; }
            .page { border: none; box-shadow: none; margin-bottom: 0; page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="no-print-zone">
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn-print" style="background:#6c757d;" onclick="window.close()">Close Window</button>
    </div>

    <!-- PAGE 1: ABSTRACT OF COST -->
    <div class="page">
        <div class="header">
            <h1>ABSTRACT OF COST</h1>
            <p>DELHI DEVELOPMENT AUTHORITY</p>
        </div>
        <p><strong>Bill No:</strong> <?= htmlspecialchars($bill['office_bill_no']) ?> | <strong>Date:</strong> <?= date('d.m.Y', strtotime($bill['bill_date'])) ?></p>
        <p><strong>Agency:</strong> <?= htmlspecialchars($bill['agency_name']) ?></p>
        <p><strong>Work:</strong> <?= htmlspecialchars($bill['name_of_work']) ?></p>
        <p><strong>Period:</strong> <?= date('d.m.Y', strtotime($bill['period_from'])) ?> to <?= date('d.m.Y', strtotime($bill['period_to'])) ?></p>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Rate</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bill['bill_items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td class="right"><?= number_format($item['qty'], 2) ?></td>
                    <td class="right"><?= number_format($item['rate'], 2) ?></td>
                    <td class="right"><?= number_format($item['qty'] * $item['rate'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="bold">
                    <td colspan="3" class="right">Total Basic Amount</td>
                    <td class="right"><?= formatIndianCurrency($calcs['total_basic']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="right">Less: Shortage/Absentee Deduction</td>
                    <td class="right" style="color:red;">- <?= formatIndianCurrency($calcs['shortage_amount']) ?></td>
                </tr>
                <tr class="bold">
                    <td colspan="3" class="right">Net Basic (A)</td>
                    <td class="right"><?= formatIndianCurrency($calcs['total_a']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="right">Add: Service Charge @ <?= $bill['service_charge_percent'] ?>% (B)</td>
                    <td class="right"><?= formatIndianCurrency($calcs['profit_amount']) ?></td>
                </tr>
                <tr class="bold" style="background:#eee;">
                    <td colspan="3" class="right">GROSS AMOUNT (A + B)</td>
                    <td class="right"><?= formatIndianCurrency($calcs['gross_amount']) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <div class="sig-box">Dealing Assistant</div>
            <div class="sig-box">Section Officer</div>
            <div class="sig-box">Manager/Secretary</div>
        </div>
    </div>

    <!-- PAGE 2: MEMORANDUM OF PAYMENTS -->
    <div class="page">
        <div class="header">
            <h1>MEMORANDUM OF PAYMENTS</h1>
        </div>
        <table>
            <tr>
                <td>1. Total Value of work done</td>
                <td class="right"><?= formatIndianCurrency($calcs['gross_amount']) ?></td>
            </tr>
            <tr>
                <td>2. Deductions/Recoveries:</td>
                <td></td>
            </tr>
            <?php foreach ($calcs['recoveries'] as $name => $amt): ?>
            <tr>
                <td style="padding-left: 30px;">- <?= $name ?></td>
                <td class="right"><?= formatIndianCurrency($amt) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="bold">
                <td>Total Recoveries</td>
                <td class="right"><?= formatIndianCurrency($calcs['total_recoveries']) ?></td>
            </tr>
            <tr class="bold" style="background:#e9ecef; font-size: 1.2em;">
                <td>NET AMOUNT PAYABLE</td>
                <td class="right"><?= formatIndianCurrency($calcs['net_amount']) ?></td>
            </tr>
        </table>
        <p style="margin-top: 20px;"><strong>Rupees in words:</strong> <?= $netWords ?> Only.</p>
    </div>

    <script>
        window.onload = function() { setTimeout(() => { window.print(); }, 500); };
    </script>
</body>
</html>
