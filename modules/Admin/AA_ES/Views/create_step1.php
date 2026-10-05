<?php
/**
 * @var array $budgets List of available budgets.
 * @var array $data Previous session data.
 * @var array $wageOrders List of available wage orders.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create AA & ES - Step 1</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .step-indicator { text-align: center; margin-bottom: 30px; color: #777; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #007bff; outline: none; }

        .form-actions { text-align: right; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; display: inline-block; margin-left: 10px; font-size: 14px; transition: background 0.2s; }
        .btn-next { background-color: #007bff; }
        .btn-next:hover { background-color: #0056b3; }
        .btn-cancel { background-color: #6c757d; }
        .btn-cancel:hover { background-color: #5a6268; }

        #wage-order-group { display: none; background-color: #fff9e6; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Create AA & ES</h1>
    <div class="step-indicator">Step 1 of 3: Basic Information</div>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="step" value="1">

        <div class="form-group">
            <label for="budgetid">Select Budget Head</label>
            <select id="budgetid" name="budgetid" required>
                <option value="">Select Budget (Code - FY - Work)</option>
                <?php foreach ($budgets as $budget): ?>
                    <option value="<?= $budget['id'] ?>" <?= (isset($data['budgetid']) && $data['budgetid'] == $budget['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($budget['code']) ?> - <?= htmlspecialchars($budget['fy']) ?> - <?= htmlspecialchars(substr($budget['name_of_work'], 0, 50)) ?>...
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="sub_head">Sub Head (Description of Work)</label>
            <textarea id="sub_head" name="sub_head" rows="4" required placeholder="Enter the detailed description of the work..."><?= htmlspecialchars($data['sub_head'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="type">Type</label>
            <select id="type" name="type" onchange="toggleWageOrder(this.value)" required>
                <option value="Manpower" <?= (isset($data['type']) && $data['type'] === 'Manpower') ? 'selected' : '' ?>>Manpower</option>
                <option value="Market" <?= (isset($data['type']) && $data['type'] === 'Market') ? 'selected' : '' ?>>Market</option>
            </select>
        </div>

        <div id="wage-order-group" class="form-group">
            <label for="wage_order_date">Reference Wage Order (Effective Date)</label>
            <select id="wage_order_date" name="wage_order_date">
                <option value="<?= date('Y-m-d') ?>">Current Rates (Today)</option>
                <?php foreach ($wageOrders as $order): 
                    // In a real app, $order might be an object or array. 
                    // WageRateService::getAllOrders returns raw arrays from WageRateModel.
                    $val = $order['valid_from'];
                    $label = $order['authority'] . " - " . $order['letter_no'] . " (Eff: " . date('d-M-Y', strtotime($val)) . ")";
                ?>
                    <option value="<?= $val ?>" <?= (isset($data['wage_order_date']) && $data['wage_order_date'] === $val) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #856404;">Select this if you are entering a backdated AA & ES to use rates applicable at that time.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-next">Next Step</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<script>
    function toggleWageOrder(type) {
        var group = document.getElementById('wage-order-group');
        if (type === 'Manpower') {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    }
    // Initial call
    toggleWageOrder(document.getElementById('type').value);
</script>

</body>
</html>
