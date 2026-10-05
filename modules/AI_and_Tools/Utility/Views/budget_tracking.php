<?php
/**
 * @var array $report
 * @var array $trend
 * @var array $budgetTrend
 * @var string $fyStart
 * @var string $fyEnd
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

function formatInLakhs($rupees) {
    $lakhs = (float)$rupees / 100000;
    return number_format($lakhs, 2) . " Lacs";
}

// PREDICTION CALCULATIONS
$totalActual = 0; $totalProjected = 0; $totalProvision = 0;
foreach ($trend as $t) { if ($t['type'] === 'actual') $totalActual += $t['amount']; else $totalProjected += $t['amount']; }
foreach ($report as $r) { $totalProvision += $r['provision']; }
$grandTotalPredicted = $totalActual + $totalProjected;
$projectedBalance = $totalProvision - $grandTotalPredicted;

// PREPARE CHART DATA
$chartLabels = [];
if (!empty($budgetTrend)) {
    $firstKey = array_key_first($budgetTrend);
    $chartLabels = array_keys($budgetTrend[$firstKey]['monthly_data']);
}

$datasets = [];
$colors = ['#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', '#1abc9c', '#e67e22', '#34495e', '#d35400', '#27ae60'];
$colorIdx = 0;

foreach ($budgetTrend as $code => $data) {
    $actualPoints = [];
    $projectedPoints = [];

    foreach ($data['monthly_data'] as $month => $mdata) {
        if ($mdata['type'] === 'actual') {
            $actualPoints[] = $mdata['percentage'];
            $projectedPoints[] = null; // Gap for actuals
        } else {
            // Connect the dotted line to the last actual point
            if (count($projectedPoints) > 0 && end($projectedPoints) === null) {
                $projectedPoints[count($projectedPoints)-1] = end($actualPoints);
            }
            $projectedPoints[] = $mdata['percentage'];
            $actualPoints[] = null;
        }
    }

    $color = $colors[$colorIdx % count($colors)];

    // SOLID LINE FOR ACTUALS
    $datasets[] = [
        'label' => "Code: $code (Actual)",
        'data' => $actualPoints,
        'borderColor' => $color,
        'backgroundColor' => 'transparent',
        'borderWidth' => 3,
        'tension' => 0.3,
        'spanGaps' => false
    ];

    // DOTTED LINE FOR PROJECTIONS
    $datasets[] = [
        'label' => "Code: $code (Projected)",
        'data' => $projectedPoints,
        'borderColor' => $color,
        'backgroundColor' => 'transparent',
        'borderWidth' => 2,
        'borderDash' => [5, 5],
        'tension' => 0.3,
        'spanGaps' => true
    ];

    $colorIdx++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Budget Tracking Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: right; }
        th { background-color: #e9ecef; text-align: center; font-weight: bold; }
        .left { text-align: left; }

        .section-title { margin-top: 40px; font-size: 20px; color: #007bff; border-left: 5px solid #007bff; padding-left: 10px; }

        .prediction-box { margin-top: 30px; padding: 25px; border-radius: 12px; background: #2c3e50; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .prediction-item { text-align: center; flex: 1; }
        .prediction-item h4 { margin: 0; font-size: 12px; text-transform: uppercase; color: #bdc3c7; }
        .prediction-item .val { font-size: 24px; font-weight: bold; margin-top: 5px; }
        .prediction-divider { width: 1px; height: 50px; background: #455a64; }

        .chart-container { margin-top: 20px; padding: 20px; background: #fcfcfc; border: 1px solid #eee; border-radius: 8px; height: 450px; }
        .legend { display: flex; gap: 20px; justify-content: center; margin-top: 20px; font-size: 12px; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Budget Tracking Dashboard</h1>
        <div style="font-size: 18px; font-weight: bold; color: #007bff;">FY: <?= date('Y', strtotime($fyStart)) ?>-<?= date('y', strtotime($fyEnd)) ?></div>
    </div>

    <form method="GET" class="filter-bar">
        <input type="hidden" name="action" value="budget_tracking">
        <label>Period From:</label>
        <input type="date" name="fy_start" value="<?= $fyStart ?>" style="padding:8px; border:1px solid #ccc; border-radius:4px;">
        <label>To:</label>
        <input type="date" name="fy_end" value="<?= $fyEnd ?>" style="padding:8px; border:1px solid #ccc; border-radius:4px;">
        <button type="submit" style="padding:8px 15px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer;">Update Report</button>
    </form>

    <div class="prediction-box">
        <div class="prediction-item"><h4>Total Provision</h4><div class="val"><?= formatInLakhs($totalProvision) ?></div></div>
        <div class="prediction-divider"></div>
        <div class="prediction-item"><h4>Actual Spent</h4><div class="val"><?= formatInLakhs($totalActual) ?></div></div>
        <div class="prediction-divider"></div>
        <div class="prediction-item"><h4>Projected (To Feb)</h4><div class="val"><?= formatInLakhs($totalProjected) ?></div></div>
        <div class="prediction-divider"></div>
        <div class="prediction-item"><h4>Predicted Total</h4><div class="val" style="color: #f1c40f;"><?= formatInLakhs($grandTotalPredicted) ?></div></div>
        <div class="prediction-divider"></div>
        <div class="prediction-item"><h4>Projected Balance</h4><div class="val" style="color: <?= $projectedBalance < 0 ? '#e74c3c' : '#2ecc71' ?>;"><?= formatInLakhs($projectedBalance) ?></div></div>
    </div>

    <div class="section-title">Budget Utilization Trend (Solid = Actual, Dotted = Projected)</div>
    <div class="chart-container">
        <canvas id="budgetLineChart"></canvas>
    </div>
    <div class="legend">
        <div class="legend-item"><span style="width:30px; height:3px; background:#3498db; display:inline-block;"></span> <strong>Actual Expenditure</strong></div>
        <div class="legend-item"><span style="width:30px; height:3px; border-top:3px dashed #bdc3c7; display:inline-block;"></span> <strong>Scope-Based Projection (Upto Feb)</strong></div>
    </div>

    <div class="section-title">Budget Head Summary</div>
    <table>
        <thead>
            <tr>
                <th>S.No.</th>
                <th class="left">Budget Code / Work Name</th>
                <th>Total Provision</th>
                <th>Total AA & ES Booked</th>
                <th>Expenditure Booked (Gross)</th>
                <th>Paid (Gross)</th>
                <th>Balance Left</th>
            </tr>
        </thead>
        <tbody>
            <?php $sno = 1; foreach ($report as $row): ?>
            <tr>
                <td style="text-align:center;"><?= $sno++ ?></td>
                <td class="left"><strong><?= htmlspecialchars($row['code']) ?></strong><br><small style="color:#666;"><?= htmlspecialchars($row['name']) ?></small></td>
                <td style="color:#0056b3; font-weight:bold;"><?= formatInLakhs($row['provision']) ?></td>
                <td style="color:#0056b3; font-weight:bold;"><?= formatInLakhs($row['aa_es']) ?></td>
                <td style="color:#dc3545;"><?= formatIndianCurrency($row['exp_booked']) ?></td>
                <td style="color:#28a745;"><?= formatIndianCurrency($row['paid']) ?></td>
                <td style="font-weight:bold; color:<?= $row['balance'] < 0 ? '#dc3545' : '#28a745' ?>;"><?= formatInLakhs($row['balance']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    const ctx = document.getElementById('budgetLineChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: <?= json_encode($datasets) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Utilization Percentage (%)' },
                    ticks: { callback: function(value) { return value + '%'; } }
                }
            },
            plugins: {
                legend: { display: false } // Custom legend used below
            }
        }
    });
</script>
</body>
</html>
