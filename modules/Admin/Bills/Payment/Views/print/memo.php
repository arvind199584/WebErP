<?php
/** @var array $data */
/** @var array $calcs */
/** @var string $netWords */
?>
<div class="document-container">
    <div class="content-section">
        <p class="bold compact-text">
            Name of work: - <?= htmlspecialchars($data['name_of_work']) ?>
        </p>

        <div class="section-divider"></div>

        <p class="compact-text">
            Sub Head: - <?= htmlspecialchars($data['sub_head']) ?>
        </p>

        <p class="bold compact-text">
            Agency: - <?= htmlspecialchars($data['agency_name']) ?>
        </p>

        <div class="section-divider"></div>

        <p class="compact-text">
            <?= htmlspecialchars($data['office_bill_no']) ?> in respect of <?= htmlspecialchars($data['agency_name']) ?> has been passed for
            <span class="amount-highlight">Rs. <?= number_format($calcs['gross_amount'], 0) ?>/-</span> & pay
            <span class="amount-highlight">Rs. <?= number_format($calcs['net_amount'], 0) ?>/- (Rupees <?= htmlspecialchars($netWords) ?> Only)</span>
            in favour of <?= htmlspecialchars($data['agency_name']) ?>.
        </p>

        <p class="bold compact-text">Details of payment as under: -</p>

        <div class="highlight-box">
            <table class="payment-table">
                <tr>
                    <td class="fixed-label">Gross Amount</td>
                    <td class="dynamic-content">
                        <div class="dynamic-field highlight" id="gross-amount">Rs. <?= number_format($calcs['gross_amount'], 0) ?>/-</div>
                    </td>
                </tr>
                <?php foreach ($calcs['recoveries'] as $name => $amount): ?>
                <tr>
                    <td class="fixed-label">Less <?= htmlspecialchars($name) ?></td>
                    <td class="dynamic-content">
                        <div class="dynamic-field">Rs. <?= number_format($amount, 0) ?>/-</div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="fixed-label amount-highlight">Net Amount</td>
                    <td class="dynamic-content">
                        <div class="dynamic-field highlight amount-highlight" id="net-amount">Rs. <?= number_format($calcs['net_amount'], 0) ?>/-</div>
                    </td>
                </tr>
            </table>
        </div>

        <p class="compact-text">
            In view of above bill is submitted for authorize of payment
            <span class="amount-highlight">Rs. <?= number_format($calcs['net_amount'], 0) ?>/- (Rupees <?= htmlspecialchars($netWords) ?> Only)</span>
            in favour of <?= htmlspecialchars($data['agency_name']) ?> through NEFT/RTGS.
        </p>
    </div>

    <div class="signature-section">
        <div class="signature-left">
            <div class="signature-line" style="margin-bottom: 60px;">AAO/<?= htmlspecialchars($data['office_code']) ?></div>
            <div class="signature-line" style="margin-bottom: 60px;">Secretary/<?= htmlspecialchars($data['office_code']) ?></div>
            <div class="signature-line">Sr. A.O. CAU (Sports)</div>
        </div>
        <div class="signature-right">
            <div class="signature-line">RE(Civil)/<?= htmlspecialchars($data['office_code']) ?></div>
        </div>
    </div>
</div>
