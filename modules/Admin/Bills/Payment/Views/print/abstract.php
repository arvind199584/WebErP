<?php
/** @var array $data */
/** @var array $calcs */
?>
<div class="page-content"> <!-- Added a wrapper for content -->
    <div class="header">
        <h1><?= htmlspecialchars($data['office_bill_no']) ?></h1>
    </div>

    <div class="work-details">
        <p><strong>Name of Work:</strong> <?= htmlspecialchars($data['name_of_work']) ?></p>
        <p><strong>Sub Head:</strong> <?= htmlspecialchars($data['sub_head']) ?></p>
        <p><strong>Agmt. No.:</strong> <?= htmlspecialchars($data['agreement_no']) ?></p>
        <p><strong>Agency:</strong> <?= htmlspecialchars($data['agency_name']) ?></p>
        <p><strong>R/A Bill:</strong> <?= htmlspecialchars($data['office_bill_no']) ?></p>
        <p><strong>Period:</strong> <?= date('d F Y', strtotime($data['period_from'])) ?> to <?= date('d F Y', strtotime($data['period_to'])) ?></p>
    </div>

    <table class="bill-table">
        <thead>
            <tr>
                <th rowspan="2">S. No.</th>
                <th rowspan="2" style="min-width: 80px;">Description</th>
                <th rowspan="2" style="min-width: 60px;">Period</th>
                <th rowspan="2">Qty</th>
                <th rowspan="2" style="min-width: 50px;">Unit</th>
                <th rowspan="2">Rate</th>
                <th colspan="3">Amount (₹)</th>
            </tr>
            <tr>
                <th>Upto date</th>
                <th>Gross Previous</th>
                <th>Since previous</th>
            </tr>
        </thead>
        <tbody>
            <?php $sno = 1; foreach ($data['bill_items'] as $item):
                $amount = $item['qty'] * $item['rate'];
            ?>
            <tr>
                <td><?= $sno++ ?></td>
                <td class="left-align"><?= htmlspecialchars($item['description']) ?></td>
                <td><?= date('d.m.Y', strtotime($data['period_from'])) ?> to <?= date('d.m.Y', strtotime($data['period_to'])) ?></td>
                <td><?= htmlspecialchars($item['qty']) ?></td>
                <td><?= htmlspecialchars($item['unit']) ?></td>
                <td class="right-align"><?= number_format((float)$item['rate'], 2) ?></td>
                <td class="right-align"><?= number_format($amount, 2) ?></td>
                <td class="right-align">0</td>
                <td class="right-align"><?= number_format($amount, 2) ?></td>
            </tr>
            <?php endforeach; ?>

            <tr class="bold">
                <td colspan="6" class="right-align">Total</td>
                <td class="right-align"><?= number_format($calcs['total_basic'], 2) ?></td>
                <td class="right-align">0</td>
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
            <tr class="bold">
                <td colspan="6" class="right-align">Say's amount</td>
                <td class="right-align"><?= number_format(round($calcs['gross_amount']), 2) ?></td>
                <td></td>
                <td class="right-align"><?= number_format(round($calcs['gross_amount']), 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="two-column">
        <div class="column">
            <div class="calculation-section">
                <h3>Recoveries on ₹<?= number_format($calcs['gross_amount'], 0) ?></h3>
                <?php foreach ($calcs['recoveries'] as $name => $amount): ?>
                <div class="calculation-row">
                    <span><?= htmlspecialchars($name) ?></span>
                    <span class="bold">₹<?= number_format($amount, 0) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="calculation-row" style="border-top: 1px solid #333; padding-top: 3px;">
                    <span class="bold">Total recoveries</span>
                    <span class="bold">₹<?= number_format($calcs['total_recoveries'], 0) ?></span>
                </div>
                <div class="calculation-row" style="border-top: 1px solid #333; padding-top: 3px;">
                    <span class="bold">Net payable amount</span>
                    <span class="bold">₹<?= number_format($calcs['net_amount'], 0) ?></span>
                </div>
            </div>
        </div>

        <div class="column">
            <div class="compact-section">
                <h3>Details of Shortage of Staff</h3>
                <table class="deduction-table">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Staff</th>
                            <th>Absentee</th>
                            <th>Deduction/day</th>
                            <th>Amt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($calcs['shortage_details'])): ?>
                            <tr><td colspan="5">No Shortage of Staff</td></tr>
                        <?php else: ?>
                            <?php $sno=1; foreach ($calcs['shortage_details'] as $row): ?>
                            <tr>
                                <td><?= $sno++ ?></td>
                                <td><?= htmlspecialchars($row['designation']) ?></td>
                                <td><?= $row['absent_days'] ?></td>
                                <td><?= number_format($row['daily_rate'], 2) ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bold">
                                <td colspan="4" class="right-align">Total deduction</td>
                                <td><?= number_format($calcs['shortage_amount'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- REMOVED SPACER DIV -->

    <div class="signature-area">
        <div class="signature-box">
            <p>Secy, <?= htmlspecialchars($data['office_code']) ?></p>
        </div>
        <div class="signature-box">
            <p>RE(Civil)/<?= htmlspecialchars($data['office_code']) ?>, DDA</p>
        </div>
    </div>
</div>
