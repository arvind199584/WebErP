<?php
$data = $_SESSION['payment_create_data'] ?? [];
$agreementId = $data['agreement_id'] ?? null;
$periodFrom = $data['period_from'] ?? null;
$periodTo = $data['period_to'] ?? null;
$agreementType = 'Manpower';

$totalDaysInPeriod = 0;
if ($periodFrom && $periodTo) {
    $start = new DateTime($periodFrom);
    $end = new DateTime($periodTo);
    $totalDaysInPeriod = $end->diff($start)->format("%a") + 1;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Bill - Step 2</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-back { background-color: #6c757d; text-decoration: none; }
        .btn-verify { background-color: #17a2b8; margin-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        .breakdown-cell { font-size: 12px; color: #666; }
        .period-info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 5px solid #007bff; }
        .period-info h3 { margin: 0 0 5px 0; color: #0056b3; }
        .formula-box { background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 13px; border: 1px solid #ffeeba; }

        #daily_attendance_section { margin-top: 40px; border-top: 2px solid #eee; padding-top: 20px; display: none; }
        .daily-table { font-size: 12px; }
        .daily-table th { background-color: #f8f9fa; }
    </style>
    <script>
        async function generateItems() {
            const agreementId = "<?= $agreementId ?>";
            const fromDate = "<?= $periodFrom ?>";
            const toDate = "<?= $periodTo ?>";

            if (!agreementId || !fromDate || !toDate) {
                alert('Missing data from Step 1.'); return;
            }

            const btn = document.getElementById('generate_btn');
            btn.disabled = true;
            btn.innerText = 'Generating...';

            try {
                const response = await fetch(`?action=generateBillItemsAjax&agreement_id=${agreementId}&from_date=${fromDate}&to_date=${toDate}`);
                const result = await response.json();

                if (result.success) {
                    populateTable(result.bill_items);
                    document.getElementById('bill_items_json').value = JSON.stringify(result.bill_items);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Auto-Generate Bill Items';
            }
        }

        async function verifyAttendance() {
            const agreementId = "<?= $agreementId ?>";
            const fromDate = "<?= $periodFrom ?>";
            const toDate = "<?= $periodTo ?>";

            const section = document.getElementById('daily_attendance_section');
            section.style.display = 'block';
            const tbody = document.getElementById('daily_attendance_body');
            tbody.innerHTML = '<tr><td colspan="2">Loading daily summary...</td></tr>';

            try {
                const response = await fetch(`?action=getDailyAttendanceAjax&agreement_id=${agreementId}&from_date=${fromDate}&to_date=${toDate}`);
                const result = await response.json();

                if (result.success) {
                    tbody.innerHTML = '';
                    if (result.summary.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="2">No attendance records found for this period.</td></tr>';
                        return;
                    }

                    // Group by date for better display
                    let currentDate = '';
                    result.summary.forEach(row => {
                        const tr = document.createElement('tr');
                        const dateDisplay = row.attendance_date !== currentDate ? row.attendance_date : '';
                        tr.innerHTML = `
                            <td style="font-weight:${dateDisplay ? 'bold' : 'normal'}">${dateDisplay}</td>
                            <td>${row.designation}: <strong>${row.present_count}</strong></td>
                        `;
                        tbody.appendChild(tr);
                        currentDate = row.attendance_date;
                    });
                }
            } catch (error) {
                alert('Failed to load daily summary.');
            }
        }

        function populateTable(items) {
            const tableBody = document.getElementById('items_table_body');
            tableBody.innerHTML = '';
            items.forEach(item => {
                const b = item.breakdown;
                const row = `<tr>
                    <td>${item.description}</td>
                    <td class="breakdown-cell">P:${b.P}, R:${b.R}, L:${b.L}, A:${b.A}</td>
                    <td><strong>${item.qty}</strong></td>
                    <td>${item.unit}</td>
                    <td>${item.rate}</td>
                    <td>₹ ${(item.qty * item.rate).toFixed(2)}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Step 2: Financials</h1>

    <div class="period-info">
        <h3>Effective Calculation Period</h3>
        <p>
            <strong>From:</strong> <?= date('d.m.Y', strtotime($periodFrom)) ?>
            &nbsp;&nbsp; <strong>To:</strong> <?= date('d.m.Y', strtotime($periodTo)) ?>
            &nbsp;&nbsp; (Total: <strong><?= $totalDaysInPeriod ?> days</strong>)
        </p>
    </div>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="bill_items" id="bill_items_json">

        <div style="margin-bottom: 20px;">
            <button type="button" id="generate_btn" class="btn" onclick="generateItems()">Auto-Generate Bill Items</button>
            <button type="button" class="btn btn-verify" onclick="verifyAttendance()">Verify Daily Attendance</button>
        </div>

        <div class="formula-box">
            <strong>Calculation Formula:</strong> <code>Qty = Present Days / 26</code>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Attendance Breakdown</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Rate</th>
                    <th>Taxable Amt</th>
                </tr>
            </thead>
            <tbody id="items_table_body">
                <tr><td colspan="6" style="text-align:center; color:#666;">Click "Auto-Generate" to load items.</td></tr>
            </tbody>
        </table>

        <div id="daily_attendance_section">
            <h3>Daily Attendance Verification</h3>
            <table class="daily-table">
                <thead><tr><th>Date</th><th>Designation & Count</th></tr></thead>
                <tbody id="daily_attendance_body"></tbody>
            </table>
        </div>

        <hr>
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <a href="?action=showCreateForm&step=1" class="btn btn-back">&larr; Back</a>
            <button type="submit" class="btn">Next: Summary &rarr;</button>
        </div>
    </form>
</div>
</body>
</html>
