<?php
/** @var array $agreements */
/** @var array $work_orders */
/** @var array $supply_orders */
/** @var array $data */

// DEBUG: See what's currently in session
// echo "<pre>"; print_r($_SESSION['payment_create_data'] ?? []); echo "</pre>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Bill - Step 1: Details</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .hidden { display: none; }
        .error { padding: 15px; background-color: #dc3545; color: white; border-radius: 5px; margin-bottom: 20px; }
    </style>
    <script>
        function handleReferenceTypeChange() {
            const type = document.getElementById('reference_type').value;
            const periodFields = document.getElementById('bill_period_fields');

            // Hide all reference groups and disable their selects
            document.querySelectorAll('.reference-group').forEach(group => {
                group.classList.add('hidden');
                const select = group.querySelector('select');
                if (select) select.disabled = true;
            });

            if (type) {
                const activeGroup = document.getElementById(`${type}_group`);
                if (activeGroup) {
                    activeGroup.classList.remove('hidden');
                    const select = activeGroup.querySelector('select');
                    if (select) select.disabled = false;
                }

                // Show period fields for Agreements
                if (type === 'agreement') {
                    periodFields.classList.remove('hidden');
                    periodFields.querySelectorAll('input').forEach(el => el.disabled = false);
                } else {
                    periodFields.classList.add('hidden');
                    periodFields.querySelectorAll('input').forEach(el => el.disabled = true);
                }
            } else {
                periodFields.classList.add('hidden');
            }
        }

        function handleBillTypeChange() {
            const billType = document.getElementById('bill_type').value;
            const complianceFields = document.getElementById('compliance_fields');
            const nonComplianceFields = document.getElementById('non_compliance_fields');

            if (billType === 'Compliance') {
                complianceFields.classList.remove('hidden');
                complianceFields.querySelector('select').disabled = false;
                nonComplianceFields.classList.add('hidden');
                nonComplianceFields.querySelectorAll('select, input').forEach(el => el.disabled = true);
            } else {
                complianceFields.classList.add('hidden');
                complianceFields.querySelector('select').disabled = true;
                nonComplianceFields.classList.remove('hidden');
                document.getElementById('reference_type').disabled = false;
                handleReferenceTypeChange();
            }
        }

        document.addEventListener('DOMContentLoaded', handleBillTypeChange);
    </script>
</head>
<body>
<div class="container">
    <h1>Step 1: Bill Identification</h1>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="1">

        <div class="form-grid">
            <div class="form-group"><label>Agency Bill No:</label><input type="text" name="agency_bill_no"></div>
            <div class="form-group"><label>Bill Date:</label><input type="date" name="bill_date" required></div>
            <div class="form-group full-width"><label>Office Bill No:</label><input type="text" name="office_bill_no"></div>
            <div class="form-group full-width"><label>Bill Type:</label>
                <select id="bill_type" name="bill_type" onchange="handleBillTypeChange()">
                    <option value="Item">Item Bill</option>
                    <option value="Compliance">Compliance Bill</option>
                    <option value="GST">GST Reimbursement</option>
                    <option value="ESIC">ESIC Reimbursement</option>
                    <option value="EPF">EPF Reimbursement</option>
                </select>
            </div>
        </div>

        <!-- Compliance Section -->
        <div id="compliance_fields" class="form-group full-width hidden">
            <label>Budget:</label>
            <select name="budget_id">
                <option value="1">Budget 1 (Placeholder)</option>
            </select>
        </div>

        <!-- Non-Compliance Section -->
        <div id="non_compliance_fields" class="full-width hidden">
            <div class="form-group full-width">
                <label>Reference Type:</label>
                <select id="reference_type" name="reference_type" onchange="handleReferenceTypeChange()">
                    <option value="">-- Select Reference --</option>
                    <option value="agreement">Agreement</option>
                    <option value="work_order">Work Order</option>
                    <option value="supply_order">Supply Order</option>
                </select>
            </div>

            <div id="agreement_group" class="form-group full-width reference-group hidden">
                <label>Select Agreement:</label>
                <select id="agreement_select" name="agreement_id">
                    <option value="">-- Select --</option>
                    <?php foreach($agreements as $ag): ?>
                        <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['agreement_no']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="work_order_group" class="form-group full-width reference-group hidden">
                <label>Select Work Order:</label>
                <select name="work_order_id"><option value="">Placeholder</option></select>
            </div>

            <div id="supply_order_group" class="form-group full-width reference-group hidden">
                <label>Select Supply Order:</label>
                <select name="supply_order_id"><option value="">Placeholder</option></select>
            </div>

            <div id="bill_period_fields" class="form-grid full-width hidden">
                <div class="form-group"><label>Bill Period (From):</label><input type="date" name="period_from"></div>
                <div class="form-group"><label>Bill Period (To):</label><input type="date" name="period_to"></div>
            </div>
        </div>

        <hr>
        <div style="text-align: right;">
            <button type="submit" class="btn">Next: Financials &rarr;</button>
        </div>
    </form>
</div>
</body>
</html>
