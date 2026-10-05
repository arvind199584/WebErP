<?php
/**
 * @var array $agreements
 * @var array $dashboardData
 * @var int|null $selectedAgreementId
 * @var string|null $message
 * @var string|null $error
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Process Wages</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-bar { display: flex; gap: 15px; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
        .btn { padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; }
        .btn-success { background-color: #28a745; }
        .btn-debit { background-color: #ffc107; color: #212529; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }

        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 25px; width: 450px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
    <script>
        function openCalcModal(employeeId, employeeName, fromDate, toDate) {
            if (!fromDate || fromDate === 'N/A') {
                alert('No pending period found for this employee.');
                return;
            }
            document.getElementById('calc_modal_employee_id').value = employeeId;
            document.getElementById('calc_employee_name').innerText = employeeName;
            document.getElementById('from_date').value = fromDate;
            document.getElementById('to_date').value = toDate;
            document.getElementById('calcModal').style.display = 'block';
        }

        function openDebitModal(employeeId, employeeName) {
            document.getElementById('debit_modal_employee_id').value = employeeId;
            document.getElementById('debit_employee_name').innerText = employeeName;
            document.getElementById('debitModal').style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header-row">
        <h1>Process Employee Wages</h1>
        <a href="?action=index" class="btn" style="background-color: #6c757d;">&laquo; Back</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="header-row" style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
        <form method="GET" action="" class="filter-bar">
            <input type="hidden" name="action" value="showProcess">
            <label>Select Agreement:</label>
            <select name="agreement_id" onchange="this.form.submit()" required>
                <option value="">-- Select --</option>
                <?php foreach ($agreements as $agreement): ?>
                    <option value="<?= $agreement['id'] ?>" <?= ($selectedAgreementId == $agreement['id']) ? 'selected' : '' ?>><?= htmlspecialchars($agreement['agreement_no']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedAgreementId): ?>
            <a href="?action=generateAll&agreement_id=<?= $selectedAgreementId ?>"
               class="btn btn-success"
               onclick="return confirm('This will generate wages for ALL employees with pending periods. Continue?')">Calculate All Pending Wages</a>
        <?php endif; ?>
    </div>

    <?php if ($selectedAgreementId): ?>
        <table style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Employee</th>
                    <th>Calculated Up To</th>
                    <th>Pending Period</th>
                    <th style="text-align:right;">Current Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($dashboardData as $row): ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($row['designation']) ?></small>
                    </td>
                    <td><?= $row['last_wage_date'] ? date('d.m.Y', strtotime($row['last_wage_date'])) : 'Never' ?></td>
                    <td>
                        <?php if ($row['pending_from'] && strtotime($row['pending_from']) <= strtotime($row['pending_to'])): ?>
                            <span style="color:orange; font-weight:bold;"><?= date('d.m.y', strtotime($row['pending_from'])) ?> to <?= date('d.m.y', strtotime($row['pending_to'])) ?></span>
                        <?php else: ?>
                            <span style="color:green;">Up to Date</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right; font-family: monospace;">₹ <?= number_format((float)$row['current_balance'], 2) ?></td>
                    <td>
                        <button class="btn btn-sm" onclick="openCalcModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['full_name']) ?>', '<?= $row['pending_from'] ?>', '<?= $row['pending_to'] ?>')">Calculate</button>
                        <button class="btn btn-debit btn-sm" onclick="openDebitModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['full_name']) ?>')">Add Debit</button>
                        <a href="?action=viewLedger&employee_id=<?= $row['id'] ?>" class="btn btn-sm" style="background-color: #17a2b8;">Ledger</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; margin-top: 40px; color: #666;">Please select an agreement to view the wage processing dashboard.</p>
    <?php endif; ?>
</div>

<!-- Calculate Modal -->
<div id="calcModal" class="modal">
    <div class="modal-content">
        <h3>Generate Wages for <span id="calc_employee_name"></span></h3>
        <form method="POST" action="?action=generate">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="employee_id" id="calc_modal_employee_id">
            <div class="form-group">
                <label>From Date:</label>
                <input type="date" name="from_date" id="from_date" required>
            </div>
            <div class="form-group">
                <label>To Date:</label>
                <input type="date" name="to_date" id="to_date" required>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeModal('calcModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Generate Wages</button>
            </div>
        </form>
    </div>
</div>

<!-- Debit Modal -->
<div id="debitModal" class="modal">
    <div class="modal-content">
        <h3>Add Debit Entry for <span id="debit_employee_name"></span></h3>
        <form method="POST" action="?action=addDebit">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="employee_id" id="debit_modal_employee_id">
            <div class="form-group">
                <label>Transaction Date:</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Amount (₹):</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div class="form-group">
                <label>Remark:</label>
                <textarea name="remark" rows="2" placeholder="e.g. Payment for Dec 2025"></textarea>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeModal('debitModal')">Cancel</button>
                <button type="submit" class="btn btn-debit">Save Debit</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
