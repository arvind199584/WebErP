<?php
$start = microtime(true);
try {
    $neonUrl = getenv('NEON_DATABASE_URL') ?: getenv('DATABASE_URL');
    if ($neonUrl) {
        $p = parse_url($neonUrl);
        $neonHost = $p['host'] ?? 'localhost';
        $neonDb = isset($p['path']) ? ltrim($p['path'], '/') : 'neondb';
        $neonUser = $p['user'] ?? 'neondb_owner';
        $neonPass = $p['pass'] ?? '';
    } else {
        $neonHost = getenv('NEON_HOST') ?: 'ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech';
        $neonDb = getenv('NEON_DB') ?: 'neondb';
        $neonUser = getenv('NEON_USER') ?: 'neondb_owner';
        $neonPass = getenv('NEON_PASS') ?: '';
    }
    $localPdo = new PDO("pgsql:host=localhost;port=5432;dbname=modpyphp;sslmode=disable", getenv('DB_USER') ?: 'postgres', getenv('DB_PASS') ?: 'daredevil');
    $neonPdo = new PDO("pgsql:host={$neonHost};port=5432;dbname={$neonDb};sslmode=require", $neonUser, $neonPass);
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
