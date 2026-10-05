<h2>Agreement Report</h2>
<table>
    <thead><tr><th>Agreement No</th><th>Agency</th><th>Tendered Amount</th><th>Period</th><th>Status</th></tr></thead>
    <tbody>
        <?php foreach ($reportData as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['agreement_no']) ?></td>
            <td><?= htmlspecialchars($row['agency_name']) ?></td>
            <td><?= number_format((float)$row['tendered_amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['period']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
