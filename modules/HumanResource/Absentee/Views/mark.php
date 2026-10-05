<?php
/**
 * @var array $agreements
 * @var array $employees
 * @var int|null $selectedAgreementId
 * @var string $selectedMonth
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        thead th { background-color: #e9ecef; }
        input[type="text"] { width: 95%; padding: 5px; }
        .btn-save { padding: 5px 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
    </style>
    <script>
        function getDaysInMonth(year, month, dayOfWeek) {
            const days = [];
            const date = new Date(year, month - 1, 1);
            const dayIndex = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].indexOf(dayOfWeek);
            if (dayIndex === -1) return '';
            while (date.getMonth() === month - 1) {
                if (date.getDay() === dayIndex) { days.push(date.getDate()); }
                date.setDate(date.getDate() + 1);
            }
            return days.join(',');
        }
        function addHoliday() {
            const holidayDays = document.getElementById('holiday_days').value.split(',').map(d => d.trim()).filter(d => d);
            document.querySelectorAll('.rest-days-input').forEach(input => {
                let currentDays = new Set(input.value.split(',').filter(d => d.trim() !== ''));
                holidayDays.forEach(day => currentDays.add(day));
                input.value = Array.from(currentDays).map(Number).sort((a, b) => a - b).join(',');
            });
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Mark New Attendance</h1>
    <?php if (isset($_SESSION['message'])): ?><div class="message success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?><div class="message error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <form method="GET" action="?" class="filter-bar">
        <input type="hidden" name="action" value="mark">
        <label for="agreement_id">Agreement:</label>
        <select id="agreement_id" name="agreement_id" onchange="this.form.submit()">
            <option value="">-- Select --</option>
            <?php foreach ($agreements as $agreement): ?>
                <option value="<?= $agreement['id'] ?>" <?= ($selectedAgreementId == $agreement['id']) ? 'selected' : '' ?>><?= htmlspecialchars($agreement['agreement_no']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="month">Month:</label>
        <input type="month" id="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
    </form>
    <?php if ($selectedAgreementId): ?>
        <?php if (empty($employees)): ?>
            <p>All employees for this agreement and month have been marked. You can view/edit them in the "View / Edit Sheet" section.</p>
        <?php else: ?>
            <div class="holiday-bar" style="margin-bottom: 20px;">
                <label for="holiday_days">Add Holiday(s):</label>
                <input type="text" id="holiday_days" placeholder="e.g., 15, 26">
                <button type="button" onclick="addHoliday()">Add to All</button>
            </div>
            <table>
                <thead>
                    <tr><th>S.No.</th><th>Name</th><th>Designation</th><th>Attendance Dates</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php $sno = 1; foreach ($employees as $emp): ?>
                    <form action="?action=saveMark" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                        <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                        <input type="hidden" name="agreement_id" value="<?= $selectedAgreementId ?>">
                        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
                        <tr>
                            <td><?= $sno++ ?></td>
                            <td><?= htmlspecialchars($emp['full_name']) ?></td>
                            <td><?= htmlspecialchars($emp['designation']) ?></td>
                            <td>
                                <?php if ($emp['is_reliever']): ?>
                                    <label>Present Days:</label>
                                    <input type="text" name="present_days" placeholder="e.g., 1,2,5,10">
                                <?php else: ?>
                                    <?php [$year, $monthNum] = explode('-', $selectedMonth); $restDays = $emp['default_rest_day'] ? "getDaysInMonth($year, $monthNum, '{$emp['default_rest_day']}')" : "''"; ?>
                                    <label>Absent Days:</label> <input type="text" name="absent_days" placeholder="e.g., 3,4">
                                    <label>Leave Days:</label> <input type="text" name="leave_days" placeholder="e.g., 15,16">
                                    <label>Rest Days:</label> <input type="text" name="rest_days" class="rest-days-input" id="rest_days_<?= $emp['id'] ?>">
                                    <script>document.getElementById('rest_days_<?= $emp['id'] ?>').value = <?= $restDays ?>;</script>
                                <?php endif; ?>
                            </td>
                            <td><button type="submit" class="btn-save">Save</button></td>
                        </tr>
                    </form>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
