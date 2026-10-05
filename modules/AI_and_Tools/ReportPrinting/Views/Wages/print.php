<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wages Verification - <?= htmlspecialchars($header['agreement_no']) ?></title>
    <style>
        body { font-family: 'Segoe UI', serif; font-size: 12px; color: #000; margin: 0; padding: 40px; background: #fff; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .right { text-align: right; }
        .no-print-zone { text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-print { padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: 10px; }
        @media print { .no-print-zone { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print-zone">
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn-print" style="background:#6c757d;" onclick="window.close()">Close Window</button>
    </div>

    <div class="header">
        <h1>WAGES VERIFICATION STATEMENT</h1>
        <p>Agreement: <?= htmlspecialchars($header['agreement_no']) ?></p>
        <p>Period: <?= date('d.m.Y', strtotime($params['from'])) ?> to <?= date('d.m.Y', strtotime($params['to'])) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Employee Name</th>
                <th class="right">Due Amount</th>
                <th class="right">Drawn Amount</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php $sno=1; foreach ($data as $row): ?>
            <tr>
                <td><?= $sno++ ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td class="right"><?= number_format($row['due'], 2) ?></td>
                <td class="right"><?= number_format($row['drawn'], 2) ?></td>
                <td class="right"><?= number_format($row['balance'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        window.onload = function() { setTimeout(() => { window.print(); }, 500); };
    </script>
</body>
</html>
