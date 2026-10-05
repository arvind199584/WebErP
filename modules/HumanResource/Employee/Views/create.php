<?php
/**
 * @var array $agreements
 * @var string $agreementDataJson
 * @var int|null $selectedAgreementId
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Employee</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }

        .tabs { display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: 20px; }
        .tab-link { padding: 10px 20px; cursor: pointer; border: none; background: none; font-size: 16px; color: #6c757d; }
        .tab-link.active { color: #007bff; border-bottom: 2px solid #007bff; font-weight: 600; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #495057; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; font-size: 14px; }

        .form-actions { text-align: right; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; font-size: 14px; }
        .btn-primary { background-color: #28a745; }
        .btn-secondary { background-color: #17a2b8; }
        .btn-cancel { background-color: #6c757d; }

        .info-box { background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; color: #0c5460; }
    </style>
    <script>
        const agreementData = <?= $agreementDataJson ?>;

        function openTab(evt, tabName) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        function onAgreementChange() {
            const agreementId = document.getElementById('agreement_id').value;

            // Sync hidden fields in both forms
            document.getElementById('agreement_id_manual').value = agreementId;
            document.getElementById('agreement_id_batch').value = agreementId;

            const data = agreementData[agreementId];
            if (data) {
                document.getElementById('joining_date').value = data.start;
                document.getElementById('leaving_date').value = data.end;

                // Populate Designation dropdowns in both tabs
                const desigSelects = [document.getElementById('designation'), document.getElementById('batch_designation')];
                desigSelects.forEach(select => {
                    select.innerHTML = '<option value="">Select...</option>';
                    data.scope.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item;
                        option.textContent = item;
                        select.appendChild(option);
                    });
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-link').click();
            const selectedId = "<?= $selectedAgreementId ?? '' ?>";
            if (selectedId) {
                const agreementSelect = document.getElementById('agreement_id');
                agreementSelect.value = selectedId;
                onAgreementChange();
            }
        });
    </script>
</head>
<body>
<div class="container">
    <h1>Create Employee</h1>

    <div class="form-group full-width">
        <label for="agreement_id">Select Agreement First</label>
        <select id="agreement_id" name="agreement_id_master" onchange="onAgreementChange()" required>
            <option value="">Select an Agreement...</option>
            <?php foreach ($agreements as $agreement): ?>
                <option value="<?= $agreement['id'] ?>"><?= htmlspecialchars($agreement['agreement_no']) ?> (<?= htmlspecialchars($agreement['agency_name']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="tabs">
        <button class="tab-link" onclick="openTab(event, 'manual')">Manual Entry</button>
        <button class="tab-link" onclick="openTab(event, 'batch')">Batch Entry</button>
    </div>

    <!-- MANUAL ENTRY TAB -->
    <div id="manual" class="tab-content">
        <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="agreement_id" id="agreement_id_manual">

            <fieldset>
                <legend>Identity</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <select id="designation" name="designation" required><option>Select Agreement First</option></select>
                    </div>
                    <div class="form-group">
                        <label for="deployed_office_id">Deployed Office (Optional)</label>
                        <select id="deployed_office_id" name="deployed_office_id">
                            <option value="">Same as Paying Office (Default)</option>
                            <?php foreach ($offices as $off): ?>
                                <option value="<?= $off->Officeid ?>"><?= htmlspecialchars($off->OfficeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Bindings</legend>
                <div class="form-grid">
                    <div class="form-group"><label for="joining_date">Joining Date</label><input type="date" id="joining_date" name="joining_date" required></div>
                    <div class="form-group"><label for="leaving_date">Leaving Date</label><input type="date" id="leaving_date" name="leaving_date"></div>
                    <div class="form-group"><label for="default_rest_day">Default Rest Day</label><select id="default_rest_day" name="default_rest_day"><option value="Sunday">Sunday</option><option value="Monday">Monday</option><option value="Tuesday">Tuesday</option><option value="Wednesday">Wednesday</option><option value="Thursday">Thursday</option><option value="Friday">Friday</option><option value="Saturday">Saturday</option></select></div>
                    <div class="form-group"><label><input type="checkbox" name="is_reliever"> Is Reliever?</label></div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" name="save_and_continue" class="btn btn-secondary">Save & Continue</button>
                <button type="submit" class="btn btn-primary">Save & Exit</button>
                <a href="?action=list" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>

    <!-- BATCH ENTRY TAB -->
    <div id="batch" class="tab-content">
        <div class="info-box">
            <strong>Batch Entry Logic:</strong><br>
            1. Enter names separated by commas (e.g., "John Doe, Jane Smith").<br>
            2. For each name, the system will create <strong>two</strong> records: the Employee and a Reliever placeholder.<br>
            3. Defaults: Joining/Leaving dates from Agreement, Rest Day: Sunday.
        </div>

        <form action="?action=process_batch" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="agreement_id" id="agreement_id_batch">

            <div class="form-group">
                <label for="batch_designation">Designation for this Batch</label>
                <select id="batch_designation" name="designation" required>
                    <option value="">Select Agreement First</option>
                </select>
            </div>

            <div class="form-group">
                <label for="names_list">Names (Comma Separated)</label>
                <textarea id="names_list" name="names_list" rows="6" placeholder="e.g. Sushil Kumar, Rajesh Sharma, Amit Gupta" required></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Process Batch & Create Records</button>
                <a href="?action=list" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
