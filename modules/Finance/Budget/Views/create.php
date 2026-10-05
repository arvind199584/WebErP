<?php
/**
 * @var array $oldInput Previous input data.
 * @var array $offices List of offices (for superuser).
 * @var string|null $message Success message.
 * @var string|null $error Error message.
 */
$oldInput = $oldInput ?? [];
$isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Budget Entry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; display: inline-block; margin-left: 5px; }
        .btn-submit { background-color: #28a745; }
        .btn-continue { background-color: #17a2b8; }
        .btn-cancel { background-color: #6c757d; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="container">
    <h1>Create New Budget Entry</h1>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <?php if ($isSuperuser): ?>
            <div class="form-group">
                <label for="officeid">Office</label>
                <select id="officeid" name="officeid" required>
                    <option value="">Select Office</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?= $office->Officeid ?>" <?= (isset($oldInput['officeid']) && $oldInput['officeid'] == $office->Officeid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($office->OfficeName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="fy">Financial Year (YYYY-YY)</label>
            <input type="text" id="fy" name="fy" value="<?= htmlspecialchars($oldInput['fy'] ?? '') ?>" required placeholder="2024-25" pattern="\d{4}-\d{2}">
        </div>

        <div class="form-group">
            <label for="code">Budget Code</label>
            <input type="text" id="code" name="code" value="<?= htmlspecialchars($oldInput['code'] ?? '') ?>" required placeholder="e.g., 4210">
        </div>

        <div class="form-group">
            <label for="name_of_work">Name of Work</label>
            <textarea id="name_of_work" name="name_of_work" rows="3" required><?= htmlspecialchars($oldInput['name_of_work'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="provision">Provision (in Lacs)</label>
            <input type="number" step="0.01" id="provision" name="provision" value="<?= htmlspecialchars($oldInput['provision'] ?? '') ?>" required placeholder="0.00">
        </div>

        <div class="form-actions">
            <button type="submit" name="save_and_continue" class="btn btn-continue">Save & Continue</button>
            <button type="submit" class="btn btn-submit">Save & Exit</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
