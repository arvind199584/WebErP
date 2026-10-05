<?php
$data = $_SESSION['deviation_create_data'];
$agreementId = $data['agreement_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Propose Deviation - Step 2</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
        input { width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .summary { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; text-align: right; }
    </style>
    <script>
        let originalBOQ = null;

        async function loadOriginalBOQ() {
            const response = await fetch(`?action=getOriginalBOQAjax&agreement_id=<?= $agreementId ?>`);
            originalBOQ = await response.json();
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('boq_body');
            tbody.innerHTML = '';

            const items = originalBOQ.type === 'Manpower' ? originalBOQ.boq.Items : originalBOQ.boq;

            items.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.description}</td>
                    <td>${item.qty}</td>
                    <td>${item.rate}</td>
                    <td><input type="number" step="0.01" class="dev-qty" data-index="${index}" value="0" oninput="calculateTotals()"></td>
                    <td class="row-total">0.00</td>
                `;
                tbody.appendChild(row);
            });
            calculateTotals();
        }

        function calculateTotals() {
            let totalEst = 0;
            const items = originalBOQ.type === 'Manpower' ? originalBOQ.boq.Items : originalBOQ.boq;
            const period = originalBOQ.type === 'Manpower' ? parseInt(originalBOQ.boq.Period) : 1;

            document.querySelectorAll('.dev-qty').forEach((input, i) => {
                const devQty = parseFloat(input.value) || 0;
                const rate = parseFloat(items[i].rate);
                const rowTotal = devQty * rate * period;
                input.closest('tr').querySelector('.row-total').textContent = rowTotal.toFixed(2);
                totalEst += rowTotal;
            });

            const justified = totalEst * 1.15;
            const deviationAmount = justified * 1.18; // Simplified: Justified + 18% GST

            document.getElementById('estimated_cost').value = totalEst.toFixed(2);
            document.getElementById('justified_amount').value = justified.toFixed(2);
            document.getElementById('deviation_amount').value = deviationAmount.toFixed(2);

            document.getElementById('display_total').textContent = deviationAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});

            // Prepare JSON for submission
            const boqData = {
                type: originalBOQ.type,
                period: period,
                items: Array.from(document.querySelectorAll('.dev-qty')).map(input => ({
                    index: input.dataset.index,
                    dev_qty: input.value
                }))
            };
            document.getElementById('boq_json').value = json.stringify(boqData);
        }

        document.addEventListener('DOMContentLoaded', loadOriginalBOQ);
    </script>
</head>
<body>
<div class="container">
    <h1>Deviation BOQ Builder (Step 2/2)</h1>
    <p>Enter the <strong>additional quantity</strong> for each item. Use negative numbers for reductions.</p>

    <form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="estimated_cost" id="estimated_cost">
        <input type="hidden" name="justified_amount" id="justified_amount">
        <input type="hidden" name="deviation_amount" id="deviation_amount">
        <input type="hidden" name="boq_json" id="boq_json">

        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Item Description</th>
                    <th>Original Qty</th>
                    <th>Rate</th>
                    <th>Deviated Qty (+/-)</th>
                    <th>Impact (₹)</th>
                </tr>
            </thead>
            <tbody id="boq_body">
                <!-- Loaded via JS -->
            </tbody>
        </table>

        <div class="summary">
            <h3>Total Deviation Impact: ₹ <span id="display_total">0.00</span></h3>
            <button type="submit" class="btn">Submit Deviation Proposal</button>
        </div>
    </form>
</div>
</body>
</html>
