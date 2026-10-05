<?php
$start = microtime(true);
try {
    $localPdo = new PDO("pgsql:host=localhost;port=5432;dbname=modpyphp;sslmode=disable", 'postgres', 'daredevil');
    $neonPdo = new PDO("pgsql:host=ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech;port=5432;dbname=neondb;sslmode=require", 'neondb_owner', 'npg_YoD4CLZ2TQpw');
    echo "Connected in " . round(microtime(true) - $start, 3) . "s\n";

    $tables = ['turf_machines', 'turf_consumption_log', 'turf_servicing_log', 'turf_inventory_items', 'turf_item_machine_compatibility'];
    foreach ($tables as $t) {
        $t0 = microtime(true);
        $localRows = $localPdo->query("SELECT id FROM \"$t\"")->fetchAll(PDO::FETCH_COLUMN);
        $neonRows = $neonPdo->query("SELECT id FROM \"$t\"")->fetchAll(PDO::FETCH_COLUMN);
        echo "Table $t: local=" . count($localRows) . ", neon=" . count($neonRows) . " checked in " . round(microtime(true) - $t0, 3) . "s\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
