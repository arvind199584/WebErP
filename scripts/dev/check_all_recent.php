<?php
require_once __DIR__ . '/core/Database.php';
try {
    $db = App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT 
            tcl.id,
            tcl.officeid,
            tcl.machine_id,
            tm.name AS machine_name,
            tcl.log_date,
            tcl.fuel_consumed_qty,
            tcl.running_hours,
            tcl.recorded_by AS operator,
            tcl.created_at
        FROM turf_consumption_log tcl
        JOIN turf_machines tm ON tcl.machine_id = tm.id
        ORDER BY tcl.id DESC
        LIMIT 10
    ");
    $logs = $stmt->fetchAll();
    echo "Top 10 Latest Rows in DB by ID:\n";
    foreach ($logs as $l) {
        echo "ID: {$l['id']} | Machine: {$l['machine_name']} (ID: {$l['machine_id']}) | Date: {$l['log_date']} | Fuel: {$l['fuel_consumed_qty']} | Hours: {$l['running_hours']} | Operator: {$l['operator']} | Created: {$l['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
