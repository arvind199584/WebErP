<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hand Receipt - <?= $id ?></title>
    <style>
        body { font-family: 'Segoe UI', serif; font-size: 14px; color: #000; margin: 0; padding: 60px; background: #fff; }
        .receipt-box { border: 2px solid #000; padding: 30px; min-height: 400px; position: relative; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { margin: 0; text-decoration: underline; }
        .row { margin-bottom: 15px; line-height: 1.6; }
        .amount-box { margin-top: 40px; font-weight: bold; font-size: 18px; }
        .footer { margin-top: 80px; display: flex; justify-content: space-between; }
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

    <div class="receipt-box">
        <div class="header">
            <h1>HAND RECEIPT</h1>
            <p>DELHI DEVELOPMENT AUTHORITY</p>
        </div>

        <div class="row">Received from the <b><?= htmlspecialchars($office_name) ?></b></div>
        <div class="row">The sum of Rupees <b><?= htmlspecialchars($net_amount_words) ?></b> only.</div>
        <div class="row">On account of: <b><?= htmlspecialchars($category) ?></b></div>
        <div class="row">Description: <?= htmlspecialchars($description) ?></div>

        <div class="amount-box">
            Net Amount: ₹ <?= number_format($net_amount, 2) ?>
        </div>

        <div class="footer">
            <div>Date: <?= date('d.m.Y', strtotime($created_at)) ?></div>
            <div style="text-align: center; border-top: 1px solid #000; width: 150px; padding-top: 5px;">Signature of Payee</div>
        </div>
    </div>

    <script>
        window.onload = function() { setTimeout(() => { window.print(); }, 500); };
    </script>
</body>
</html>
