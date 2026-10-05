<?php
/**
 * @var array $data Previous session data.
 * @var array $wageItems List of wage items with current rates.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create AA & ES - Step 2</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 40px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; gap: 30px; }
        .form-section { flex: 2; }
        .summary-section { flex: 1; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; height: fit-content; position: sticky; top: 20px; }

        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .step-indicator { text-align: center; margin-bottom: 30px; color: #777; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box; }

        .checkbox-group { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd; }
        .checkbox-group label { font-weight: normal; cursor: pointer; display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 8px; }
        .highlight-label { color: #007bff; font-weight: bold !important; }

        table.boq-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #ddd; }
        .boq-table th, .boq-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .boq-table th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        .boq-table input, .boq-table select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

        .form-actions { text-align: right; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; display: inline-block; margin-left: 10px; font-size: 14px; transition: background 0.2s; }
        .btn-next { background-color: #007bff; }
        .btn-next:hover { background-color: #0056b3; }
        .btn-back { background-color: #6c757d; }
        .btn-back:hover { background-color: #5a6268; }
        .btn-add { background-color: #28a745; padding: 8px 16px; font-size: 13px; }
        .btn-add:hover { background-color: #218838; }
        .btn-remove { background-color: #dc3545; padding: 6px 12px; font-size: 12px; border-radius: 4px; color: white; border: none; cursor: pointer; }
        .btn-remove:hover { background-color: #c82333; }

        .summary-title { font-size: 1.2em; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95em; }
        .summary-row.total { font-weight: bold; font-size: 1.1em; border-top: 1px solid #ccc; padding-top: 10px; margin-top: 10px; color: #28a745; }
        .summary-val { font-family: monospace; }
    </style>
    <script>
        function addItemRow() {
            const table = document.getElementById('boq-body');
            const rowCount = table.rows.length;
            const row = table.insertRow(rowCount);

            row.innerHTML = `
                <td>
                    <select name="items[${rowCount}][description]" onchange="updateRate(this, ${rowCount}); calculateTotals();" required>
                        <option value="">Select Designation</option>
                        <?php foreach ($wageItems as $item): ?>
                            <option value="<?= htmlspecialchars($item['item_name']) ?>" data-rate="<?= $item['current_wage'] ?>">
                                <?= htmlspecialchars($item['item_name']) ?> (Rs. <?= $item['current_wage'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="items[${rowCount}][qty]" class="qty-input" step="0.01" required placeholder="0" oninput="calculateTotals()"></td>
                <td><input type="number" name="items[${rowCount}][rate]" id="rate_${rowCount}" class="rate-input" step="0.01" readonly placeholder="0.00" style="background-color: #e9ecef;"></td>
                <td style="text-align: center;"><button type="button" class="btn-remove" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            calculateTotals();
        }

        function updateRate(select, index) {
            const rate = select.options[select.selectedIndex].getAttribute('data-rate');
            document.getElementById('rate_' + index).value = rate || 0;
        }

        function calculateTotals() {
            const period = parseFloat(document.getElementById('period').value) || 0;
            const is7Day = document.getElementById('chk_7day').checked;
            const hasEsic = document.getElementById('chk_esic').checked;
            const hasEpf = document.getElementById('chk_epf').checked;
            const hasBonus = document.getElementById('chk_bonus').checked;

            let totalEst = 0;
            let totalEsic = 0;
            let totalEpf = 0;
            let totalBonus = 0;

            const rows = document.querySelectorAll('#boq-body tr');

            rows.forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                const rateInput = row.querySelector('.rate-input');

                if (qtyInput && rateInput) {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const rate = parseFloat(rateInput.value) || 0;

                    // Apply 7/6 multiplier if 7-day contract
                    const effectiveQty = is7Day ? (qty * 7.0 / 6.0) : qty;

                    // Base Cost
                    totalEst += (effectiveQty * rate * period);

                    // Components
                    if (hasEsic) {
                        totalEsic += (effectiveQty * period * 0.0325 * Math.min(rate, 21000));
                    }
                    if (hasEpf) {
                        totalEpf += (effectiveQty * period * 0.13 * Math.min(rate, 15000));
                    }
                    if (hasBonus) {
                        totalBonus += (effectiveQty * period * 0.0833 * Math.min(rate, 21000));
                    }
                }
            });

            const justified = totalEst * 1.15;
            const gst = justified * 0.18;
            const totalAAES = Math.round(justified + gst + totalEsic + totalEpf + totalBonus);

            // Update UI
            document.getElementById('val_est').textContent = formatCurrency(Math.round(totalEst));
            document.getElementById('val_justified').textContent = formatCurrency(justified);
            document.getElementById('val_gst').textContent = formatCurrency(gst);
            document.getElementById('val_esic').textContent = formatCurrency(totalEsic);
            document.getElementById('val_epf').textContent = formatCurrency(totalEpf);
            document.getElementById('val_bonus').textContent = formatCurrency(totalBonus);
            document.getElementById('val_total').textContent = formatCurrency(totalAAES);
        }

        function formatCurrency(num) {
            return num.toLocaleString('en-IN', { style: 'currency', currency: 'INR' });
        }
    </script>
</head>
<body>

<div class="container">
    <div class="form-section">
        <h1>Create AA & ES (Manpower)</h1>
        <div class="step-indicator">Step 2 of 3: BOQ Details</div>

        <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="step" value="2">

            <div class="form-group">
                <label for="period">Period (Months)</label>
                <input type="number" id="period" name="period" required value="12" min="1" oninput="calculateTotals()">
            </div>

            <div class="checkbox-group">
                <label class="highlight-label"><input type="checkbox" id="chk_7day" name="is_7_day" onchange="calculateTotals()"> 7-Day Contract (Add Reliever 7/6)</label>
                <label><input type="checkbox" id="chk_esic" name="esic" checked onchange="calculateTotals()"> ESIC (3.25%)</label>
                <label><input type="checkbox" id="chk_epf" name="epf" checked onchange="calculateTotals()"> EPF (13%)</label>
                <label><input type="checkbox" id="chk_bonus" name="bonus" checked onchange="calculateTotals()"> Bonus (8.33%)</label>
            </div>

            <h3>Items</h3>
            <table class="boq-table">
                <thead>
                    <tr>
                        <th>Designation</th>
                        <th width="120">Qty (Nos)</th>
                        <th width="180">Rate (Current)</th>
                        <th width="80" style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="boq-body">
                </tbody>
            </table>
            <button type="button" class="btn btn-add" onclick="addItemRow()">+ Add Item Row</button>

            <div class="form-actions">
                <a href="?action=showCreateForm&step=1" class="btn btn-back">Back</a>
                <button type="submit" class="btn btn-next">Next Step</button>
            </div>
        </form>
    </div>

    <div class="summary-section">
        <div class="summary-title">Live Calculation</div>
        <div class="summary-row">
            <span>Estimated Cost (Rounded):</span>
            <span class="summary-val" id="val_est">₹0.00</span>
        </div>
        <div class="summary-row">
            <span>Justified (Est + 15%):</span>
            <span class="summary-val" id="val_justified">₹0.00</span>
        </div>
        <div class="summary-row">
            <span>GST (18%):</span>
            <span class="summary-val" id="val_gst">₹0.00</span>
        </div>
        <hr>
        <div class="summary-row">
            <span>ESIC:</span>
            <span class="summary-val" id="val_esic">₹0.00</span>
        </div>
        <div class="summary-row">
            <span>EPF:</span>
            <span class="summary-val" id="val_epf">₹0.00</span>
        </div>
        <div class="summary-row">
            <span>Bonus:</span>
            <span class="summary-val" id="val_bonus">₹0.00</span>
        </div>
        <div class="summary-row total">
            <span>Total AA & ES (Rounded):</span>
            <span class="summary-val" id="val_total">₹0.00</span>
        </div>
    </div>

    <script>
        addItemRow();
        setTimeout(calculateTotals, 100);
    </script>
</div>

</body>
</html>
