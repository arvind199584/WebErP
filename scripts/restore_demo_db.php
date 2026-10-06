<?php
/**
 * Standalone & invocable script to restore modpyphp_demo database back to golden seed.
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Core\Database;

function restoreDemoDatabase(): array
{
    $seedFile = __DIR__ . '/../database/dumps/demo_golden_seed.sql';
    if (!file_exists($seedFile)) {
        return ['success' => false, 'error' => 'Golden seed SQL file not found.'];
    }

    try {
        $db = Database::getInstance(true)->getConnection();
        $sql = file_get_contents($seedFile);
        $db->exec($sql);
        return [
            'success' => true,
            'message' => 'Demo sandbox database restored to golden state successfully.',
            'restored_at' => date('Y-m-d H:i:s')
        ];
    } catch (\Throwable $e) {
        error_log("Demo DB Restore Failed: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// If invoked directly from CLI:
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Restoring demo database (modpyphp_demo)...\n";
    $result = restoreDemoDatabase();
    if ($result['success']) {
        echo "[SUCCESS] " . $result['message'] . " at " . $result['restored_at'] . "\n";
        exit(0);
    } else {
        echo "[ERROR] " . $result['error'] . "\n";
        exit(1);
    }
}
