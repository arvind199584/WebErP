<!DOCTYPE html>
<html>
<head>
    <title>Add Contact</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>Add New Contact</h1>
    <form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="name" required placeholder="e.g. Shri Rajesh Kumar">
        </div>
        <div class="form-group">
            <label>Designation:</label>
            <input type="text" name="designation" placeholder="e.g. Commissioner (Sports)">
        </div>
        <div class="form-group">
            <label>Department / Office:</label>
            <input type="text" name="department" placeholder="e.g. DDA, Vikas Sadan">
        </div>
        <div class="form-group">
            <label>Category:</label>
            <select name="category">
                <option value="Higher Authority">Higher Authority</option>
                <option value="Agency">Agency</option>
                <option value="Internal Department">Internal Department</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Full Address:</label>
            <textarea name="address" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email">
        </div>
        <button type="submit" class="btn">Save Contact</button>
    </form>
</div>
</body>
</html>
