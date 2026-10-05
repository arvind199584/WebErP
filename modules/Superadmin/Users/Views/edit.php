<?php
/**
 * @var \App\Modules\Superadmin\Users\DTO\UserDTO $user The user object to edit.
 * @var array $offices An array of OfficeDTO objects.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/form.css"> <!-- Assuming a shared CSS file -->
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
        .password-note { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit User: <?= htmlspecialchars($user->getFullName()) ?></h1>
    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?= $user->id ?>">
        <div class="form-group">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($user->firstname) ?>" required>
        </div>
        <div class="form-group">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($user->lastname) ?>">
        </div>
        <div class="form-group">
            <label for="usrname">Username</label>
            <input type="text" id="usrname" name="usrname" value="<?= htmlspecialchars($user->usrname) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user->phone) ?>">
        </div>
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password">
            <small class="password-note">Leave blank to keep the current password.</small>
        </div>
        <div class="form-group">
            <label for="officeid">Office</label>
            <select id="officeid" name="officeid" required>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= $office->Officeid ?>" <?= ($user->officeid == $office->Officeid) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($office->OfficeName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="staff" <?= ($user->role == 'staff') ? 'selected' : '' ?>>Staff</option>
                <option value="manager" <?= ($user->role == 'manager') ? 'selected' : '' ?>>Manager</option>
                <option value="superuser" <?= ($user->role == 'superuser') ? 'selected' : '' ?>>Superuser (Office ID must be 1)</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-submit">Update User</button>
            <a href="?action=list" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
