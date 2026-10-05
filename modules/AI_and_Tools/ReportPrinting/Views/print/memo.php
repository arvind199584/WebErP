<?php
/** @var array $data */
/** @var array $calcs */
/** @var string $netWords */
?>
<div class="content-section">
    <p class="bold">Name of work: - <?= htmlspecialchars($data['name_of_work']) ?></p>
    <p>Sub Head: - <?= htmlspecialchars($data['sub_head']) ?></p>
    <p class="bold">Agency: - <?= htmlspecialchars($data['agency_name']) ?></p>

    <div style="margin: 20px 0;">
        <p><?= htmlspecialchars($data['office_bill_no']) ?> in respect of <?= htmlspecialchars($data['agency_name']) ?> has been passed for
        <span class="bold">Rs. <?= number_format($calcs['gross_amount'], 0) ?>/-</span> & pay
        <span class="bold">Rs. <?= number_format($calcs['net_amount'], 0) ?>/- (Rupees <?= $netWords ?> Only)</span>
        in favour of <?= htmlspecialchars($data['agency_name']) ?>.</p>
    </div>

    <p class="bold">Details of payment as under: -</p>
    <table style="width: 60%; margin: 15px 0;" class="no-border">
        <tr><td class="left-align bold">Gross Amount</td><td class="right-align">Rs. <?= number_format($calcs['gross_amount'], 0) ?>/-</td></tr>
        <?php foreach ($calcs['recoveries'] as $name => $amount): ?>
        <tr><td class="left-align">Less <?= $name ?></td><td class="right-align">Rs. <?= number_format($amount, 0) ?>/-</td></tr>
        <?php endforeach; ?>
        <tr class="bold" style="border-top: 1px solid black !important;"><td class="left-align">Net Amount</td><td class="right-align">Rs. <?= number_format($calcs['net_amount'], 0) ?>/-</td></tr>
    </table>

    <p>In view of above bill is submitted for authorize of payment <span class="bold">Rs. <?= number_format($calcs['net_amount'], 0) ?>/-</span> in favour of <?= htmlspecialchars($data['agency_name']) ?> through NEFT/RTGS.</p>
</div>

<!-- Signature Block: Adjusted Spacing -->
<div style="margin-top: 1in; display: flex; justify-content: space-between;">
    <div style="text-align: left; width: 40%;">
        <!-- AAO: Base position -->
        <br>
        <p class="bold underline" style="margin-bottom: 20pt;">AAO/<?= $data['office_code'] ?></p>
        <br>
        <br>

        <!-- Secretary: 40 points below AAO (20pt margin + 20pt gap) -->
        <p class="bold underline" style="margin-bottom: 40pt;">Secretary/<?= $data['office_code'] ?></p>
        <br>

        <!-- Sr. AO: 60 points below Secretary -->
        <p class="bold underline">Sr. A.O. CAU (Sports)</p>
    </div>
    <div style="text-align: right; width: 40%;">
        <br>
        <br>
        <p class="bold underline">RE(Civil)/<?= $data['office_code'] ?></p>
    </div>
</div>
