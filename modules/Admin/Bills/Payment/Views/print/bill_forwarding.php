<?php
/** @var array $data */
/** @var array $calcs */
?>
<!-- PAGE 1 (Page 3 of Doc) -->
<div class="page page-one page-break">
    <div>
        <div class="header">
            <h1>BILL FORWARDING MEMO</h1>
        </div>

        <table class="memo-table">
            <tr>
                <td class="serial">1.</td>
                <td class="label">Name of Work/Scheme</td>
                <td class="value"><span class="bold"><?= htmlspecialchars($data['name_of_work']) ?></span></td>
            </tr>
            <tr>
                <td class="serial">2.</td>
                <td class="label">SH:</td>
                <td class="value"><span class="bold"><?= htmlspecialchars($data['sub_head']) ?></span></td>
            </tr>
            <tr>
                <td class="serial">3.</td>
                <td class="label">Agency</td>
                <td class="value"><span class="bold"><?= htmlspecialchars($data['agency_name']) ?></span></td>
            </tr>
            <tr>
                <td class="serial">4.</td>
                <td class="label">Budget Code/Item No.</td>
                <td class="value"><span class="bold"><?= htmlspecialchars($data['budget_code']) ?></span></td>
            </tr>
            <tr>
                <td class="serial">5.</td>
                <td class="label">Pan No.</td>
                <td class="value"><span class="bold"><?= htmlspecialchars($data['pan_no']) ?></span></td>
            </tr>
            <tr>
                <td class="serial">6.</td>
                <td class="label">GSTIN No.</td>
                <td class="value"><span class="bold"><?= htmlspecialchars($data['gst_no']) ?></span></td>
            </tr>
            <tr>
                <td class="serial">7.</td>
                <td class="label">Tendered Amount / Amount of work done:</td>
                <td class="value"><span class="bold">Rs. <?= number_format((float)$data['tendered_amount'], 0) ?>/-</span></td>
            </tr>
            <tr>
                <td></td>
                <td class="label">a. Total Work done</td>
                <td class="value"><span class="bold">Rs. <?= number_format($calcs['gross_amount'], 0) ?>/-</span></td>
            </tr>
            <tr>
                <td></td>
                <td class="label">b. Reimbursement of GST</td>
                <td class="value"></td>
            </tr>
            <tr>
                <td class="serial">8.</td>
                <td class="label" colspan="2">Reference of AA/ES (AR & MO) in case on Maintenance work:</td>
            </tr>
            <tr>
                <td class="serial">9.</td>
                <td class="label" colspan="2">
                    Proportionate component available:<br>
                    To approved P.E. Separately for<br>
                    Civil/Elect./Hort. Work (Group Wise)
                </td>
            </tr>
            <tr>
                <td class="serial">10</td>
                <td class="label">
                    Progressive expdr. Of the scheme :<br>
                    Against the provision indicated<br>
                    Under Col. 6 upto the previous month
                </td>
                <td class="value"><span class="bold">TODO: Calc Progressive</span></td>
            </tr>
            <tr>
                <td class="serial">11</td>
                <td class="label" colspan="2">
                    Reference to the approval of the deviation if any<br>
                    a. No.<br>
                    b. Amount : NA<br>
                    c. Date
                </td>
            </tr>
            <tr>
                <td class="serial">12</td>
                <td class="label" colspan="2">
                    a) Budget provision during the year : Rs. <?= number_format((float)$data['provision'], 2) ?> Lacs<br>
                    b) Less departmental charges<br>
                    c) Net provision for the division
                </td>
            </tr>
            <tr>
                <td class="serial">13.</td>
                <td class="label">Expenditure during the year</td>
                <td class="value"><span class="bold">TODO: Calc Yearly</span></td>
            </tr>
            <tr>
                <td class="serial">14</td>
                <td class="label" colspan="2">
                    Ref. of the Budget Slip<br>
                    a. No. & date<br>
                    b. Amount
                </td>
            </tr>
            <tr>
                <td class="serial">15</td>
                <td class="label">Amount of bill chargeable to work</td>
                <td class="value"><span class="bold">Rs. <?= number_format($calcs['gross_amount'], 0) ?>/-</span></td>
            </tr>
            <tr>
                <td class="serial">16</td>
                <td class="label">Net amount payable</td>
                <td class="value"><span class="bold">Rs. <?= number_format($calcs['net_amount'], 0) ?>/-</span></td>
            </tr>
            <tr>
                <td class="serial">17</td>
                <td class="label" colspan="2">The maintenance estimate is approved and work is technically sanctioned.</td>
            </tr>
            <tr>
                <td class="serial">18</td>
                <td class="label" colspan="2">The works against which the bills have been forwarded above are actually executed at site</td>
            </tr>
            <tr>
                <td class="serial">19</td>
                <td class="label" colspan="2">In accordance of the enclosed bill, the account details of beneficiary are given below: -</td>
            </tr>
            <tr>
                <td class="serial">20</td>
                <td class="label">Bank Details</td>
                <td class="value">
                    <table class="bank-table" style="width: 100%; border: none;">
                        <tr>
                            <td style="width: 30%;">Name of the payee</td>
                            <td style="width: 5%;">:</td>
                            <td style="width: 65%;"><span class="bold"><?= htmlspecialchars($data['agency_name']) ?></span></td>
                        </tr>
                        <tr>
                            <td>Account Number</td>
                            <td>:</td>
                            <td><span class="bold"><?= htmlspecialchars($data['account_no']) ?></span></td>
                        </tr>
                        <tr>
                            <td>IFSC Code</td>
                            <td>:</td>
                            <td><span class="bold"><?= htmlspecialchars($data['ifsc']) ?></span></td>
                        </tr>
                        <tr>
                            <td>Bank Name</td>
                            <td>:</td>
                            <td><span class="bold"><?= htmlspecialchars($data['bank_name']) ?></span></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Page 3 Signature: Forced Right Alignment -->
    <div style="display: flex; justify-content: flex-end; margin-top: 60px;"> <!-- Pushed down slightly -->
        <div style="text-align: center; width: 200px;">
            <span class="bold" style="text-decoration: underline;">AAO (<?= htmlspecialchars($data['office_code']) ?>), DDA</span>
            <div style="margin-top: 40px;"></div>
        </div>
    </div>
