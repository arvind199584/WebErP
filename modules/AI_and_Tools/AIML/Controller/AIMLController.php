<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIML\Controller;

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AIMLService.php';
require_once __DIR__ . '/../Services/AIMLSchemaService.php';

use App\Core\BaseController;
use App\Modules\AI_and_Tools\AIML\Services\AIMLService;
use App\Modules\AI_and_Tools\AIML\Services\AIMLSchemaService;
use Exception;

class AIMLController extends BaseController {
    private AIMLService $service;

    public function __construct() {
        parent::__construct('AI_and_Tools/AIML');
        $schemaService = new AIMLSchemaService();
        $schemaService->initSchema();
        $this->service = new AIMLService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index': $this->index(); break;
                case 'ask': $this->ask(); break;
                case 'training_hub': $this->trainingHub(); break;
                case 'resolve_training': $this->resolveTraining(); break;
                case 'normalize_sql_ajax': $this->normalizeSqlAjax(); break;
                case 'get_tables_ajax': $this->getTablesAjax(); break; // New
                case 'get_columns_ajax': $this->getColumnsAjax(); break; // New
                case 'report_wrong_ajax': $this->reportWrongAjax(); break; // New
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            echo "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    private function index(): void {
        $this->render(__DIR__ . '/../Views/index.php', ['response' => null, 'query' => '']);
    }

    private function ask(): void {
        $query = $_POST['query'] ?? '';
        $response = null;
        if ($query) {
            $response = $this->service->processQuery($query, (int)$this->currentUser['id']);
        }
        $this->render(__DIR__ . '/../Views/index.php', ['response' => $response, 'query' => $query]);
    }

    private function trainingHub(): void {
        $pending = $this->service->getPendingTraining();
        $this->render(__DIR__ . '/../Views/training_hub.php', ['pending' => $pending]);
    }

    private function resolveTraining(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $text = $_POST['text'];

            // Handle No-Code Builder
            if (isset($_POST['builder_table'])) {
                $table = $_POST['builder_table'];
                $cols = isset($_POST['builder_cols']) ? implode(', ', $_POST['builder_cols']) : '*';
                $cond = $_POST['builder_condition'] ?? '';

                $sql = "SELECT $cols FROM $table";
                if ($cond) {
                    $sql .= " WHERE $cond";
                }
            } else {
                // Manual SQL
                $sql = $_POST['sql'];
            }

            $this->service->resolveTraining($id, $text, $sql);
            $_SESSION['message'] = "AI trained successfully!";
        }
        header("Location: ?action=training_hub");
        exit;
    }

    private function normalizeSqlAjax(): void {
        header('Content-Type: application/json');
        $fuzzySql = $_GET['sql'] ?? '';
        $exactSql = $this->service->normalizeSql($fuzzySql);
        echo json_encode(['exact_sql' => $exactSql]);
        exit;
    }

    private function getTablesAjax(): void {
        header('Content-Type: application/json');
        $schema = $this->service->getDatabaseSchema();
        echo json_encode(array_keys($schema));
        exit;
    }

    private function getColumnsAjax(): void {
        header('Content-Type: application/json');
        $table = $_GET['table'] ?? '';
        $schema = $this->service->getDatabaseSchema();
        echo json_encode($schema[$table] ?? []);
        exit;
    }

    private function reportWrongAjax(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $query = $_POST['query'] ?? '';
            $sql = $_POST['sql'] ?? '';
            if ($query) {
                $logPayload = [
                    'question' => $query,
                    'level_1' => "Flagged as WRONG by user. Generated SQL: " . $sql,
                    'level_2' => "User reviewed and flagged output as incorrect."
                ];
                $logQueryStr = json_encode($logPayload);
                $this->service->logForTraining($logQueryStr, (int)$this->currentUser['id']);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
}

$controller = new AIMLController();
$controller->handleRequest();
