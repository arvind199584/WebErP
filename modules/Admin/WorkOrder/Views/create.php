<!DOCTYPE html>
<html>
<head>
    <title>Create Work Order</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin: auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; }
        .btn { padding: 10px 15px; }
    </style>
    <script>
        let aa_es_data = {};
        <?php foreach ($aa_es_list as $item) {
            echo "aa_es_data[{$item['id']}] = { est: {$item['estimated_cost']}, just: {$item['justified_amount']} };\n";
        } ?>

        function onAaEsChange() {
            const aa_es_id = document.getElementById('aa_es_id').value;
            const data = aa_es_data[aa_es_id];
            if (data) {
                document.getElementById('est_cost_display').innerText = 'Est. Cost: ' + data.est;
                document.getElementById('just_amount_display').innerText = 'Just. Amt: ' + data.just;
            }
        }
        function calculateTendered() {
            const aa_es_id = document.getElementById('aa_es_id').value;
            const data = aa_es_data[aa_es_id];
            if (!data) return;
            const sc = parseFloat(document.getElementById('service_charge_percent').value) || 0;
            const ta = data.est * (1 + sc / 100);
            document.getElementById('tendered_amount').value = Math.round(ta);
        }
        function calculateServiceCharge() {
            const aa_es_id = document.getElementById('aa_es_id').value;
            const data = aa_es_data[aa_es_id];
            if (!data) return;
            const ta = parseFloat(document.getElementById('tendered_amount').value) || 0;
            if (ta === 0) return;
            const sc = ((ta / data.est) - 1) * 100;
            document.getElementById('service_charge_percent').value = sc.toFixed(2);
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Create Work Order</h1>
    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label for="aa_es_id">AA & ES</label>
            <select id="aa_es_id" name="aa_es_id" onchange="onAaEsChange()" required>
                <option value="">Select...</option>
                <?php foreach ($aa_es_list as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['alias']) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="est_cost_display"></div>
            <div id="just_amount_display"></div>
        </div>
        <div class="form-group">
            <label for="agency_id">Agency</label>
            <select id="agency_id" name="agency_id" required>
                <option value="">Select...</option>
                <?php foreach ($agencies as $agency): ?>
                    <option value="<?= $agency['id'] ?>"><?= htmlspecialchars($agency['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="work_order_no">Work Order No</label>
            <input type="text" id="work_order_no" name="work_order_no" required>
        </div>
        <div class="form-group">
            <label for="service_charge_percent">Service Charge (%)</label>
            <input type="number" step="0.01" id="service_charge_percent" name="service_charge_percent" oninput="calculateTendered()">
        </div>
        <div class="form-group">
            <label for="tendered_amount">Tendered Amount</label>
            <input type="number" step="1" id="tendered_amount" name="tendered_amount" oninput="calculateServiceCharge()">
        </div>
        <button type="submit" class="btn">Create</button>
    </form>
</div>
</body>
</html>
