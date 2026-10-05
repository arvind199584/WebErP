<?php
/**
 * @var array $data Previous session data.
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

        table.boq-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #ddd; }
        .boq-table th, .boq-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .boq-table th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        .boq-table input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

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

        /* Summary Styles */
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
                <td><input type="text" name="items[${rowCount}][description]" required placeholder="Item Description"></td>
                <td><input type="number" name="items[${rowCount}][qty]" class="qty-input" step="0.01" required placeholder="0" oninput="calculateTotals()"></td>
                <td><input type="text" name="items[${rowCount}][unit]" required placeholder="Unit"></td>
                <td><input type="number" name="items[${rowCount}][rate]" class="rate-input" step="0.01" required placeholder="0.00" oninput="calculateTotals()"></td>
                <td><input type="number" name="items[${rowCount}][gst]" class="gst-input" step="0.01" required placeholder="18" value="18" oninput="calculateTotals()"></td>
                <td style="text-align: center;"><button type="button" class="btn-remove" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            calculateTotals();
        }

        function calculateTotals() {
            let totalAmount = 0;

            const rows = document.querySelectorAll('#boq-body tr');

            rows.forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                const rateInput = row.querySelector('.rate-input');
                const gstInput = row.querySelector('.gst-input');

                if (qtyInput && rateInput && gstInput) {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const rate = parseFloat(rateInput.value) || 0;
                    const gst = parseFloat(gstInput.value) || 0;

                    // Amount = Qty * Rate * (1 + GST/100)
                    totalAmount += (qty * rate * (1 + gst / 100));
                }
            });

            // Update UI
            document.getElementById('val_total').textContent = formatCurrency(totalAmount);
        }

        function formatCurrency(num) {
            return num.toLocaleString('en-IN', { style: 'currency', currency: 'INR' });
        }
    </script>
</head>
<body>

<div class="container">
    <div class="form-section">
        <h1>Create AA & ES (Market)</h1>
        <div class="step-indicator">Step 2 of 3: BOQ Details</div>

        <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="step" value="2">

            <h3>Items</h3>
            <table class="boq-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th width="100">Qty</th>
                        <th width="100">Unit</th>
                        <th width="120">Rate</th>
                        <th width="80">GST %</th>
                        <th width="80" style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="boq-body">
                    <!-- Rows will be added here -->
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
            <span>Estimated Cost:</span>
            <span class="summary-val" id="val_total">₹0.00</span>
        </div>
        <div class="summary-row total">
            <span>Total AA & ES:</span>
            <span class="summary-val" id="val_total">₹0.00</span>
        </div>
    </div>

    <script>
        addItemRow(); // Add one row by default
        setTimeout(calculateTotals, 100);
    </script>
</div>

</body>
</html>
