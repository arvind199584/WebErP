<?php
/** @var array $budgets */
/** @var array $offices */
/** @var bool $isSuperUser */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Hand Receipt</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .row { display: flex; gap: 15px; }
        .row > div { flex: 1; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .checkbox-group input { width: auto; }
        .required-label::after { content: " *"; color: red; }
        .hidden { display: none; }
        select:disabled { background-color: #e9ecef; cursor: not-allowed; }
    </style>
    <script>
        function calculateNet() {
            const gross = parseFloat(document.getElementById('gross_amount').value) || 0;
            const tds = parseFloat(document.getElementById('tds_amount').value) || 0;
            document.getElementById('net_amount').value = (gross - tds).toFixed(2);
        }

        function toggleBudgetRequirement() {
            const hasImplication = document.getElementById('budget_implication').checked;
            const budgetSelect = document.getElementById('budgetid');
            const budgetLabel = document.getElementById('budget_label');

            if (hasImplication) {
                budgetSelect.disabled = false;
                budgetSelect.required = true;
                budgetLabel.classList.add('required-label');
            } else {
                budgetSelect.disabled = true;
                budgetSelect.required = false;
                budgetSelect.value = ""; // Clear selection
                budgetLabel.classList.remove('required-label');
            }
        }

        function handleCategoryChange(val) {
            const customInput = document.getElementById('custom_category_container');
            const customField = document.getElementById('custom_category');
            if (val === 'Custom') {
                customInput.classList.remove('hidden');
                customField.required = true;
            } else {
                customInput.classList.add('hidden');
                customField.required = false;
            }
        }

        async function loadBudgets(officeId) {
            const budgetSelect = document.getElementById('budgetid');
            budgetSelect.innerHTML = '<option value="">-- Loading... --</option>';
            if (!officeId) { budgetSelect.innerHTML = '<option value="">-- Select Office First --</option>'; return; }
            try {
                const response = await fetch(`?action=getBudgetsAjax&office_id=${officeId}`);
                const budgets = await response.json();
                budgetSelect.innerHTML = '<option value="">-- Select Budget --</option>';
                budgets.forEach(b => {
                    const option = document.createElement('option');
                    option.value = b.id;
                    option.textContent = `${b.code} - ${b.name_of_work}`;
                    budgetSelect.appendChild(option);
                });
                // Re-enforce disabled state if checkbox is unchecked
                toggleBudgetRequirement();
            } catch (error) {
                budgetSelect.innerHTML = '<option value="">-- Error loading budgets --</option>';
            }
        }

        document.addEventListener('DOMContentLoaded', toggleBudgetRequirement);
    </script>
</head>
<body>
<div class="container">
    <h1>New Hand Receipt</h1>
    <form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>

        <?php if ($isSuperUser): ?>
        <div class="form-group">
            <label class="required-label">Target Office:</label>
            <select name="officeid" id="officeid" onchange="loadBudgets(this.value)" required>
                <option value="">-- Select Office --</option>
                <?php foreach ($offices as $o): ?>
                    <option value="<?= $o['officeid'] ?>"><?= htmlspecialchars($o['officename']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group checkbox-group">
            <input type="checkbox" name="budget_implication" id="budget_implication" onchange="toggleBudgetRequirement()">
            <label for="budget_implication">Does this deplete the budget?</label>
        </div>

        <div class="form-group">
            <label id="budget_label">Budget Head:</label>
            <select name="budgetid" id="budgetid" disabled>
                <option value="">-- Select Budget --</option>
                <?php foreach ($budgets as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['code']) ?> - <?= htmlspecialchars($b['name_of_work']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="required-label">Category:</label>
            <select name="category_select" id="category_select" onchange="handleCategoryChange(this.value)" required>
                <option value="Security Deposit Release">Security Deposit Release</option>
                <option value="Earnest Money Release">Earnest Money Release</option>
                <option value="Misc Transportation Charges">Misc Transportation Charges</option>
                <option value="Honorarium to Consultants">Honorarium to Consultants</option>
                <option value="Custom">-- Custom / Other --</option>
            </select>
        </div>

        <div id="custom_category_container" class="form-group hidden">
            <label class="required-label">Enter Custom Category:</label>
            <input type="text" name="custom_category" id="custom_category" placeholder="e.g., Legal Fees, Printing Charges">
        </div>

        <div class="form-group">
            <label class="required-label">Description:</label>
            <textarea name="description" rows="3" required></textarea>
        </div>

        <div class="row">
            <div class="form-group">
                <label class="required-label">Gross Amount (₹):</label>
                <input type="number" step="0.01" name="gross_amount" id="gross_amount" oninput="calculateNet()" required>
            </div>
            <div class="form-group">
                <label>TDS (₹):</label>
                <input type="number" step="0.01" name="tds_amount" id="tds_amount" oninput="calculateNet()" value="0.00">
            </div>
        </div>

        <div class="form-group">
            <label>Net Amount (₹):</label>
            <input type="number" step="0.01" name="net_amount" id="net_amount" readonly>
        </div>

        <button type="submit" class="btn">Create Receipt</button>
    </form>
</div>
</body>
</html>
