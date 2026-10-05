<?php
/**
 * @var array $oldInput Previous input data (if any).
 * @var string|null $message Success message.
 * @var string|null $error Error message.
 */
$oldInput = $oldInput ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Wage Item</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; display: inline-block; margin-left: 5px; }
        .btn-submit { background-color: #28a745; }
        .btn-continue { background-color: #17a2b8; }
        .btn-cancel { background-color: #6c757d; }
        .btn-dashboard { background-color: #343a40; float: left; }
        #custom_allowance_group { display: none; margin-top: 10px; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    <script>
        // Define the valid keys for each authority
        const categoryKeys = {
            'Sports Wing': [
                'Stenographer',
                'Receptionist',
                'Computer Operator',
                'Clerk',
                'Petro For Dak Rider'
            ],
            'Central Govt': [
                'Graduate and Above',
                'Skilled',
                'Matriculate',
                'Semi-Skilled',
                'Un-Skilled',
                'Security Guards (Without Arms)'
            ]
        };

        function updateFormFields(preSelectedKey = null) {
            const authoritySelect = document.getElementById('authority');
            const categorySelect = document.getElementById('json_key');
            const selectedAuthority = authoritySelect.value;

            // 1. Update Mapped Category Dropdown
            categorySelect.innerHTML = '<option value="">Select Category</option>'; // Reset

            if (selectedAuthority && categoryKeys[selectedAuthority]) {
                categoryKeys[selectedAuthority].forEach(key => {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = key;
                    if (preSelectedKey && preSelectedKey === key) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
            }
        }

        function toggleAllowanceField() {
            const allowanceSelect = document.getElementById('allowance_type');
            const customGroup = document.getElementById('custom_allowance_group');
            const fixedInput = document.getElementById('fixed_allowance');

            if (allowanceSelect.value === 'custom') {
                customGroup.style.display = 'block';
                fixedInput.required = true;
            } else {
                customGroup.style.display = 'none';
                fixedInput.value = '0.00';
                fixedInput.required = false;
            }
        }

        // Initialize form on load if data exists
        document.addEventListener('DOMContentLoaded', function() {
            const oldAuthority = "<?= htmlspecialchars($oldInput['authority'] ?? '') ?>";
            const oldKey = "<?= htmlspecialchars($oldInput['json_key'] ?? '') ?>";
            const oldAllowance = parseFloat("<?= htmlspecialchars($oldInput['fixed_allowance'] ?? '0') ?>");

            if (oldAuthority) {
                updateFormFields(oldKey);
            }

            if (oldAllowance > 0) {
                document.getElementById('allowance_type').value = 'custom';
                toggleAllowanceField();
                document.getElementById('fixed_allowance').value = oldAllowance;
            }
        });
    </script>
</head>
<body>

<div class="container">
    <h1>Create New Wage Item</h1>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label for="authority">Authority</label>
            <select id="authority" name="authority" onchange="updateFormFields()" required>
                <option value="">Select Authority</option>
                <option value="Sports Wing" <?= (isset($oldInput['authority']) && $oldInput['authority'] === 'Sports Wing') ? 'selected' : '' ?>>Sports Wing</option>
                <option value="Central Govt" <?= (isset($oldInput['authority']) && $oldInput['authority'] === 'Central Govt') ? 'selected' : '' ?>>Central Govt</option>
            </select>
        </div>

        <div class="form-group">
            <label for="item_name">Item Name (Designation)</label>
            <input type="text" id="item_name" name="item_name" value="<?= htmlspecialchars($oldInput['item_name'] ?? '') ?>" required placeholder="e.g., Data Entry Operator">
        </div>

        <div class="form-group">
            <label for="json_key">Mapped Category (JSON Key)</label>
            <select id="json_key" name="json_key" required>
                <option value="">Select Authority First</option>
            </select>
            <small>Select the category from the Wage Order that determines the base rate.</small>
        </div>

        <div class="form-group">
            <label for="unit">Unit</label>
            <select id="unit" name="unit" required>
                <option value="Per Person Per Month" <?= (isset($oldInput['unit']) && $oldInput['unit'] === 'Per Person Per Month') ? 'selected' : '' ?>>Per Person Per Month</option>
                <option value="Per Person Per Day" <?= (isset($oldInput['unit']) && $oldInput['unit'] === 'Per Person Per Day') ? 'selected' : '' ?>>Per Person Per Day</option>
            </select>
        </div>

        <div class="form-group">
            <label for="allowance_type">Fixed Allowance</label>
            <select id="allowance_type" onchange="toggleAllowanceField()">
                <option value="none">None (0.00)</option>
                <option value="custom">Custom Fixed Amount</option>
            </select>
        </div>

        <div class="form-group" id="custom_allowance_group">
            <label for="fixed_allowance">Allowance Amount</label>
            <input type="number" step="0.01" id="fixed_allowance" name="fixed_allowance" value="<?= htmlspecialchars($oldInput['fixed_allowance'] ?? '0.00') ?>">
        </div>

        <div class="form-actions">
            <a href="/index.php" class="btn btn-dashboard">Back to Dashboard</a>
            <button type="submit" name="save_and_continue" class="btn btn-continue">Save & Continue</button>
            <button type="submit" class="btn btn-submit">Save & Exit</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
