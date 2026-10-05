<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIML\Services;

require_once __DIR__ . '/../../../../vendor/autoload.php';
use App\Core\Database;
use PDO;
use GuzzleHttp\Client;

class AIMLService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function processQuery(string $userInput, int $userId): array {
        @set_time_limit(120); // Extend execution limit for LLM generation
        $schema = $this->getDatabaseSchema();
        $client = new Client(['verify' => false]);
        try {
            $response = $client->post('https://127.0.0.1:5000/nlp', [
                'json' => ['query' => $userInput, 'schema' => $schema],
                'timeout' => 90.0
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            
            // Handle Structured Action (like INSERT)
            if (!empty($result['action_type']) && $result['action_type'] === 'INSERT') {
                return $this->handleInsertAction($result);
            }

            if (!empty($result['sql'])) {
                if ($this->isSqlSafe($result['sql'])) { return $this->executeSql($result['sql'], $result['params'] ?? []); }
                else { return ['type' => 'text', 'data' => 'Security Error: This action is not permitted.']; }
            }
            $this->logForTraining($result['log_query'] ?? $userInput, $userId);
            return ['type' => 'text', 'data' => "I'm still learning. I've sent your question to the administrator for training!"];
        } catch (\Exception $e) { 
            error_log("AIML Service Exception: " . $e->getMessage());
            return ['type' => 'text', 'data' => 'Error: AI Brain is offline. Detail: ' . $e->getMessage()]; 
        }
    }

    private function handleInsertAction(array $result): array {
        $table = $result['target_table'];
        $data = $result['data'] ?? [];
        if (empty($table) || empty($data)) return ['type' => 'text', 'data' => 'Data Entry Error: Missing table or data.'];

        try {
            $schema = $this->getDatabaseSchema();
            
            // 1. Validate Table Name (Whitelist)
            if (!isset($schema[$table])) {
                throw new \Exception("Security Error: Table '{$table}' is not recognized.");
            }

            // 2. Validate and Quote Column Names
            $validColumns = $schema[$table];
            $colsToInsert = [];
            $placeholders = [];
            $filteredData = [];

            foreach ($data as $col => $val) {
                if (in_array($col, $validColumns)) {
                    $colsToInsert[] = "\"$col\""; // Quote identifier for safety
                    $placeholders[] = ":$col";
                    $filteredData[$col] = $val;
                }
            }

            if (empty($colsToInsert)) {
                throw new \Exception("Data Entry Error: No valid columns provided for table '{$table}'.");
            }

            $sql = "INSERT INTO \"$table\" (" . implode(', ', $colsToInsert) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($filteredData);
            return ['type' => 'text', 'data' => "Successfully added record to " . ucwords(str_replace('_', ' ', $table)) . "!"];
        } catch (\Exception $e) {
            error_log("Database Error in AIMLService::handleInsertAction: " . $e->getMessage());
            return ['type' => 'text', 'data' => 'Entry Error: Failed to add record due to a database error.'];
        }
    }

    public function normalizeSql(string $fuzzySql): string {
        $schema = $this->getDatabaseSchema();
        $client = new Client(['verify' => false]);
        try {
            $response = $client->post('https://127.0.0.1:5000/normalize-sql', [
                'json' => ['sql' => $fuzzySql, 'schema' => $schema]
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['exact_sql'] ?? $fuzzySql;
        } catch (\Exception $e) {
            return $fuzzySql;
        }
    }

    public function isSqlSafe(string $sql): bool {
        if (empty(trim($sql))) return false;

        // Step 0: normalise — collapse whitespace, upper-case for scanning
        $sqlStripped    = trim($sql);
        $sqlNormalised  = strtoupper(preg_replace('/\s+/', ' ', $sqlStripped));

        // Step 1: must start with SELECT
        if (strpos($sqlNormalised, 'SELECT') !== 0) {
            return false;
        }

        // Step 2: literal forbidden patterns (no word boundary needed)
        $forbiddenPatterns = [
            ';',        // statement stacking
            '--',       // SQL line comment
            '/*',       // block comment open
            '*/',       // block comment close
            '$$',       // dollar-quote (PL/pgSQL injection)
            "\x00",     // null byte
            '\\x',      // hex-escape evasion
            'CHR(',     // chr() encoding trick
            'CHAR(',    // char() encoding trick
        ];
        foreach ($forbiddenPatterns as $pattern) {
            if (strpos($sqlNormalised, $pattern) !== false) {
                return false;
            }
        }

        // Step 3: whole-word keyword blacklist
        $forbiddenKeywords = [
            // DML
            'INSERT', 'UPDATE', 'DELETE', 'UPSERT', 'MERGE',
            // DDL
            'DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'REPLACE',
            // Privileges
            'GRANT', 'REVOKE',
            // Execution / flow control
            'EXEC', 'EXECUTE', 'CALL', 'PERFORM', 'DO',
            'DECLARE', 'RAISE',
            // Dangerous set operators
            'UNION', 'INTERSECT', 'EXCEPT',
            // Server-side operations
            'COPY', 'VACUUM', 'ANALYZE', 'REINDEX', 'CLUSTER',
            // Dangerous PG functions
            'PG_SLEEP', 'PG_READ_FILE', 'PG_STAT_FILE',
            'PG_LS_DIR', 'PG_EXECUTE', 'PG_RELOAD_CONF',
            'PG_ROTATE_LOGFILE', 'PG_TERMINATE_BACKEND',
            'PG_CANCEL_BACKEND', 'PG_ADVISORY_LOCK',
            'LO_IMPORT', 'LO_EXPORT', 'LO_READ', 'LO_WRITE',
            'DBLINK', 'DBLINK_EXEC',
            'CURRENT_SETTING', 'SET_CONFIG',
            // Catalog / metadata access
            'INFORMATION_SCHEMA', 'PG_CATALOG',
            'PG_CLASS', 'PG_SHADOW', 'PG_AUTHID', 'PG_USER',
            'PG_ROLES', 'PG_STAT_ACTIVITY',
            // Transaction manipulation
            'COMMIT', 'ROLLBACK', 'SAVEPOINT',
        ];
        foreach ($forbiddenKeywords as $kw) {
            if (preg_match('/(?<!\w)' . preg_quote($kw, '/') . '(?!\w)/', $sqlNormalised)) {
                return false;
            }
        }

        // Step 4: sensitive table guard
        $sensitiveTables = [
            'USERS', 'PENDING_TRAINING', 'NLP_KNOWLEDGE_BASE',
            'ACTIVITY_LOG', 'VECTOR_KB',
            'PG_SHADOW', 'PG_AUTHID',
        ];
        foreach ($sensitiveTables as $table) {
            if (preg_match('/(?<!\w)' . preg_quote($table, '/') . '(?!\w)/', $sqlNormalised)) {
                return false;
            }
        }

        // Step 5: max length guard
        if (strlen($sqlStripped) > 4000) {
            return false;
        }

        return true;
    }

    private function executeSql(string $sql, array $params): array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['type' => (count($data) === 1 && count($data[0]) === 1) ? 'text' : 'table', 'data' => $data, 'sql' => $sql];
        } catch (\Exception $e) {
            error_log("Database Error in AIMLService::executeSql: " . $e->getMessage());
            return ['type' => 'text', 'data' => 'Data Error: ' . $e->getMessage() . ' | SQL: ' . $sql];
        }
    }

    public function logForTraining(string $query, int $userId): void {
        $sql = "INSERT INTO pending_training (user_query, asked_by) VALUES (:q, :u)";
        $this->db->prepare($sql)->execute(['q' => $query, 'u' => $userId]);
    }

    public function getPendingTraining(): array {
        return $this->db->query("SELECT * FROM pending_training WHERE is_resolved = FALSE ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resolveTraining(int $id, string $text, string $sql): void {
        if (!$this->isSqlSafe($sql)) {
            throw new \Exception("Security Error: The provided SQL for training is not safe.");
        }
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("INSERT INTO nlp_knowledge_base (question_text, sql_query) VALUES (:t, :s) ON CONFLICT (question_text) DO UPDATE SET sql_query = EXCLUDED.sql_query");
        $stmt->execute(['t' => strtolower($text), 's' => $sql]);
        $this->db->prepare("UPDATE pending_training SET is_resolved = TRUE WHERE id = :id")->execute(['id' => $id]);
        $this->db->commit();
    }

    // Changed to PUBLIC so Controller can access it
    public function getDatabaseSchema(): array {
        $sql = "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = 'public'";
        $schema = [];
        foreach ($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $schema[$row['table_name']][] = $row['column_name'];
        }
        return $schema;
    }
}
