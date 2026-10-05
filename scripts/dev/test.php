<?php
require_once __DIR__ . '/core/Database.php';

try {
    $db = App\Core\Database::getInstance()->getConnection();
    
    // Simulate what BaseController does
    $db->prepare("SELECT set_config('app.current_office_id', :officeId, false)")->execute(['officeId' => "1"]);
    $db->prepare("SELECT set_config('app.current_user_role', :role, false)")->execute(['role' => 'superuser']);
    $db->prepare("SELECT set_config('app.current_user_id', :userId, false)")->execute(['userId' => "1"]);

    echo "Config set successfully.\n";

    // Simulate getAllOffices
    $sql = "SELECT * FROM office ORDER BY OfficeName ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    echo "Offices: " . count($stmt->fetchAll()) . "\n";

    // Simulate getAllMachines
    $sql = "SELECT m.*, i.description as fuel_type_name, o.OfficeName as office_name 
            FROM turf_machines m 
            LEFT JOIN turf_inventory_items i ON m.fuel_item_id = i.id 
            JOIN office o ON m.officeid = o.Officeid 
            ORDER BY o.OfficeName, m.name";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    echo "Machines: " . count($stmt->fetchAll()) . "\n";

    // Simulate getLatestLogDate
    $sql = "SELECT MAX(log_date) FROM turf_consumption_log";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    echo "Latest date: " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
