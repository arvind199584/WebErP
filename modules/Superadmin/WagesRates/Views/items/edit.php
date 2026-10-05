<?php
/**
 * @var \App\Modules\WagesRates\DTO\WageItemDTO $item The item to edit.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Wage Item</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; }
        .btn-submit { background-color: #007bff; }
        .btn-cancel { background-color: #6c757d; display: inline-block; margin-left: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Wage Item</h1>
    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?= $item->id ?>">
        <div class="form-group">
            <label for="authority">Authority</label>
            <select id="authority" name="authority" required>
                <option value="Sports Wing" <?= $item->authority === 'Sports Wing' ? 'selected' : '' ?>>Sports Wing</option>
                <option value="Central Govt" <?= $item->authority === 'Central Govt' ? 'selected' : '' ?>>Central Govt</option>
            </select>
        </div>
        <div class="form-group">
            <label for="item_name">Item Name (Designation)</label>
            <input type="text" id="item_name" name="item_name" value="<?= htmlspecialchars($item->item_name) ?>" required>
        </div>
        <div class="form-group">
            <label for="json_key">Mapped Category (JSON Key)</label>
            <input type="text" id="json_key" name="json_key" value="<?= htmlspecialchars($item->json_key) ?>" required>
            <small>Must match the key used in the Wage Order rates.</small>
        </div>
        <div class="form-group">
            <label for="unit">Unit</label>
            <select id="unit" name="unit" required>
                <option value="Per Person Per Month" <?= $item->unit === 'Per Person Per Month' ? 'selected' : '' ?>>Per Person Per Month</option>
                <option value="Per Person Per Day" <?= $item->unit === 'Per Person Per Day' ? 'selected' : '' ?>>Per Person Per Day</option>
            </select>
        </div>
        <div class="form-group">
            <label for="fixed_allowance">Fixed Allowance</label>
            <input type="number" step="0.01" id="fixed_allowance" name="fixed_allowance" value="<?= htmlspecialchars((string)$item->fixed_allowance) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-submit">Update Item</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
