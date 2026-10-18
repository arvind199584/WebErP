<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "Connected to database successfully.\n";

    $tables = [
        'dsr_2023.sql',
        'civil_global_rates.sql',
        'dar_item_breakups.sql',
        'civil_estimates.sql',
        'civil_estimate_items.sql',
        'civil_justifications.sql'
    ];

    foreach ($tables as $sqlFile) {
        $filePath = __DIR__ . '/../../schema/tables/' . $sqlFile;
        if (!file_exists($filePath)) {
            echo "Skipping $sqlFile (not found)\n";
            continue;
        }
        $sql = file_get_contents($filePath);
        $db->exec($sql);
        echo "Executed $sqlFile successfully.\n";
    }

    echo "\nAll Civil tables created successfully!\n";

} catch (\Throwable $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
    exit(1);
}
