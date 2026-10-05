<h2>Bill Report</h2>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Bill No</th>
            <th>Agency</th>
            <th>Type</th>
            <th>Net Amount</th>
            <th>Status</th>
            <th class="no-print">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reportData as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['bill_date']) ?></td>
            <td><?= htmlspecialchars($row['office_bill_no']) ?></td>
            <td><?= htmlspecialchars($row['agency_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['bill_type']) ?></td>
            <td><?= number_format((float)$row['net_amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td class="no-print">
                <a href="/modules/ReportPrinting/Controller/ReportPrintingController.php?action=printBill&id=<?= $row['id'] ?>" target="_blank" style="color: #007bff; text-decoration: none;">Print Bill</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
