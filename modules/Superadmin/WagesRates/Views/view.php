<?php
/**
 * @var \App\Modules\WagesRates\DTO\WageOrderDTO $order The wage order object to view.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Wage Order</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 20px; }
        .detail-row { display: flex; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .detail-label { font-weight: bold; width: 150px; }
        .detail-value { flex: 1; }
        .rates-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .rates-table th, .rates-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .rates-table th { background-color: #f2f2f2; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Wage Order Details</h1>

    <div class="detail-row">
        <span class="detail-label">ID:</span>
        <span class="detail-value"><?= htmlspecialchars((string)$order->id) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Authority:</span>
        <span class="detail-value"><?= htmlspecialchars($order->authority) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Letter No:</span>
        <span class="detail-value"><?= htmlspecialchars($order->letter_no) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Letter Date:</span>
        <span class="detail-value"><?= htmlspecialchars($order->letter_date) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Valid From:</span>
        <span class="detail-value"><?= htmlspecialchars($order->valid_from) ?></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Valid To:</span>
        <span class="detail-value"><?= htmlspecialchars($order->valid_to) ?></span>
    </div>

    <h3>Rates</h3>
    <table class="rates-table">
        <thead>
            <tr>
                <th>Category/Item</th>
                <th>Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->rates as $key => $value): ?>
                <tr>
                    <td><?= htmlspecialchars($key) ?></td>
                    <td><?= htmlspecialchars((string)$value) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="?action=list" class="btn-back">Back to List</a>
</div>

</body>
</html>
