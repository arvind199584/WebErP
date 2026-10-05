<?php
/**
 * @var array $employee
 * @var array $agreements
 * @var string $agreementDataJson
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Employee</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #495057; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; font-size: 14px; }

        .form-actions { text-align: right; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; font-size: 14px; display: inline-block; }
        .btn-primary { background-color: #007bff; }
        .btn-cancel { background-color: #6c757d; }

        fieldset { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        legend { padding: 0 10px; font-weight: bold; color: #007bff; }
    </style>
    <script>
        const agreementData = <?= $agreementDataJson ?>;

        function onAgreementChange() {
            const agreementId = document.getElementById('agreement_id').value;
            const data = agreementData[agreementId];
            if (data) {
                const desigSelect = document.getElementById('designation');
                const currentDesig = "<?= htmlspecialchars($employee['designation']) ?>";
                desigSelect.innerHTML = '<option value="">Select...</option>';
                data.scope.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    if (item === currentDesig) {
                        option.selected = true;
                    }
                    desigSelect.appendChild(option);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            onAgreementChange();
        });
    </script>
</head>
<body>
<div class="container">
    <h1>Edit Employee</h1>

    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?= $employee['id'] ?>">

        <div class="form-group full-width">
            <label for="agreement_id">Agreement</label>
            <select id="agreement_id" name="agreement_id" onchange="onAgreementChange()" required>
                <?php foreach ($agreements as $agreement): ?>
                    <option value="<?= $agreement['id'] ?>" <?= $employee['agreement_id'] == $agreement['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($agreement['agreement_no']) ?> (<?= htmlspecialchars($agreement['agency_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <fieldset>
            <legend>Identity</legend>
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($employee['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="designation">Designation</label>
                    <select id="designation" name="designation" required>
                        <option value="<?= htmlspecialchars($employee['designation']) ?>" selected><?= htmlspecialchars($employee['designation']) ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deployed_office_id">Deployed Office (Optional)</label>
                    <select id="deployed_office_id" name="deployed_office_id">
                        <option value="">Same as Paying Office (Default)</option>
                        <?php foreach ($offices as $off): ?>
                            <option value="<?= $off->Officeid ?>" <?= $employee['deployed_office_id'] == $off->Officeid ? 'selected' : '' ?>><?= htmlspecialchars($off->OfficeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Bindings</legend>
            <div class="form-grid">
                <div class="form-group">
                    <label for="joining_date">Joining Date</label>
                    <input type="date" id="joining_date" name="joining_date" value="<?= $employee['joining_date'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="leaving_date">Leaving Date</label>
                    <input type="date" id="leaving_date" name="leaving_date" value="<?= $employee['leaving_date'] ?>">
                </div>
                <div class="form-group">
                    <label for="default_rest_day">Default Rest Day</label>
                    <select id="default_rest_day" name="default_rest_day">
                        <?php foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
                            <option value="<?= $day ?>" <?= $employee['default_rest_day'] == $day ? 'selected' : '' ?>><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="margin-top: 30px;">
                        <input type="checkbox" name="is_reliever" <?= $employee['is_reliever'] ? 'checked' : '' ?>> Is Reliever?
                    </label>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Statutory & Bank Details</legend>
            <div class="form-grid">
                <div class="form-group">
                    <label for="account_no">Account No</label>
                    <input type="text" id="account_no" name="account_no" value="<?= htmlspecialchars($employee['account_no'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="ifsc">IFSC</label>
                    <input type="text" id="ifsc" name="ifsc" value="<?= htmlspecialchars($employee['ifsc'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" id="bank_name" name="bank_name" value="<?= htmlspecialchars($employee['bank_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="uan_no">UAN No</label>
                    <input type="text" id="uan_no" name="uan_no" value="<?= htmlspecialchars($employee['uan_no'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="esic_no">ESIC No</label>
                    <input type="text" id="esic_no" name="esic_no" value="<?= htmlspecialchars($employee['esic_no'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Employee</button>
            <a href="?action=list&agreement_id=<?= $employee['agreement_id'] ?>" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
