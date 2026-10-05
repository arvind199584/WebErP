<?php
require_once 'core/Database.php';
$db = \App\Core\Database::getInstance()->getConnection();

echo "=== Category Table Columns ===\n";
$stmt = $db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'turf_inventory_categories'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['column_name']} | {$r['data_type']} | Nullable: {$r['is_nullable']}\n";
}

echo "\n=== Category Table Constraints ===\n";
$stmt = $db->query("SELECT constraint_name, constraint_type FROM information_schema.table_constraints WHERE table_name = 'turf_inventory_categories'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['constraint_name']} | {$r['constraint_type']}\n";
}
