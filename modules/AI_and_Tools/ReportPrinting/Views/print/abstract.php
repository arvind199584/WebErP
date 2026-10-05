<?php
/** @var array $data */
/** @var array $calcs */
?>
<div class="header" style="text-align: center;"> <!-- CENTERED TITLE -->
    <h1 class="underline"><?= htmlspecialchars($data['office_bill_no']) ?></h1>
</div>

<div class="work-details" style="margin-bottom: 20px;">
    <p><strong>Name of Work:</strong> <?= htmlspecialchars($data['name_of_work']) ?></p>
    <p><strong>Sub Head:</strong> <?= htmlspecialchars($data['sub_head']) ?></p>
    <p><strong>Agmt. No.:</strong> <?= htmlspecialchars($data['agreement_no']) ?></p>
    <p><strong>Agency:</strong> <?= htmlspecialchars($data['agency_name']) ?></p>
    <p><strong>R/A Bill:</strong> <?= htmlspecialchars($data['office_bill_no']) ?></p>
    <p><strong>Period:</strong> <?= date('d F Y', strtotime($data['period_from'])) ?> to <?= date('d F Y', strtotime($data['period_to'])) ?></p>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2">S. No.</th>
            <th rowspan="2">Description</th>
            <th rowspan="2">Period</th>
            <th rowspan="2">Qty</th>
            <th rowspan="2">Unit</th>
            <th rowspan="2">Rate</th>
            <th colspan="3">Amount (₹)</th>
        </tr>
        <tr>
            <th>Upto date</th>
            <th>Previous</th>
            <th>Since prev</th>
        </tr>
    </thead>
    <tbody>
        <?php $sno = 1; foreach ($data['bill_items'] as $item):
            $amount = $item['qty'] * $item['rate'];
        ?>
        <tr>
            <td><?= $sno++ ?></td>
            <td class="left-align"><?= htmlspecialchars($item['description']) ?></td>
            <td><?= date('d.m.y', strtotime($data['period_from'])) ?>-<?= date('d.m.y', strtotime($data['period_to'])) ?></td>
            <td><?= $item['qty'] ?></td>
            <td><?= $item['unit'] ?></td>
            <td class="right-align"><?= number_format((float)$item['rate'], 2) ?></td>
            <td class="right-align"><?= number_format($amount, 2) ?></td>
            <td class="right-align">0.00</td>
            <td class="right-align"><?= number_format($amount, 2) ?></td>
        </tr>
        <?php endforeach; ?>

        <tr class="bold">
            <td colspan="6" class="right-align">Total</td>
            <td class="right-align"><?= number_format($calcs['total_basic'], 2) ?></td>
            <td class="right-align">0.00</td>
            <td class="right-align"><?= number_format($calcs['total_basic'], 2) ?></td>
        </tr>
        <tr>
            <td colspan="6" class="right-align">R/o shortage of staff as per Absentee</td>
            <td class="right-align">(-) <?= number_format($calcs['shortage_amount'], 2) ?></td>
            <td></td>
            <td class="right-align">(-) <?= number_format($calcs['shortage_amount'], 2) ?></td>
        </tr>
        <tr class="bold">
            <td colspan="6" class="right-align">Total (A)</td>
            <td class="right-align"><?= number_format($calcs['total_a'], 2) ?></td>
            <td></td>
            <td class="right-align"><?= number_format($calcs['total_a'], 2) ?></td>
        </tr>
        <tr>
            <td colspan="6" class="right-align">Add Contractor Profit @<?= $data['service_charge_percent'] ?>% (B)</td>
            <td class="right-align"><?= number_format($calcs['profit_amount'], 2) ?></td>
            <td></td>
            <td class="right-align"><?= number_format($calcs['profit_amount'], 2) ?></td>
        </tr>
        <tr class="bold">
            <td colspan="6" class="right-align">Total (A+B)</td>
            <td class="right-align"><?= number_format($calcs['gross_amount'], 2) ?></td>
            <td></td>
            <td class="right-align"><?= number_format($calcs['gross_amount'], 2) ?></td>
        </tr>
        <tr class="bold">
            <td colspan="6" class="right-align">Gross amount of this bill</td>
            <td class="right-align"><?= number_format($calcs['gross_amount'], 2) ?></td>
            <td></td>
            <td class="right-align"><?= number_format($calcs['gross_amount'], 2) ?></td>
        </tr>
    </tbody>
</table>

<div style="display: flex; justify-content: space-between; margin-top: 20px;">
    <div style="width: 48%;">
        <p class="bold">Recoveries on ₹<?= number_format($calcs['gross_amount'], 0) ?></p>
        <table class="no-border" style="font-size: 10px;">
            <?php foreach ($calcs['recoveries'] as $name => $amount): ?>
            <tr><td class="left-align"><?= $name ?></td><td class="right-align">₹<?= number_format($amount, 0) ?></td></tr>
            <?php endforeach; ?>
            <tr class="bold" style="border-top: 1px solid black !important;"><td class="left-align">Total recoveries</td><td class="right-align">₹<?= number_format($calcs['total_recoveries'], 0) ?></td></tr>
            <tr class="bold"><td class="left-align">Net payable amount</td><td class="right-align">₹<?= number_format($calcs['net_amount'], 0) ?></td></tr>
        </table>
    </div>
    <div style="width: 48%;">
        <p class="bold">Details of Shortage of Staff</p>
        <table style="font-size: 10px;">
            <thead><tr><th>Staff</th><th>Abs</th><th>Rate</th><th>Amt</th></tr></thead>
            <tbody>
                <?php if (empty($calcs['shortage_details'])): ?>
                    <!-- NO SHORTAGE ROW -->
                    <tr><td colspan="4" style="text-align: center; padding: 10px;">No shortage of staff</td></tr>
                <?php else: ?>
                    <?php foreach ($calcs['shortage_details'] as $s): ?>
                    <tr><td class="left-align"><?= $s['designation'] ?></td><td><?= $s['absent_days'] ?></td><td><?= number_format($s['daily_rate'],0) ?></td><td><?= number_format($s['amount'],0) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 50px; display: flex; justify-content: flex-end;">
    <div style="text-align: center; width: 40%;">
        <p class="bold underline">RE(Civil)/<?= $data['office_code'] ?>, DDA</p>
    </div>
</div>
