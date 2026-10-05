<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Register - <?= htmlspecialchars($month) ?></title>
    <style>
        body { font-family: 'Segoe UI', serif; font-size: 10px; color: #000; margin: 0; padding: 20px; background: #fff; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; text-decoration: underline; }
        .header p { margin: 2px 0; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 2px; text-align: center; overflow: hidden; }
        th { background-color: #f2f2f2; font-size: 9px; }
        .name-col { width: 120px; text-align: left; padding-left: 5px; font-weight: bold; }
        .desig-col { width: 80px; text-align: left; padding-left: 5px; font-size: 8px; }
        .day-col { width: 18px; }
        .sum-col { width: 22px; font-weight: bold; background-color: #f9f9f9; }
        .status-P { color: green; }
        .status-A { color: red; font-weight: bold; }
        .status-R { color: blue; }
        .status-L { color: transparent; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .sig-box { text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px; margin-top: 40px; }
        .no-print-zone { text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-print { padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: 10px; }
        .btn-excel { background: #28a745; } /* Green color for Excel button */
        @media print { .no-print-zone { display: none; } body { padding: 0; } @page { size: landscape; margin: 1cm; } }
    </style>
</head>
<body>
    <div class="no-print-zone">
        <a href="/modules/ReportPrinting/Controller/AttendanceReportController.php?action=exportExcel&agreement_id=<?= htmlspecialchars($_GET['agreement_id']) ?>&month=<?= htmlspecialchars($month) ?>" class="btn-print btn-excel">Export to Excel</a>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn-print" style="background:#6c757d;" onclick="window.close()">Close Window</button>
    </div>

    <div class="header">
        <div style="font-weight: bold; font-size: 16px; margin-bottom: 5px; text-transform: uppercase;">Delhi Development Authority</div>
        <div style="font-size: 14px; margin-bottom: 10px;"><?= date('F, Y', strtotime($month . '-01')) ?></div>

        <div style="display: flex; justify-content: space-between; text-align: left; margin-top: 10px;">
            <div style="flex: 1.5; text-align: left;">
                <div style="margin-bottom: 3px;"><strong>Work:</strong> <?= htmlspecialchars($header['name_of_work']) ?></div>
                <div><strong>Sub-Head:</strong> <?= htmlspecialchars($header['sub_head']) ?></div>
            </div>
            <div style="flex: 1; text-align: right;">
                <div style="margin-bottom: 3px;"><strong>Agreement:</strong> <?= htmlspecialchars($header['agreement_no']) ?></div>
                <div><strong>Agency:</strong> <?= htmlspecialchars($header['agency_name']) ?></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;" rowspan="2">S.No.</th>
                <th class="name-col" rowspan="2">Name of Employee</th>
                <th class="desig-col" rowspan="2">Designation</th>
                <th colspan="<?= $daysInMonth ?>">Days of Month</th>
                <th colspan="4">Summary</th>
            </tr>
            <tr>
                <?php for($d=1; $d<=$daysInMonth; $d++): ?>
                    <th class="day-col"><?= $d ?></th>
                <?php endfor; ?>
                <th class="sum-col">P</th>
                <th class="sum-col">A</th>
                <th class="sum-col">R</th>
                <th class="sum-col">L</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalP = 0;
            $sno = 1;
            $dailyTotals = array_fill(1, $daysInMonth, 0);
            foreach ($data as $row): 
                $totalP += $row['summary']['P'];
            ?>
            <tr>
                <td style="text-align: center;"><?= $sno++ ?></td>
                <td class="name-col"><?= htmlspecialchars($row['name']) ?></td>
                <td class="desig-col"><?= htmlspecialchars($row['designation']) ?></td>
                <?php for($d=1; $d<=$daysInMonth; $d++):
                    $status = $row['days'][$d];
                    if ($status === 'P') $dailyTotals[$d]++;
                ?>
                    <td class="day-col status-<?= $status ?>"><?= ($status === 'L') ? '' : $status ?></td>
                <?php endfor; ?>
                <td class="sum-col"><?= $row['summary']['P'] ?></td>
                <td class="sum-col"><?= $row['summary']['A'] ?></td>
                <td class="sum-col"><?= $row['summary']['R'] ?></td>
                <td class="sum-col"><?= $row['summary']['L'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #eee;">
                <td colspan="3" style="text-align: right; padding-right: 10px;">TOTAL</td>
                <?php for($d=1; $d<=$daysInMonth; $d++): ?>
                    <td><?= $dailyTotals[$d] ?></td>
                <?php endfor; ?>
                <td class="sum-col"><?= $totalP ?></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="sig-box">Secretary/<?= htmlspecialchars($header['Shortname']) ?></div>
        <div class="sig-box"><?= htmlspecialchars($header['signatory']) ?>/<?= htmlspecialchars($header['Shortname']) ?></div>
    </div>

    <script>
        window.onload = function() { setTimeout(() => { window.print(); }, 500); };
    </script>
</body>
</html>
