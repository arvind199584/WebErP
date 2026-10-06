<?php
/**
 * Service to calculate diffs between Local PostgreSQL & Cloud Neon DB
 * and allow Superusers to Commit or Reject uncommitted cloud mutations.
 */

declare(strict_types=1);

namespace App\Modules\Superadmin\ActivityLog\Services;

use App\Core\Database;
use PDO;
use Exception;

class CloudSyncDiffService
{
    private PDO $localDb;
    private string $neonDsn = "";
    private string $neonUser = "";
    private string $neonPass = "";

    private array $tablesToAudit = [
        'attendance_records', 'turf_consumption_log', 'water_log', 'bills',
        'wage_orders', 'wages_ledger', 'agreements', 'employees', 'users', 'office', 'budget', 'aa_es'
    ];

    public function __construct()
    {
        $this->localDb = Database::getInstance()->getConnection();

        $neonUrl = getenv('NEON_DATABASE_URL') ?: getenv('DATABASE_URL');
        if (!empty($neonUrl)) {
            $parsed = parse_url($neonUrl);
            $host = $parsed['host'] ?? 'ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech';
            $port = $parsed['port'] ?? '5432';
            $dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'neondb';
            $user = $parsed['user'] ?? 'neondb_owner';
            $pass = $parsed['pass'] ?? '';
            $sslmode = 'require';
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $q);
                if (isset($q['sslmode'])) $sslmode = $q['sslmode'];
            }
            $this->neonDsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
            $this->neonUser = $user;
            $this->neonPass = $pass;
        } else {
            $neonHost = getenv('NEON_HOST') ?: 'ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech';
            $neonPort = getenv('NEON_PORT') ?: '5432';
            $neonDb   = getenv('NEON_DB') ?: 'neondb';
            $this->neonDsn = "pgsql:host={$neonHost};port={$neonPort};dbname={$neonDb};sslmode=require";
            $this->neonUser = getenv('NEON_USER') ?: 'neondb_owner';
            $this->neonPass = getenv('NEON_PASS') ?: '';
        }
    }

    private function getNeonConnection(): PDO
    {
        $pdo = new PDO($this->neonDsn, $this->neonUser, $this->neonPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    /**
     * Compare Neon DB vs Local DB records and return detailed table diffs
     */
    public function getCloudDiffs(): array
    {
        $diffs = [];
        $neonDb = $this->getNeonConnection();

        foreach ($this->tablesToAudit as $table) {
            // Check if table exists in local DB
            $chk = $this->localDb->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = :tbl");
            $chk->execute(['tbl' => $table]);
            if (!$chk->fetchColumn()) {
                continue;
            }

            // Get columns common to both DBs
            $getCols = function($pdo, $tbl) {
                $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :tbl");
                $stmt->execute(['tbl' => $tbl]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            };

            $localCols = $getCols($this->localDb, $table);
            $neonCols = $getCols($neonDb, $table);
            $commonCols = array_intersect($localCols, $neonCols);

            if (empty($commonCols)) continue;

            // Fetch Neon Rows
            $stmtNeon = $neonDb->query("SELECT * FROM \"$table\"");
            $neonRows = $stmtNeon->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Local Rows by ID
            $stmtLocal = $this->localDb->query("SELECT * FROM \"$table\"");
            $localRows = $stmtLocal->fetchAll(PDO::FETCH_ASSOC);
            $localMap = [];
            foreach ($localRows as $lr) {
                $localMap[$lr['id']] = $lr;
            }

            $tableDiffs = [];

            foreach ($neonRows as $nRow) {
                $id = $nRow['id'];
                if (!isset($localMap[$id])) {
                    // New record inserted on Cloud!
                    $tableDiffs[] = [
                        'type' => 'NEW_INSERT',
                        'table' => $table,
                        'id' => $id,
                        'cloud_data' => $nRow,
                        'local_data' => null,
                        'changed_fields' => array_keys($nRow)
                    ];
                } else {
                    // Exists in both: compare field by field
                    $lRow = $localMap[$id];
                    $changedFields = [];
                    foreach ($commonCols as $col) {
                        $nVal = $nRow[$col] ?? null;
                        $lVal = $lRow[$col] ?? null;
                        if ((string)$nVal !== (string)$lVal) {
                            $changedFields[$col] = [
                                'cloud' => $nVal,
                                'local' => $lVal
                            ];
                        }
                    }

                    if (!empty($changedFields)) {
                        $tableDiffs[] = [
                            'type' => 'MODIFIED',
                            'table' => $table,
                            'id' => $id,
                            'cloud_data' => $nRow,
                            'local_data' => $lRow,
                            'changed_fields' => $changedFields
                        ];
                    }
                }
            }

            if (!empty($tableDiffs)) {
                $diffs[$table] = $tableDiffs;
            }
        }

        return $diffs;
    }

    /**
     * Commit (approve) cloud record changes into Local PostgreSQL
     */
    public function commitChange(string $table, int $id): bool
    {
        if (!in_array($table, $this->tablesToAudit)) {
            throw new Exception("Invalid table name.");
        }

        $neonDb = $this->getNeonConnection();
        $stmt = $neonDb->prepare("SELECT * FROM \"$table\" WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $cloudRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cloudRow) {
            throw new Exception("Record not found in Neon cloud.");
        }

        // Get local columns
        $stmtCols = $this->localDb->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :tbl");
        $stmtCols->execute(['tbl' => $table]);
        $localCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

        $validCols = array_intersect(array_keys($cloudRow), $localCols);
        $quotedCols = array_map(fn($c) => '"' . $c . '"', $validCols);
        $placeholders = array_map(fn($c) => ':' . $c, $validCols);

        $updatePairs = [];
        foreach ($validCols as $col) {
            if ($col !== 'id') {
                $updatePairs[] = '"' . $col . '" = EXCLUDED."' . $col . '"';
            }
        }

        $sql = "INSERT INTO \"$table\" (" . implode(', ', $quotedCols) . ") 
                VALUES (" . implode(', ', $placeholders) . ") 
                ON CONFLICT (id) DO UPDATE SET " . implode(', ', $updatePairs);

        $params = [];
        foreach ($validCols as $col) {
            $val = $cloudRow[$col];
            if ($val === '') $val = null;
            $params[$col] = $val;
        }

        $stmtLocal = $this->localDb->prepare($sql);
        return $stmtLocal->execute($params);
    }

    /**
     * Cancel (reject) cloud record change: removes or reverts it on Neon to align back to Local
     */
    public function cancelChange(string $table, int $id): bool
    {
        if (!in_array($table, $this->tablesToAudit)) {
            throw new Exception("Invalid table name.");
        }

        $neonDb = $this->getNeonConnection();

        // Check local record
        $stmtLocal = $this->localDb->prepare("SELECT * FROM \"$table\" WHERE id = :id");
        $stmtLocal->execute(['id' => $id]);
        $localRow = $stmtLocal->fetch(PDO::FETCH_ASSOC);

        if (!$localRow) {
            // Local doesn't have it (it was a bad INSERT on Cloud): DELETE from Neon
            $delStmt = $neonDb->prepare("DELETE FROM \"$table\" WHERE id = :id");
            return $delStmt->execute(['id' => $id]);
        } else {
            // Local has original record: REVERT Neon back to Local version
            $getCols = $neonDb->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :tbl");
            $getCols->execute(['tbl' => $table]);
            $neonCols = $getCols->fetchAll(PDO::FETCH_COLUMN);

            $validCols = array_intersect(array_keys($localRow), $neonCols);
            $setPairs = [];
            $params = ['id' => $id];

            foreach ($validCols as $col) {
                if ($col !== 'id') {
                    $setPairs[] = '"' . $col . '" = :' . $col;
                    $val = $localRow[$col];
                    if ($val === '') $val = null;
                    $params[$col] = $val;
                }
            }

            $sql = "UPDATE \"$table\" SET " . implode(', ', $setPairs) . " WHERE id = :id";
            $updStmt = $neonDb->prepare($sql);
            return $updStmt->execute($params);
        }
    }

    /**
     * Backup Local PostgreSQL Database using pg_dump
     */
    public function createLocalBackup(): array
    {
        $backupDir = __DIR__ . '/../../../../storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $filename = 'modpyphp_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        // Path to pg_dump
        $pgDumpBin = 'C:\\Program Files\\PostgreSQL\\18\\bin\\pg_dump.exe';
        if (!file_exists($pgDumpBin)) {
            $pgDumpBin = 'pg_dump'; // fallback to PATH
        }

        $connUri = 'postgresql://postgres:daredevil@localhost:5432/modpyphp';
        $cmd = sprintf('"%s" "%s" --clean --if-exists --no-owner --no-acl -F p -f "%s"', $pgDumpBin, $connUri, $filepath);

        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception("Local DB backup failed: " . implode("\n", $output));
        }

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * List all available local backup .sql files
     */
    public function listLocalBackups(): array
    {
        $backupDir = __DIR__ . '/../../../../storage/backups';
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*.sql');
        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        usort($backups, fn($a, $b) => strcmp($b['filename'], $a['filename']));
        return $backups;
    }

    /**
     * Restore Local PostgreSQL Database from a specified SQL backup file
     */
    public function restoreLocalBackup(string $filename): bool
    {
        $backupDir = __DIR__ . '/../../../../storage/backups';
        $filepath = realpath($backupDir . '/' . basename($filename));

        if (!$filepath || !file_exists($filepath)) {
            throw new Exception("Backup file not found.");
        }

        $psqlBin = 'C:\\Program Files\\PostgreSQL\\18\\bin\\psql.exe';
        if (!file_exists($psqlBin)) {
            $psqlBin = 'psql';
        }

        $connUri = 'postgresql://postgres:daredevil@localhost:5432/modpyphp';
        $cmd = sprintf('"%s" "%s" -f "%s"', $psqlBin, $connUri, $filepath);

        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Database restore failed: " . implode("\n", $output));
        }

        return true;
    }

    /**
     * Re-upload / Refresh entire dataset over Neon DB from Local PostgreSQL
     */
    public function refreshNeonFromLocal(): array
    {
        // Execute python migration script
        $scriptPath = __DIR__ . '/../../../../scripts/migration/migrate_to_neon.py';
        if (!file_exists($scriptPath)) {
            throw new Exception("Migration script not found.");
        }

        $cmd = 'python "' . $scriptPath . '"';
        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Neon refresh failed:\n" . implode("\n", $output));
        }

        return [
            'status' => 'success',
            'output' => implode("\n", $output)
        ];
    }
}

