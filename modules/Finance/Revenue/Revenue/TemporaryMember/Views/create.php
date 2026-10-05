<?php
$isEdit = $isEdit ?? false;
$member = $member ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $isEdit ? 'Edit' : 'Create' ?> Temporary Member</title>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-success { background: #28a745; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h1><?= $isEdit ? 'Edit' : 'Add New' ?> Temporary Member</h1>
    <form action="?action=<?= $isEdit ? 'update' : 'create' ?>" method="POST">
        <?= \App\Core\CSRFManager::getTokenInput() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $member['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>TM No (Auto-generated)</label>
            <input type="text" name="tm_no" value="<?= htmlspecialchars($member['tm_no'] ?? '') ?>" placeholder="Leave blank for auto-generation" <?= $isEdit ? 'readonly' : '' ?>>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($member['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="dob" value="<?= htmlspecialchars($member['dob'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3"><?= htmlspecialchars($member['address'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Dependents</label>
            <div id="dependents-container" style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
                <table style="width: 100%;" id="dependents-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Relation</th>
                            <th>DOB</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="dependents-body">
                        <!-- Rows added via JS -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary" style="margin-top: 10px; padding: 5px 10px;" onclick="addDependentRow()">+ Add Relative</button>
            </div>
            <input type="hidden" name="dependents" id="dependents-json">
            <small id="validation-msg" style="color: #dc3545; font-weight: bold;"></small>
        </div>

        <div class="form-group">
            <label>Validity (Auto-Calculated)</label>
            <div style="background: #e9ecef; padding: 10px; border-radius: 4px; border: 1px solid #ccc; color: #495057;">
                Valid for 3 months starting from the 1st of <?= (date('d') >= 26) ? 'next' : 'this' ?> month.
            </div>
            <input type="hidden" name="validity" value="">
        </div>

        <div class="form-group">
            <label>Payment Amount (Auto-calculated)</label>
            <input type="number" step="0.01" name="payment_amount" value="<?= htmlspecialchars((string)($member['payment_amount'] ?? '0.00')) ?>" readonly>
            <small>Includes: Base Rates + I-Card Fees (₹10/each) + 18% GST.</small>
        </div>

        <div class="form-group">
            <label>Payment Reference (UTR / Check No / Cash Receipt)</label>
            <input type="text" name="payment_reference" value="<?= htmlspecialchars($member['payment_reference'] ?? '') ?>" placeholder="Enter transaction reference">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success" onclick="return prepareSubmission()"><?= $isEdit ? 'Update Member' : 'Create Member' ?></button>
            <a href="?action=index" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    const dependentsBody = document.getElementById('dependents-body');
    const existingDeps = <?= $member['dependents'] ?? '[]' ?>;
    
    function addDependentRow(data = {name: '', relation: 'Son', dob: ''}) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" class="dep-name" value="${data.name}" required></td>
            <td>
                <select class="dep-relation" onchange="validateRow(this.closest('tr'))">
                    <option value="Spouse" ${data.relation === 'Spouse' ? 'selected' : ''}>Spouse</option>
                    <option value="Son" ${data.relation === 'Son' ? 'selected' : ''}>Son</option>
                    <option value="Daughter" ${data.relation === 'Daughter' ? 'selected' : ''}>Daughter</option>
                </select>
            </td>
            <td><input type="date" class="dep-dob" value="${data.dob}" onchange="validateRow(this.closest('tr'))" required></td>
            <td><button type="button" onclick="this.closest('tr').remove()" style="background:none; border:none; color:red; cursor:pointer;">✖</button></td>
        `;
        dependentsBody.appendChild(row);
        validateRow(row);
    }

    function calculateAge(dob) {
        if (!dob) return 0;
        const diff = Date.now() - new Date(dob).getTime();
        return Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25));
    }

    function validateRow(row) {
        const relation = row.querySelector('.dep-relation').value;
        const dob = row.querySelector('.dep-dob').value;
        const msg = document.getElementById('validation-msg');
        msg.textContent = "";
        
        if (relation === 'Son' || relation === 'Daughter') {
            const age = calculateAge(dob);
            if (dob && (age < 5 || age > 21)) {
                msg.textContent = `⚠️ Children must be between 5 and 21 years old (Age detected: ${age})`;
                row.style.background = "#fff3f3";
                return false;
            }
        }
        row.style.background = "transparent";
        return true;
    }

    function prepareSubmission() {
        const rows = dependentsBody.querySelectorAll('tr');
        const deps = [];
        let isValid = true;

        rows.forEach(row => {
            if (!validateRow(row)) isValid = false;
            deps.push({
                name: row.querySelector('.dep-name').value,
                relation: row.querySelector('.dep-relation').value,
                dob: row.querySelector('.dep-dob').value
            });
        });

        if (!isValid) {
            alert("Please correct the errors in dependents data (Check Age/Relation).");
            return false;
        }

        document.getElementById('dependents-json').value = JSON.stringify(deps);
        return true;
    }

    // Initialize existing dependents
    document.addEventListener('DOMContentLoaded', () => {
        if (existingDeps.length > 0) {
            existingDeps.forEach(addDependentRow);
        } else {
            addDependentRow();
        }
    });
</script>
    </form>
</div>
</body>
</html>
