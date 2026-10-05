<?php
/** @var array $reportData */
/** @var string|null $fromDate */
/** @var string|null $toDate */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bill Status Report</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1200px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .no-print { margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="container">
    <h1>Bill Status Report</h1>

    <form class="no-print" method="GET">
        <input type="hidden" name="action" value="billStatus">
        From: <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate ?? '') ?>">
        To: <input type="date" name="to_date" value="<?= htmlspecialchars($toDate ?? '') ?>">
        <button type="submit">Filter</button>
        <button type="button" onclick="window.print()">Print</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Office Bill No</th>
                <th>Agency</th>
                <th>Type</th>
                <th>Gross Amount</th>
                <th>Net Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportData as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['bill_date']) ?></td>
                <td><?= htmlspecialchars($row['office_bill_no']) ?></td>
                <td><?= htmlspecialchars($row['agency_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['bill_type']) ?></td>
                <td><?= number_format((float)$row['gross_amount'], 2) ?></td>
                <td><?= number_format((float)$row['net_amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
