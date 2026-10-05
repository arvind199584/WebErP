<?php
/**
 * @var \App\Modules\Agency\DTO\AgencyDTO $agency The agency to edit.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Agency</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-actions { text-align: right; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; display: inline-block; margin-left: 5px; }
        .btn-submit { background-color: #007bff; }
        .btn-cancel { background-color: #6c757d; }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Agency</h1>

    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?= $agency->id ?>">

        <div class="form-group">
            <label for="name">Agency Name (Required)</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars((string)($agency->name ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label for="account_no">Account No</label>
            <input type="text" id="account_no" name="account_no" value="<?= htmlspecialchars((string)($agency->account_no ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="ifsc">IFSC Code</label>
            <input type="text" id="ifsc" name="ifsc" value="<?= htmlspecialchars((string)($agency->ifsc ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="bank_name">Bank Name</label>
            <input type="text" id="bank_name" name="bank_name" value="<?= htmlspecialchars((string)($agency->bank_name ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="pan_no">PAN No</label>
            <input type="text" id="pan_no" name="pan_no" value="<?= htmlspecialchars((string)($agency->pan_no ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="gst_no">GST No</label>
            <input type="text" id="gst_no" name="gst_no" value="<?= htmlspecialchars((string)($agency->gst_no ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3"><?= htmlspecialchars((string)($agency->address ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="contact_person">Contact Person</label>
            <input type="text" id="contact_person" name="contact_person" value="<?= htmlspecialchars((string)($agency->contact_person ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars((string)($agency->email ?? '')) ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-submit">Update Agency</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