</div>

<!-- PAGE 2 (Page 4 of Doc) -->
<div class="page">
    <div class="header">
        <h1>BILL FORWARDING MEMO - CERTIFICATIONS</h1>
    </div>

    <div class="page-two-content">
        <div class="certification-title">To be certified by AAO</div>
        <div class="paragraph-content">
            <p>(I) Proposed release of payment is covered under sanctioned AA & ES / ARMO & Budget provision of the relevant year.</p>
            <p>(II) Per case calling and annual ceiling of Secy. (Sports) has been adhered to bills/ vouchers/receipts etc furnished by agency are also duly complaint of all orders / guidelines of Govt. of India w.r.t. GST/Income Tax and all other statutory provisions.</p>
        </div>

        <div style="margin-top: 40px;"></div>

        <!-- Page 4 AAO Signature: Forced Right Alignment -->
        <div style="display: flex; justify-content: flex-end; margin-top: 60px;"> <!-- Pushed down slightly -->
            <div style="text-align: center; width: 200px;">
                <span class="bold" style="text-decoration: underline;">AAO (<?= htmlspecialchars($data['office_code']) ?>), DDA</span>
                <div style="margin-top: 40px;"></div>
            </div>
        </div>

        <div class="certification-title">To be Certified by Secy.</div>
        <div class="paragraph-content">
            <p>(i) Works / supply has already been executed / made at site against which payment is being proposed for release.</p>
            <p>(ii) Payment is within the scope of agreement Deviation if any, is duly sanctioned by Competent Authority.</p>
            <p>(iii) With reference to enhanced financial powers of Secy. Of respective SC/GCs DDA vide order dated 01.05.2019. It is certified that work has been not been split up with a view to keep the same within delegated Powers.</p>
        </div>

        <div style="margin-top: 40px;"></div>

        <!-- Page 4 Secretary Signature: Forced Right Alignment -->
        <div style="display: flex; justify-content: flex-end; margin-top: 60px;"> <!-- Pushed down slightly -->
            <div style="text-align: center; width: 200px;">
                <span class="bold" style="text-decoration: underline;">Secretary (<?= htmlspecialchars($data['office_code']) ?>), DDA</span>
                <div style="margin-top: 40px;"></div>
            </div>
        </div>
    </div>
</div>
