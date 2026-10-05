<!DOCTYPE html>
<html>
<head>
    <title>New Member</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Register New Member</h1>
    <form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>

        <div class="form-group">
            <label>Membership Type:</label>
            <select name="membership_type" required>
                <option value="General">General</option>
                <option value="Associate">Associate</option>
                <option value="Govt Servant">Govt Servant</option>
                <option value="Senior Citizen">Senior Citizen</option>
            </select>
        </div>

        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="full_name" required placeholder="e.g. Sushil Kumar Sharma">
        </div>

        <div class="form-group">
            <label>Gender:</label>
            <select name="gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Date of Birth:</label>
            <input type="date" name="dob" required>
        </div>

        <div class="form-group">
            <label>UAIDI (Aadhar):</label>
            <input type="text" name="uaidi" required>
        </div>

        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email">
        </div>

        <div class="form-group">
            <label>Address:</label>
            <textarea name="address" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Joining Date:</label>
            <input type="date" name="joining_date" value="<?= date('Y-m-d') ?>">
        </div>

        <button type="submit" class="btn">Register Member</button>
    </form>
</div>
</body>
</html>
