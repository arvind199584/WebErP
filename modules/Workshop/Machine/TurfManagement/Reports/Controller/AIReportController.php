<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Reports\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ReportService.php';
require_once __DIR__ . '/../Models/ReportModel.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Reports\Services\ReportService;
use Exception;
use PDOException;

class AIReportController extends BaseController {
    protected $reportService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->reportService = new ReportService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';

        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $officeId = $_SESSION['office_id'] ?? 1;
            $userRole = strtolower($this->currentUser['role'] ?? 'guest');

            if ($this->isAjaxRequest() && $action === 'executeQuery') {
                $this->executeQuery($officeId, $userRole);
                return;
            }

            $this->checkPermission($action);

            switch ($action) {
                case 'index':
                    $this->showReportForm();
                    break;
                default:
                    throw new Exception("Unknown page action requested.");
            }
        } catch (Exception $e) {
            // Handle errors
        }
    }

    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function showReportForm(): void {
        $this->render(__DIR__ . '/../Views/ai_report.php');
    }

    private function executeQuery(int $officeId, string $userRole): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid request method.");
            $postData = json_decode(file_get_contents('php://input'), true);
            $sql = $postData['sql'] ?? null;
            $params = $postData['params'] ?? [];
            if (!$sql) throw new Exception("No SQL query provided.");

            if ($userRole !== 'superuser') {
                if (stripos($sql, 'WHERE') === false) {
                    $sql .= " WHERE officeid = :officeid";
                } else {
                    $sql .= " AND officeid = :officeid";
                }
                $params['officeid'] = $officeId;
            }

            $results = $this->reportService->executeAdHocQuery($sql, $params);
            echo json_encode(['status' => 'success', 'data' => $results]);
        } catch (PDOException $e) {
            error_log("Database query exception in AIReportController: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database Query Failed. Please contact the administrator.']);
        } catch (Exception $e) {
            error_log("General exception in AIReportController: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

$controller = new AIReportController();
$controller->handleRequest();
