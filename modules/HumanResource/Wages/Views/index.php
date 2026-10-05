<?php
$currentUser = \App\Modules\Superadmin\Users\Services\UserService::getCurrentUser();
$canRecalculate = in_array($currentUser['role'], ['superuser', 'manager']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wages Module</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; text-align: center; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: inline-block; margin: 10px; text-decoration: none; color: #333; width: 250px; vertical-align: top; height: 180px; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; color: #007bff; }
        .card-danger h2 { color: #dc3545; }
        .card-warning h2 { color: #ffc107; }
    </style>
</head>
<body>
<div class="container">
    <h1>Wages Management</h1>

    <a href="?action=showProcess" class="card">
        <h2>Process Wages</h2>
        <p>Find unprocessed periods and generate wage slips for employees.</p>
    </a>

    <a href="?action=showBatchDebit" class="card card-warning">
        <h2>Batch Payment</h2>
        <p>Post debit entries for all employees in an agreement at once.</p>
    </a>

    <a href="?action=showReport" class="card">
        <h2>Summary Report</h2>
        <p>View a summary of wages due, drawn, and balance for a selected period.</p>
    </a>

    <?php if ($canRecalculate): ?>
    <a href="?action=showRecalculate" class="card card-danger">
        <h2>Recalculate</h2>
        <p>Delete and regenerate wages for a specific period (Batch operation).</p>
    </a>
    <?php endif; ?>
</div>
</body>
</html>
