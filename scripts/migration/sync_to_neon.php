<?php
/**
 * Non-blocking background sync script to replicate Local PostgreSQL -> Neon Cloud DB
 */
set_time_limit(300);
header('Content-Type: application/json');

$response = ['status' => 'success', 'synced' => []];

try {
    // Load .env and .env.neon if available
    $loadEnv = function(string $filepath): void {
        if (file_exists($filepath)) {
            $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                if (getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                }
            }
        }
    };
    $loadEnv(__DIR__ . '/../../.env');
    $loadEnv(__DIR__ . '/../../.env.neon');

    // 1. Connect to Local DB
    $locHost = getenv('DB_HOST') ?: 'localhost';
    $locPort = getenv('DB_PORT') ?: '5432';
    $locDb   = getenv('DB_NAME') ?: 'modpyphp';
    $locUser = getenv('DB_USER') ?: 'postgres';
    $locPass = getenv('DB_PASS') ?: '';

    $localPdo = new PDO("pgsql:host={$locHost};port={$locPort};dbname={$locDb};sslmode=disable", $locUser, $locPass);
    $localPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    @$localPdo->exec("DEALLOCATE ALL");

    // 2. Connect to Neon Cloud DB
    $neonUrl = getenv('NEON_DATABASE_URL') ?: getenv('DATABASE_URL');
    if ($neonUrl) {
        $p = parse_url($neonUrl);
        $neonHost = $p['host'] ?? 'localhost';
        $neonDb = isset($p['path']) ? ltrim($p['path'], '/') : 'neondb';
        $neonUser = isset($p['user']) ? urldecode($p['user']) : 'neondb_owner';
        $neonPass = isset($p['pass']) ? urldecode($p['pass']) : '';
    } else {
        $neonHost = getenv('NEON_HOST');
        $neonDb = getenv('NEON_DB') ?: 'neondb';
        $neonUser = getenv('NEON_USER') ?: 'neondb_owner';
        $neonPass = getenv('NEON_PASS') ?: '';
    }

    if (!$neonHost) {
        throw new RuntimeException("NEON_DATABASE_URL or NEON_HOST is not set.");
    }
    $neonPdo = new PDO("pgsql:host={$neonHost};port=5432;dbname={$neonDb};sslmode=require", $neonUser, $neonPass);
    $neonPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    @$neonPdo->exec("DEALLOCATE ALL");

    $tablesToSync = [
        'tenants', 'mutual_funds', 'stock_prices', 'historical_prices', 'pending_training', 'nlp_knowledge_base',
        'registry_enums', 'registry_tables', 'registry_procedures', 'registry_triggers', 'registry_constraints',
        'office', 'users', 'budget', 'opening_balances', 'aa_es', 'agencies', 'wage_items', 'tm', 'tm_rates',
        'membership_sequences', 'members', 'dependents', 'mf_holdings', 'agreements', 'employees', 'attendance_records',
        'wage_orders', 'wages_ledger', 'epf_ledger', 'esic_ledger', 'work_orders', 'supply_orders', 'bills',
        'hand_receipts', 'address_book', 'internal_emails', 'turf_inventory_categories', 'turf_inventory_items',
        'turf_machines', 'turf_inventory_ledger', 'turf_consumption_log', 'water_log', 'gc_items', 'gc_machinery',
        'gc_main_stock_transactions', 'gc_sub_ledger_consumption', 'activity_logs'
    ];

    foreach ($tablesToSync as $table) {
        // Get target column names for Local & Neon
        $getCols = function($pdo, $tbl) {
            $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :tbl");
            $stmt->execute(['tbl' => $tbl]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        };

        $localCols = $getCols($localPdo, $table);
        $neonCols = $getCols($neonPdo, $table);

        // --- 1. PUSH: Local -> Neon ---
        $stmtLocal = $localPdo->query("SELECT * FROM \"$table\"");
        $localRows = $stmtLocal->fetchAll(PDO::FETCH_ASSOC);

        // Fetch existing IDs from Neon in 1 single roundtrip
        $neonExistingIds = array_flip($neonPdo->query("SELECT id FROM \"$table\"")->fetchAll(PDO::FETCH_COLUMN));

        $missingRows = [];
        foreach ($localRows as $row) {
            if (!isset($neonExistingIds[$row['id']])) {
                $missingRows[] = $row;
            }
        }

        $pushedCount = 0;
        if (!empty($missingRows)) {
            $validCols = array_intersect(array_keys($missingRows[0]), $neonCols);
            $quotedCols = array_map(fn($c) => '"' . $c . '"', $validCols);

            // Insert in chunks of 500 rows per roundtrip
            $chunks = array_chunk($missingRows, 500);
            foreach ($chunks as $chunk) {
                $valueClauses = [];
                $params = [];
                foreach ($chunk as $row) {
                    $filteredRow = array_intersect_key($row, array_flip($validCols));
                    $rowPlaceholders = [];
                    foreach ($validCols as $col) {
                        $rowPlaceholders[] = '?';
                        $val = $filteredRow[$col] ?? null;
                        if ($val === '') {
                            $val = null;
                        }
                        $params[] = $val;
                    }
                    $valueClauses[] = '(' . implode(', ', $rowPlaceholders) . ')';
                }

                $bulkSql = 'INSERT INTO "' . $table . '" (' . implode(', ', $quotedCols) . ') VALUES ' . implode(', ', $valueClauses) . ' ON CONFLICT (id) DO NOTHING';
                $stmt = $neonPdo->prepare($bulkSql);
                $stmt->execute($params);
                $pushedCount += count($chunk);
            }

            if (in_array('id', $validCols)) {
                try {
                    $neonPdo->exec("SELECT setval('{$table}_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"{$table}\"))");
                } catch (Exception $e) {}
            }
        }

        $response['synced'][$table] = [
            'pushed_to_cloud' => $pushedCount
        ];
    }

    $response['message'] = "Sync completed successfully!";
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
