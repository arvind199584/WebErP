<?php
/**
 * @var \App\Modules\Agreement\DTO\AgreementDTO $agreement
 */
$currentScope = end($agreement->scope);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scope</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; }
        .btn-submit { background-color: #28a745; }
        .btn-cancel { background-color: #6c757d; }
        .scope-history { margin-top: 30px; }
        .scope-history h3 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .scope-item { background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Scope for Agreement: <?= htmlspecialchars($agreement->agreement_no) ?></h1>

    <form action="?action=updateScope" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?= $agreement->id ?>">

        <h3>Add New Scope</h3>
        <div class="form-group">
            <label for="new_scope_date">Effective From Date:</label>
            <input type="date" id="new_scope_date" name="new_scope_date" required>
        </div>

        <h4>Deployed Quantities:</h4>
        <?php foreach ($currentScope['Items'] as $item => $qty): ?>
            <div class="form-group">
                <label><?= htmlspecialchars($item) ?></label>
                <input type="number" name="items[<?= htmlspecialchars($item) ?>]" value="<?= htmlspecialchars((string)$qty) ?>" required>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-submit">Update Scope</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>

    <div class="scope-history">
        <h3>Scope History</h3>
        <?php foreach (array_reverse($agreement->scope) as $scope): ?>
            <div class="scope-item">
                <strong>From:</strong> <?= htmlspecialchars($scope['datefrom']) ?>
                <strong>To:</strong> <?= htmlspecialchars($scope['dateto'] ?? 'Current') ?>
                <pre><?= htmlspecialchars(json_encode($scope['Items'], JSON_PRETTY_PRINT)) ?></pre>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
