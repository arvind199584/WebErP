<?php
/**
 * @var array $data
 * @var \App\Modules\AA_ES\DTO\AA_ES_DTO $aa_es
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Agreement - Step 2</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .step-indicator { text-align: center; margin-bottom: 20px; color: #666; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; }
        .btn-next { background-color: #007bff; }
        .btn-back { background-color: #6c757d; }
        .info-box { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-box strong { display: inline-block; width: 150px; }
        .validation-error { color: #dc3545; font-size: 0.9em; margin-top: 5px; display: none; }
    </style>
    <script>
        const estimated_cost = <?= $aa_es->estimated_cost ?>;
        const justified_amount = <?= $aa_es->justified_amount ?>;

        function calculateTenderedAmount() {
            const serviceCharge = parseFloat(document.getElementById('service_charge_percent').value) || 0;
            // CORRECTED FORMULA: TA = EC * (1 + SC/100)
            const tenderedAmount = estimated_cost * (1 + (serviceCharge / 100));
            document.getElementById('tendered_amount').value = Math.round(tenderedAmount);
            validateTenderedAmount();
        }

        function calculateServiceCharge() {
            const tenderedAmount = parseFloat(document.getElementById('tendered_amount').value) || 0;
            if (tenderedAmount === 0 || estimated_cost === 0) return;
            // CORRECTED FORMULA: SC % = ((TA / EC) - 1) * 100
            const serviceCharge = ((tenderedAmount / estimated_cost) - 1) * 100;
            document.getElementById('service_charge_percent').value = serviceCharge.toFixed(2);
            validateTenderedAmount();
        }

        function validateTenderedAmount() {
            const tenderedAmount = parseFloat(document.getElementById('tendered_amount').value) || 0;
            const errorEl = document.getElementById('tendered-amount-error');
            const nextBtn = document.querySelector('.btn-next');

            if (tenderedAmount > justified_amount) {
                errorEl.innerText = `Error: Tendered Amount (Rs. ${tenderedAmount.toFixed(2)}) cannot exceed Justified Amount (Rs. ${justified_amount.toFixed(2)}).`;
                errorEl.style.display = 'block';
                nextBtn.disabled = true;
            } else {
                errorEl.style.display = 'none';
                nextBtn.disabled = false;
            }
        }
    </script>
</head>
<body>

<div class="container">
    <h1>Create Agreement</h1>
    <div class="step-indicator">Step 2 of 3: Financials</div>

    <div class="info-box">
        <div><strong>Estimated Cost:</strong> Rs. <?= number_format($aa_es->estimated_cost, 2) ?></div>
        <div><strong>Justified Amount:</strong> Rs. <?= number_format($aa_es->justified_amount, 2) ?></div>
    </div>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="2">

        <div class="form-group">
            <label for="service_charge_percent">Service Charge (%)</label>
            <input type="number" step="0.01" id="service_charge_percent" name="service_charge_percent" value="<?= htmlspecialchars($data['service_charge_percent'] ?? '') ?>" oninput="calculateTenderedAmount()">
        </div>

        <div class="form-group">
            <label for="tendered_amount">Tendered Amount</label>
            <input type="number" step="1" id="tendered_amount" name="tendered_amount" value="<?= htmlspecialchars($data['tendered_amount'] ?? '') ?>" oninput="calculateServiceCharge()">
            <div id="tendered-amount-error" class="validation-error"></div>
        </div>

        <div class="form-actions">
            <a href="?action=showCreateForm&step=1" class="btn btn-back">Back</a>
            <button type="submit" class="btn btn-next">Next Step</button>
        </div>
    </form>
</div>

</body>
</html>
