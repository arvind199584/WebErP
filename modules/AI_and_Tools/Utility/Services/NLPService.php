<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\Utility\Services;

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Superadmin/Users/Services/UserService.php';

use App\Core\Database;
use App\Modules\Superadmin\Users\Services\UserService;
use PDO;
use GuzzleHttp\Client;

class NLPService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function processQuery(string $userInput): array {
        @set_time_limit(120); // Extend execution limit for LLM generation
        // 1. Check Knowledge Base First (Exact Match)
        $kbSql = "SELECT sql_query FROM nlp_knowledge_base WHERE lower(question_text) = :q LIMIT 1";
        $stmt = $this->db->prepare($kbSql);
        $stmt->execute(['q' => strtolower(trim($userInput))]);
        $kbResult = $stmt->fetchColumn();

        if ($kbResult) {
            return $this->processSqlResult($kbResult, [], 'Knowledge Base Match');
        }

        // 2. Fetch Schema Metadata to help the AI
        $schema = $this->getDatabaseSchema();

        // 3. Call Flask API with Schema Context
        $client = new Client(['verify' => false]);
        try {
            $response = $client->post('https://127.0.0.1:5000/nlp', [
                'json' => [
                    'query' => $userInput,
                    'schema' => $schema
                ],
                'timeout' => 90.0
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['error'])) {
                return ['type' => 'text', 'data' => $result['error']];
            }

            if (!empty($result['sql'])) {
                return $this->processSqlResult($result['sql'], $result['params'] ?? [], $result['predicted_intent'] ?? 'AI Generated');
            }

        } catch (\Exception $e) {
            return ['type' => 'text', 'data' => 'Error: Could not connect to Python API. ' . $e->getMessage()];
        }

        return ['type' => 'text', 'data' => 'No query generated.'];
    }

    private function getDatabaseSchema(): array {
        $sql = "SELECT table_name, column_name
                FROM information_schema.columns
                WHERE table_schema = 'public'
                AND table_name NOT IN ('nlp_knowledge_base', 'migrations')";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $schema = [];
        foreach ($rows as $row) {
            $schema[$row['table_name']][] = $row['column_name'];
        }
        return $schema;
    }

    private function processSqlResult(string $sql, array $params, string $intent): array {
        $currentUser = UserService::getCurrentUser();
        $isSuperUser = ($currentUser && $currentUser['role'] === 'superuser');
        $isLocal = $this->isLocalhost();

        if (!($isSuperUser && $isLocal)) {
            if (!$this->isQuerySafe($sql)) {
                return ['type' => 'text', 'data' => 'Security Error: Only read-only queries are permitted.'];
            }
        }

        $data = $this->executeQuery($sql, $params);

        // Determine if result is a single value or a table
        $type = (count($data) === 1 && count($data[0]) === 1) ? 'text' : 'table';

        if ($type === 'text') {
            $val = array_values($data[0])[0];
            $displayVal = is_numeric($val) ? number_format((float)$val, 2) : $val;
            return [
                'type' => 'text',
                'data' => "Result: " . ($displayVal ?? '0'),
                'predicted_intent' => $intent
            ];
        } else {
            return [
                'type' => 'table',
                'title' => 'Query Result',
                'data' => $data,
                'predicted_intent' => $intent
            ];
        }
    }

    public function saveTrainingExample(string $text, string $sql): void {
        $sqlInsert = "INSERT INTO nlp_knowledge_base (question_text, sql_query) VALUES (:text, :sql)
                      ON CONFLICT (question_text) DO UPDATE SET sql_query = EXCLUDED.sql_query";
        $stmt = $this->db->prepare($sqlInsert);
        $stmt->execute([
            'text' => strtolower(trim($text)),
            'sql' => $sql
        ]);
    }

    private function isLocalhost(): bool {
        $whitelist = ['127.0.0.1', '::1'];
        return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
    }

    private function isQuerySafe(string $sql): bool {
        $sql = trim(strtoupper($sql));
        if (strpos($sql, 'SELECT') !== 0) return false;
        $forbidden = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'REPLACE', 'GRANT', 'REVOKE', 'EXEC', 'CALL', 'UNION', '--', ';'];
        foreach ($forbidden as $word) {
            if (preg_match("/\b" . preg_quote($word) . "\b/", $sql)) return false;
        }
        return true;
    }

    private function executeQuery(string $sql, array $params = []): array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            if (stripos(trim($sql), 'SELECT') !== 0) {
                return [['Rows Affected' => $stmt->rowCount()]];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Database Error in NLPService: " . $e->getMessage());
            return [['Error' => 'An error occurred while executing the query.']];
        }
    }
}
