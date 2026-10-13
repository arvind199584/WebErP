<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Voucher - <?php echo htmlspecialchars($voucher['voucher_no'] ?? ''); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .page {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: normal;
            color: #555;
        }
        .header h3 {
            margin: 0;
            font-size: 14px;
            color: #777;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            background: #fdfdfd;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 4px;
            font-size: 13px;
        }
        .meta-item {
            margin-bottom: 6px;
        }
        .meta-item strong {
            display: inline-block;
            width: 150px;
            color: #4a5568;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #cbd5e0;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: 600;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 20px;
        }
        .sig-block {
            text-align: center;
            width: 200px;
            border-top: 1px solid #718096;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        .no-print-bar {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 8px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-print:hover {
            background: #0b5ed7;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .page {
                box-shadow: none;
                border-radius: 0;
                padding: 10px;
                max-width: 100%;
            }
            .no-print-bar {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <button class="btn-print" onclick="window.print()">🖨️ Print Receipt Voucher</button>
</div>

<div class="page">
    <div class="header">
        <h1>DELHI DEVELOPMENT AUTHORITY</h1>
        <h2>OFFICE MANAGEMENT & TURF MAINTENANCE SYSTEM</h2>
        <h3>STORE RECEIPT VOUCHER (GOODS INWARD NOTE)</h3>
    </div>

    <div class="meta-grid">
        <div>
            <div class="meta-item">
                <strong>Voucher No:</strong> 
                <span style="font-weight: bold; color: #0d6efd;"><?php echo htmlspecialchars((string)($voucher['voucher_no'] ?? '')); ?></span>
            </div>
            <div class="meta-item">
                <strong>Voucher Date:</strong> 
                <?php echo htmlspecialchars((string)($voucher['voucher_date'] ?? '')); ?>
            </div>
            <div class="meta-item">
                <strong>Office / Unit:</strong> 
                <?php echo htmlspecialchars((string)($voucher['office_name'] ?? '')); ?>
            </div>
            <div class="meta-item">
                <strong>Store Division:</strong> 
                <?php echo strtoupper(htmlspecialchars((string)($voucher['store_category'] ?? 'General'))); ?>
            </div>
        </div>
        <div>
            <div class="meta-item">
                <strong>Received From:</strong> 
                <b><?php echo htmlspecialchars((string)($voucher['party'] ?? $voucher['agency_name'] ?? '-')); ?></b>
            </div>
            <div class="meta-item">
                <strong>Source Contract:</strong> 
                <?php 
                    $src = strtoupper((string)($voucher['source_type'] ?? 'MANUAL'));
                    $ref = (string)($voucher['source_ref_no'] ?? '');
                    echo htmlspecialchars($src . ($ref ? " ({$ref})" : ''));
                ?>
            </div>
            <div class="meta-item">
                <strong>Consignment Status:</strong> 
                <?php echo htmlspecialchars((string)($voucher['supply_status'] ?? 'COMPLETE')); ?>
            </div>
            <div class="meta-item">
                <strong>Entry Timestamp:</strong> 
                <?php echo htmlspecialchars((string)($voucher['created_at'] ?? '')); ?>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">S.No</th>
                <th>Item Description / Nomenclature</th>
                <th class="text-right" style="width: 100px;">Quantity</th>
                <th style="width: 80px;">Unit</th>
                <th class="text-right" style="width: 110px;">Rate (₹)</th>
                <th class="text-right" style="width: 120px;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $items = is_array($voucher['items'] ?? null) ? $voucher['items'] : [];
                $totalQty = 0;
                $totalAmount = 0;
                if (!empty($items)):
                    foreach ($items as $idx => $it):
                        $qty = (float)($it['quantity'] ?? 0);
                        $rate = (float)($it['rate'] ?? 0);
                        $amt = isset($it['amount']) ? (float)$it['amount'] : ($qty * $rate);
                        $totalQty += $qty;
                        $totalAmount += $amt;
            ?>
                <tr>
                    <td class="text-center"><?php echo $idx + 1; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars((string)($it['description'] ?? '')); ?></strong>
                        <?php if (!empty($it['remarks'])): ?>
                            <br><small style="color: #666; font-style: italic;"><?php echo htmlspecialchars($it['remarks']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-weight: bold;"><?php echo number_format($qty, 2); ?></td>
                    <td><?php echo htmlspecialchars((string)($it['unit'] ?? 'Units')); ?></td>
                    <td class="text-right"><?php echo $rate > 0 ? number_format($rate, 2) : '-'; ?></td>
                    <td class="text-right" style="font-weight: bold;"><?php echo $amt > 0 ? number_format($amt, 2) : '-'; ?></td>
                </tr>
            <?php 
                    endforeach;
                else:
            ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #999; padding: 20px;">No items entered.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f7fafc; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAL:</td>
                <td class="text-right"><?php echo number_format($totalQty, 2); ?></td>
                <td></td>
                <td></td>
                <td class="text-right" style="color: #2b6cb0;">₹<?php echo number_format($totalAmount, 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if (!empty($voucher['remarks'])): ?>
        <div style="font-size: 12px; margin-bottom: 25px; background: #fffaf0; border: 1px solid #feebc8; padding: 10px; border-radius: 4px;">
            <strong>Remarks / Verification Notes:</strong> <?php echo htmlspecialchars($voucher['remarks']); ?>
        </div>
    <?php endif; ?>

    <div class="signatures">
        <div class="sig-block">
            Received By<br>
            <span style="font-size: 11px; font-weight: normal; color: #666;">Store Keeper / In-charge</span>
        </div>
        <div class="sig-block">
            Inspected & Verified By<br>
            <span style="font-size: 11px; font-weight: normal; color: #666;">Section Officer / Supervisor</span>
        </div>
        <div class="sig-block">
            Passed By<br>
            <span style="font-size: 11px; font-weight: normal; color: #666;">Assistant Engineer / Unit Head</span>
        </div>
    </div>
</div>

</body>
</html>
