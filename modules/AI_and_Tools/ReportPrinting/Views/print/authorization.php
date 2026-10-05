<?php
/** @var array $data */
/** @var array $calcs */
/** @var string $netWords */
?>
<div class="page">
    <div class="content-wrapper">
        <p>NOW: <?= htmlspecialchars($data['name_of_work']) ?><br>
        SH:&nbsp; <?= htmlspecialchars($data['sub_head']) ?>.</p>
        <p> <?= htmlspecialchars($data['office_bill_no']) ?> bill in respect of <?= htmlspecialchars($data['agency_name']) ?> has been passed for ₹
        <?= number_format($calcs['gross_amount'], 0) ?>/- and payment of ₹ <?= number_format($calcs['net_amount'], 0) ?>/- (Rupees
        <?= htmlspecialchars($netWords) ?> only). </p>
        <p> The details are as under: - </p>

        <table style="width: 100%; border-collapse: collapse;" border="1">
            <tbody>
                <tr>
                    <td style="width: 50%;"><b>Gross Amount</b></td>
                    <td style="text-align: right;"><b>₹ <?= number_format($calcs['gross_amount'], 0) ?></b></td>
                </tr>
                <tr>
                    <td><b>Recovery</b></td>
                    <td></td>
                </tr>
                <?php foreach ($calcs['recoveries'] as $name => $amount): ?>
                <tr>
                    <td style="padding-left: 20px;"><?= htmlspecialchars($name) ?></td>
                    <td style="text-align: right;">₹ <?= number_format($amount, 0) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><b>Total Recovery</b></td>
                    <td style="text-align: right;"><b>₹ <?= number_format($calcs['total_recoveries'], 0) ?></b></td>
                </tr>
                <tr>
                    <td><b>Net Amount</b></td>
                    <td style="text-align: right;"><b>₹ <?= number_format($calcs['net_amount'], 0) ?></b></td>
                </tr>
            </tbody>
        </table>

        <p>In view of the above, bill is submitted for authorization of payment for
        <span style="font-weight:bold;">₹ <?= number_format($calcs['net_amount'], 0) ?>/- (Rupees <?= htmlspecialchars($netWords) ?> only)</span>
        to <span style="font-weight:bold;"><?= htmlspecialchars($data['agency_name']) ?></span> by RTGS/NEFT (If agreed). AA &amp; ES has been approved by the Competent Authority. (Copy Enclosed).
        </p>

        <div style="margin-top: auto; padding-top: 50px; display: flex; justify-content: space-between;">
            <div style="text-align: center;">
                <p style="font-weight: bold; text-decoration: underline;">Sr. A.O. (Sports)</p>
            </div>
            <div style="text-align: center;">
                <p style="font-weight: bold; text-decoration: underline;">Secretary, <?= htmlspecialchars($data['office_code']) ?></p>
            </div>
        </div>
    </div>
</div>
