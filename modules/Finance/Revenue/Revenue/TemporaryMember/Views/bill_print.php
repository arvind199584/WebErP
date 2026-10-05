<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TM Receipt - <?= htmlspecialchars($member['tm_no']) ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #000; background: #fff; margin: 0; padding: 20px; font-size: 14px; }
        .bill-container { max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; position: relative; }
        .header { text-align: center; border-bottom: 1px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 5px 0; font-size: 22px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .bill-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        
        .section-title { background: #000; color: #fff; padding: 2px 10px; font-size: 12px; margin-bottom: 10px; display: inline-block; }
        
        .member-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .member-details div { border-bottom: 1px dotted #ccc; padding: 2px 0; }
        .label { font-weight: bold; width: 120px; display: inline-block; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .items-table th { background: #f0f0f0; }
        .text-right { text-align: right; }
        
        .totals { margin-top: 20px; border-top: 2px solid #000; padding-top: 10px; }
        .totals-row { display: flex; justify-content: flex-end; margin-bottom: 5px; }
        .totals-label { width: 150px; text-align: right; font-weight: bold; padding-right: 20px; }
        .totals-value { width: 100px; text-align: right; font-weight: bold; }
        
        .footer { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-box { text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px; }
        
        .no-print { position: fixed; top: 10px; right: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 20px; background: #28a745; color: #fff; border: none; cursor: pointer; border-radius: 4px;">🖨️ Print Receipt</button>
    <button onclick="window.history.back()" style="padding: 10px 20px; background: #6c757d; color: #fff; border: none; cursor: pointer; border-radius: 4px;">⬅️ Back</button>
</div>

<div class="bill-container">
    <div class="header">
        <h1><?= htmlspecialchars($office['officename']) ?></h1>
        <p><?= htmlspecialchars($office['address']) ?></p>
        <p>Email: <?= htmlspecialchars($office['email']) ?> | Tel: <?= htmlspecialchars($office['phone']) ?></p>
        <h2 style="margin-top: 15px; text-decoration: underline;">TEMPORARY MEMBERSHIP RECEIPT</h2>
    </div>

    <div class="bill-info">
        <span>Receipt No: <?= htmlspecialchars($member['tm_no']) ?></span>
        <span>Ref: <?= htmlspecialchars($member['payment_reference'] ?? 'N/A') ?></span>
        <span>Date: <?= date('d-M-Y', strtotime($member['created_at'])) ?></span>
    </div>

    <div class="section-title">MEMBER INFORMATION</div>
    <div class="member-details">
        <div><span class="label">Name:</span> <?= htmlspecialchars($member['name']) ?></div>
        <div><span class="label">DOB:</span> <?= date('d-M-Y', strtotime($member['dob'])) ?></div>
        <div style="grid-column: 1 / -1;"><span class="label">Address:</span> <?= htmlspecialchars($member['address']) ?></div>
        <div><span class="label">Validity:</span> <?= htmlspecialchars(str_replace(['[', ']', '"'], '', $member['validity'])) ?></div>
        <div><span class="label">I-Cards Issued:</span> <?= 1 + $breakdown['dep_count'] ?></div>
    </div>

    <?php if ($breakdown['dep_count'] > 0): ?>
    <div class="section-title">DEPENDENTS</div>
    <table class="items-table" style="font-size: 11px; margin-bottom: 20px;">
        <thead>
            <tr>
                <th>Sr.</th>
                <th>Name</th>
                <th>Relation</th>
                <th>Date of Birth</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $deps = json_decode($member['dependents'], true);
            foreach ($deps as $i => $d): 
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td><?= htmlspecialchars($d['relation']) ?></td>
                <td><?= date('d-M-Y', strtotime($d['dob'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="section-title">PAYMENT BREAKDOWN</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Rate (₹)</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Main Membership Fee</td>
                <td class="text-right"><?= number_format((float)$rates['main_member_rate'], 2) ?></td>
                <td class="text-right">1</td>
                <td class="text-right"><?= number_format((float)$rates['main_member_rate'], 2) ?></td>
            </tr>
            <?php if ($breakdown['dep_count'] > 0): ?>
            <tr>
                <td>Dependent Membership Fee</td>
                <td class="text-right"><?= number_format((float)$rates['dependent_rate'], 2) ?></td>
                <td class="text-right"><?= $breakdown['dep_count'] ?></td>
                <td class="text-right"><?= number_format((float)($breakdown['dep_count'] * $rates['dependent_rate']), 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Identity Card Charges</td>
                <td class="text-right"><?= number_format((float)$rates['icard_rate'], 2) ?></td>
                <td class="text-right"><?= 1 + $breakdown['dep_count'] ?></td>
                <td class="text-right"><?= number_format((float)$breakdown['icard_total'], 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Subtotal:</span>
            <span class="totals-value">₹<?= number_format((float)$breakdown['subtotal'], 2) ?></span>
        </div>
        <div class="totals-row">
            <span class="totals-label">GST (<?= (float)$rates['gst_percent'] ?>%):</span>
            <span class="totals-value">₹<?= number_format((float)$breakdown['gst_amount'], 2) ?></span>
        </div>
        <div class="totals-row" style="font-size: 18px; border-top: 1px dashed #000; margin-top: 5px; padding-top: 5px;">
            <span class="totals-label">TOTAL PAID:</span>
            <span class="totals-value">₹<?= number_format((float)$breakdown['total'], 2) ?></span>
        </div>
    </div>

    <div class="footer">
        <div style="font-size: 10px; font-style: italic;">
            * This is a computer generated receipt.<br>
            * Membership is subject to rules and regulations.
        </div>
        <div class="signature-box">
            Authorized Signatory
        </div>
    </div>
</div>

</body>
</html>
