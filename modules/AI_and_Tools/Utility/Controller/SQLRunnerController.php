<?php
namespace App\Modules\AI_and_Tools\Utility\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../../../../core/Database.php';

use App\Core\BaseController;
use App\Core\Database;
use Exception;
use PDOException;

class SQLRunnerController extends BaseController {

    public function __construct() {
        parent::__construct('AI_and_Tools/Utility');
    }

    public function handleRequest(): void {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (strtolower($this->currentUser['role'] ?? '') !== 'superuser') {
                throw new Exception("Access Denied: This feature is for superusers only.");
            }

            $action = $_GET['action'] ?? 'index';

            switch ($action) {
                case 'index':
                    $this->showRunner();
                    break;
                case 'execute':
                    $this->executeQuery();
                    break;
                default:
                    throw new Exception("Unknown page action requested.");
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['error' => 'Controller Error: ' . $e->getMessage()]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /index.php');
            }
            exit;
        }
    }

    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function showRunner(): void {
        $this->render(__DIR__ . '/../Views/sql_runner.php');
    }

    private function executeQuery(): void {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method.");
            }

            $postData = json_decode(file_get_contents('php://input'), true);
            $sql = $postData['sql'] ?? '';

            $trimmedSql = strtolower(trim($sql));
            if (strpos($trimmedSql, 'select') !== 0) {
                throw new Exception("Security Error: Only SELECT queries are allowed.");
            }

            // Prevent multiple queries / stacked queries
            if (strpos(rtrim($trimmedSql, ';'), ';') !== false) {
                throw new Exception("Security Error: Stacked queries (using semicolons) are not allowed.");
            }

            // Strict blacklist for SQL Injection functions / destructive keywords
            $forbidden = [
                'DROP', 'TRUNCATE', 'ALTER', 'GRANT', 'REVOKE', 'UPDATE', 'DELETE', 'INSERT', 
                'UNION', '--', '/*', '*/', 'EXEC', 'PG_SLEEP', 'COPY', 'VACUUM', 'ANALYZE',
                'INFORMATION_SCHEMA', 'PG_CATALOG', 'CURRENT_SETTING', 'PG_LS_DIR'
            ];
            foreach ($forbidden as $word) {
                if (preg_match("/\b" . preg_quote($word, '/') . "\b/", strtoupper($trimmedSql))) {
                    throw new Exception("Security Error: Forbidden keyword/construct detected.");
                }
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $results]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

$controller = new SQLRunnerController();
$controller->handleRequest();
