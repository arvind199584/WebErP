<!DOCTYPE html>
<html>
<head>
    <title>Attendance Module</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; text-align: center; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: inline-block; margin: 10px; text-decoration: none; color: #333; width: 250px; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Attendance Management</h1>
    <a href="?action=mark" class="card">
        <h2>Mark New Attendance</h2>
        <p>Enter attendance for employees for a specific month.</p>
    </a>
    <a href="?action=edit" class="card">
        <h2>View / Edit Sheet</h2>
        <p>View and edit the full attendance grid for any agreement and month.</p>
    </a>
</div>
</body>
</html>
