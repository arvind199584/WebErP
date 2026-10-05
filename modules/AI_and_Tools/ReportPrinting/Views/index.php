<!DOCTYPE html>
<html>
<head>
    <title>Report Printing Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 60px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; }
        h1 { color: #2c3e50; margin-bottom: 30px; }
        .report-selector { margin-top: 20px; }
        select { width: 100%; padding: 15px; font-size: 16px; border: 2px solid #e0e0e0; border-radius: 8px; background-color: #f8f9fa; cursor: pointer; transition: border-color 0.3s; }
        select:focus { border-color: #007bff; outline: none; }
        .btn { display: inline-block; margin-top: 30px; padding: 15px 40px; background-color: #007bff; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px; transition: background 0.3s; }
        .btn:hover { background-color: #0056b3; }
        .icon-large { font-size: 60px; margin-bottom: 20px; color: #007bff; }
    </style>
    <script>
        function goToReport() {
            const url = document.getElementById('report_select').value;
            if (url) {
                window.location.href = url;
            } else {
                alert('Please select a report type.');
            }
        }
    </script>
</head>
<body>
<div class="container">
    <div class="icon-large">🖨️</div>
    <h1>Report Printing Hub</h1>
    <p style="color: #666; margin-bottom: 30px;">Select the type of report you wish to generate and print.</p>

    <div class="report-selector">
        <select id="report_select">
            <option value="">-- Choose a Report --</option>
            <optgroup label="Financial Reports">
                <option value="../Controller/BillReportController.php">Bill Printing (Abstract/Memo)</option>
                <option value="../Controller/ExpenditureReportController.php">Agreement Expenditure Statement</option>
                <option value="../../Utility/Controller/UtilityController.php?action=budget_tracking">Budget Tracking Dashboard</option>
                <option value="../Controller/HandReceiptPrintController.php">Hand Receipts</option>
            </optgroup>
            <optgroup label="HR & Attendance">
                <option value="../Controller/AttendanceReportController.php">Attendance Register</option>
                <option value="../Controller/WagesReportController.php">Wages Verification (Due/Drawn)</option>
                <option value="../Controller/ESICReportController.php">ESIC Verification</option>
                <option value="../Controller/EPFReportController.php">EPF Verification</option>
            </optgroup>
            <optgroup label="Works & Sanctions">
                <option value="../Controller/AAESReportController.php">AA & ES Calculation Sheet</option>
            </optgroup>
        </select>
    </div>

    <button onclick="goToReport()" class="btn">Open Report Module</button>
</div>
</body>
</html>
