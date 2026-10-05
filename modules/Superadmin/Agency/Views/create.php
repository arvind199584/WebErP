<?php
/**
 * @var array $oldInput Previous input data.
 * @var string|null $message Success message.
 * @var string|null $error Error message.
 */
$oldInput = $oldInput ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Agency</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
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
    <h1>Create New Agency</h1>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars((string)$message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars((string)$error) ?></div>
    <?php endif; ?>

    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label for="name">Agency Name (Required)</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars((string)($oldInput['name'] ?? '')) ?>" required placeholder="e.g., Ashok Kumar Jain & Bros">
            <small>Prefix "M/s" will be added automatically.</small>
        </div>

        <div class="form-group">
            <label for="account_no">Account No</label>
            <input type="text" id="account_no" name="account_no" value="<?= htmlspecialchars((string)($oldInput['account_no'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="ifsc">IFSC Code</label>
            <input type="text" id="ifsc" name="ifsc" value="<?= htmlspecialchars((string)($oldInput['ifsc'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="bank_name">Bank Name</label>
            <input type="text" id="bank_name" name="bank_name" value="<?= htmlspecialchars((string)($oldInput['bank_name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="pan_no">PAN No</label>
            <input type="text" id="pan_no" name="pan_no" value="<?= htmlspecialchars((string)($oldInput['pan_no'] ?? '')) ?>" placeholder="ABCDE1234F">
        </div>

        <div class="form-group">
            <label for="gst_no">GST No</label>
            <input type="text" id="gst_no" name="gst_no" value="<?= htmlspecialchars((string)($oldInput['gst_no'] ?? '')) ?>" placeholder="07ABCDE1234F1Z5">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3"><?= htmlspecialchars((string)($oldInput['address'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="contact_person">Contact Person</label>
            <input type="text" id="contact_person" name="contact_person" value="<?= htmlspecialchars((string)($oldInput['contact_person'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars((string)($oldInput['email'] ?? '')) ?>">
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
