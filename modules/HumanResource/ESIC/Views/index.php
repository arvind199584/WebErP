<?php
/**
 * @var array $agreements
 * @var array $employees
 * @var int|null $selectedAgreementId
 * @var string $selectedMonth
 */
$pendingEmployees = array_filter($employees, fn($e) => !$e['has_contribution']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>ESIC Contribution</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .btn { padding: 8px 12px; border: none; cursor: pointer; color: white; border-radius: 4px; }
        .btn-generate { background-color: #28a745; }
        .btn-generate:disabled { background-color: #6c757d; }
    </style>
</head>
<body>
<div class="container">
    <h1>ESIC Contribution Processing</h1>
    <form method="GET" action="?" class="filter-bar">
        <input type="hidden" name="action" value="list">
        <label>Agreement:</label>
        <select name="agreement_id" onchange="this.form.submit()">
            <option value="">-- Select --</option>
            <?php foreach ($agreements as $agreement): ?>
                <option value="<?= $agreement['id'] ?>" <?= ($selectedAgreementId == $agreement['id']) ? 'selected' : '' ?>><?= htmlspecialchars($agreement['agreement_no']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Month:</label>
        <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
    </form>

    <?php if ($selectedAgreementId): ?>
    <form action="?action=generate" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
        <div style="margin-bottom: 15px;">
            <button type="submit" class="btn btn-generate" <?= empty($pendingEmployees) ? 'disabled' : '' ?>>
                Generate for Selected (<?= count($pendingEmployees) ?>)
            </button>
        </div>
        <table>
            <thead><tr><th><input type="checkbox" onchange="document.querySelectorAll('.emp-check').forEach(c => c.checked = this.checked)"></th><th>S.No.</th><th>Name</th><th>Status</th></tr></thead>
            <tbody>
                <?php $sno = 1; foreach ($employees as $emp): ?>
                <tr>
                    <td>
                        <?php if (!$emp['has_contribution']): ?>
                            <input type="checkbox" class="emp-check" name="employee_ids[]" value="<?= $emp['id'] ?>" checked>
                        <?php endif; ?>
                    </td>
                    <td><?= $sno++ ?></td>
                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                    <td><?= $emp['has_contribution'] ? '<span style="color:green;">Generated</span>' : 'Pending' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
