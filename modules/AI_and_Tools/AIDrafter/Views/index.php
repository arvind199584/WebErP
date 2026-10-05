<!DOCTYPE html>
<html>
<head>
    <title>AI Letter Drafter</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, textarea, input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #6f42c1; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; }
        .btn:hover { background-color: #59359a; }
        .hidden { display: none; }
        .phase-selector { display: flex; gap: 20px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 4px; }
    </style>
    <script>
        function togglePhase() {
            const phase = document.querySelector('input[name="phase"]:checked').value;
            document.getElementById('pre_award_group').classList.toggle('hidden', phase !== 'pre');
            document.getElementById('post_award_group').classList.toggle('hidden', phase !== 'post');

            // Update letter types based on phase
            const typeSelect = document.getElementById('letter_type');
            typeSelect.innerHTML = '';
            if (phase === 'pre') {
                typeSelect.add(new Option('Performance Guarantee (PG) Letter', 'PG_Request'));
                typeSelect.add(new Option('Award Letter', 'Award_Letter'));
            } else {
                typeSelect.add(new Option('Request Letter to Agency', 'Request_Letter'));
                typeSelect.add(new Option('Contract Extension', 'Extension'));
                typeSelect.add(new Option('Show Cause Notice', 'Show_Cause'));
            }
            typeSelect.add(new Option('General Correspondence', 'General'));
        }
    </script>
</head>
<body>
<div class="container">
    <h1>✨ AI Letter Drafter</h1>
    <p>Generate formal government correspondence using AI.</p>

    <form method="POST" action="?action=generate">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label>Select Phase:</label>
            <div class="phase-selector">
                <label><input type="radio" name="phase" value="pre" checked onchange="togglePhase()"> Pre-Award (Sanctions)</label>
                <label><input type="radio" name="phase" value="post" onchange="togglePhase()"> Post-Award (Agreements)</label>
            </div>
        </div>

        <!-- PRE-AWARD GROUP -->
        <div id="pre_award_group" class="form-group">
            <label>Select AA & ES (Sanction):</label>
            <select name="aa_es_id">
                <option value="">-- Select Sanction --</option>
                <?php foreach ($aa_es_list as $ae): ?>
                    <option value="<?= $ae['id'] ?>"><?= htmlspecialchars($ae['sub_head']) ?> (<?= htmlspecialchars($ae['budget_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- POST-AWARD GROUP -->
        <div id="post_award_group" class="form-group hidden">
            <label>Select Agreement:</label>
            <select name="agreement_id">
                <option value="">-- Select Agreement --</option>
                <?php foreach ($agreements as $ag): ?>
                    <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['agreement_no']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Recipient (From Address Book):</label>
            <select name="address_id">
                <option value="">-- Select Recipient (Optional) --</option>
                <?php foreach ($addresses as $addr): ?>
                    <option value="<?= $addr['id'] ?>"><?= htmlspecialchars($addr['name']) ?> (<?= htmlspecialchars($addr['designation']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Type of Letter:</label>
            <select name="letter_type" id="letter_type" required>
                <option value="PG_Request">Performance Guarantee (PG) Letter</option>
                <option value="Award_Letter">Award Letter</option>
                <option value="General">General Correspondence</option>
            </select>
        </div>

        <div class="form-group">
            <label>Additional Context / Instructions:</label>
            <textarea name="custom_context" rows="4" placeholder="e.g. Mention that the PG should be submitted in the form of FDR/Bank Guarantee..."></textarea>
        </div>

        <button type="submit" class="btn">Generate Draft with AI</button>
    </form>
</div>
</body>
</html>
