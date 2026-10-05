<?php
/**
 * @var array $agreements
 * @var array $attendanceData
 * @var int|null $selectedAgreementId
 * @var string $selectedMonth
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>View/Edit Attendance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 95%; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; align-items: center; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: center; white-space: nowrap; }
        thead th { background-color: #e9ecef; position: sticky; top: 0; z-index: 1; }
        .name-col { text-align: left; width: 250px; position: sticky; left: 0; background-color: #f2f2f2; z-index: 2; }
        select { width: 100%; border: none; background: transparent; }
        .summary-col { background-color: #e9ecef; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>View / Edit Attendance Sheet</h1>
    <form method="GET" action="?" class="filter-bar">
        <input type="hidden" name="action" value="edit">
        <label for="agreement_id">Agreement:</label>
        <select id="agreement_id" name="agreement_id" onchange="this.form.submit()">
            <option value="">-- Select --</option>
            <?php foreach ($agreements as $agreement): ?>
                <option value="<?= $agreement['id'] ?>" <?= ($selectedAgreementId == $agreement['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($agreement['agreement_no']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="month">Month:</label>
        <input type="month" id="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
    </form>

    <?php if ($selectedAgreementId && !empty($attendanceData)): ?>
    <form action="?action=saveSheet" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="agreement_id" value="<?= $selectedAgreementId ?>">
        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="name-col">Name</th>
                        <?php $daysInMonth = count(reset($attendanceData)['days']); ?>
                        <?php for ($i = 1; $i <= $daysInMonth; $i++): ?>
                            <th><?= $i ?></th>
                        <?php endfor; ?>
                        <th class="summary-col">P</th><th class="summary-col">A</th><th class="summary-col">R</th><th class="summary-col">L</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceData as $row): ?>
                    <tr>
                        <td class="name-col"><?= htmlspecialchars($row['full_name']) ?></td>
                        <?php foreach ($row['days'] as $day => $status): ?>
                            <td>
                                <select name="attendance[<?= $row['employee_id'] ?>][<?= $selectedMonth ?>-<?= str_pad((string)$day, 2, '0', STR_PAD_LEFT) ?>]">
                                    <option value="P" <?= $status == 'P' ? 'selected' : '' ?>>P</option>
                                    <option value="A" <?= $status == 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="R" <?= $status == 'R' ? 'selected' : '' ?>>R</option>
                                    <option value="L" <?= $status == 'L' ? 'selected' : '' ?>>L</option>
                                </select>
                            </td>
                        <?php endforeach; ?>
                        <td class="summary-col"><?= $row['summary']['P'] ?></td>
                        <td class="summary-col"><?= $row['summary']['A'] ?></td>
                        <td class="summary-col"><?= $row['summary']['R'] ?></td>
                        <td class="summary-col"><?= $row['summary']['L'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: right; margin-top: 20px;">
            <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px;">Save Changes</button>
        </div>
    </form>
    <?php elseif ($selectedAgreementId): ?>
        <p>No attendance data found for this selection.</p>
    <?php endif; ?>
</div>
</body>
</html>
