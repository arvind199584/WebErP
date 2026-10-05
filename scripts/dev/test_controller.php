<?php
require_once __DIR__ . '/core/Database.php';

try {
    $db = App\Core\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT column_name, column_default
        FROM information_schema.columns
        WHERE table_name = 'turf_consumption_log'
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "{$col['column_name']} | {$col['column_default']}\n";
    }

} catch (Exception $e) {

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
