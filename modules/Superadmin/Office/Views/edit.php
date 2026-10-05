<?php
/**
 * @var \App\Modules\Office\DTO\OfficeDTO $office The office object to edit, passed from the controller.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; }
        .btn-submit { background-color: #007bff; }
        .btn-cancel { background-color: #6c757d; display: inline-block; margin-left: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Office (ID: <?= htmlspecialchars((string)$office->Officeid); ?>)</h1>
    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="Officeid" value="<?= htmlspecialchars((string)$office->Officeid); ?>">
        <div class="form-group">
            <label for="OfficeName">Office Name</label>
            <input type="text" id="OfficeName" name="OfficeName" value="<?= htmlspecialchars($office->OfficeName); ?>" required>
        </div>
        <div class="form-group">
            <label for="Address">Address</label>
            <textarea id="Address" name="Address" rows="3"><?= htmlspecialchars($office->Address); ?></textarea>
        </div>
        <div class="form-group">
            <label for="Phone">Phone</label>
            <input type="text" id="Phone" name="Phone" value="<?= htmlspecialchars($office->Phone); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($office->email); ?>">
        </div>
        <div class="form-group">
            <label for="ContactPerson">Contact Person</label>
            <input type="text" id="ContactPerson" name="ContactPerson" value="<?= htmlspecialchars($office->ContactPerson); ?>">
        </div>
        <div class="form-group" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
            <label style="font-size: 1.1em; margin-bottom: 10px;">Enable Modules</label>
            <div style="display: flex; gap: 20px;">
                <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="has_hr" value="1" style="width: auto;" <?= $office->has_hr ? 'checked' : ''; ?>> HR (Employees & Attendance)
                </label>
                <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="has_finance" value="1" style="width: auto;" <?= $office->has_finance ? 'checked' : ''; ?>> Finance / Budget
                </label>
                <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="has_store" value="1" style="width: auto;" <?= $office->has_store ? 'checked' : ''; ?>> Store Room
                </label>
                <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="has_workshop" value="1" style="width: auto;" <?= $office->has_workshop ? 'checked' : ''; ?>> Workshop Management
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-submit">Update Office</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
