<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Logs\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/LogService.php';
require_once __DIR__ . '/../../Machines/Services/MachineService.php';
require_once __DIR__ . '/../../../../../../modules/Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Logs\Services\LogService;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;

class LogController extends BaseController {
    private $logService;
    private $machineService;
    private $officeService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->logService = new LogService();
        $this->machineService = new MachineService();
        $this->officeService = new OfficeService();
    }

    private function isAjaxRequest(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors' ||
               strtolower($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
    }

    public function handleRequest(): void {
        if (!$this->currentUser) {
            header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
            exit;
        }

        $action = $_GET['action'] ?? 'list';
        $userRole = strtolower($this->currentUser['role']);
        $userOfficeId = (int)$this->currentUser['officeid'];

        try {
            // Handle AJAX actions first
            if ($action === 'getFormData') {
                $this->checkPermission($action);
                $this->getFormData($userRole, $userOfficeId);
                return;
            }
            if ($action === 'validateStagedLog') {
                $this->checkPermission($action);
                $this->validateStagedLog($userRole, $userOfficeId);
                return;
            }
            if ($action === 'createLogs') {
                $this->checkPermission($action);
                $this->createLogs($userRole, $userOfficeId);
                return;
            }
            if ($action === 'auditLogs') {
                $this->checkPermission($action);
                $this->auditLogs($userRole, $userOfficeId);
                return;
            }
            if ($action === 'applyAuditFixes') {
                $this->checkPermission($action);
                $this->applyAuditFixes($userRole, $userOfficeId);
                return;
            }

            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                    $this->listLogs($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'createLogs':
                    $this->createLogs($userRole, $userOfficeId);
                    break;
                case 'update':
                    $this->updateLog($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteLog($userRole, $userOfficeId);
                    break;
                case 'editByDate':
                    $this->editLogsByDate($userRole, $userOfficeId);
                    break;
                case 'showAuditScreen':
                    $this->showAuditScreen($userRole, $userOfficeId);
                    break;
                default:
                    throw new Exception("Unknown action requested: " . htmlspecialchars($action));
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => $e->getMessage()]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?action=list');
            }
            exit;
        }
    }

    private function getFormData(string $userRole, int $userOfficeId): void {
        header('Content-Type: application/json');
        try {
            $machineOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;
            $machines = $this->machineService->getAllMachines($machineOfficeId);

            echo json_encode([
                'status' => 'success',
                'machines' => $machines
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function validateStagedLog(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        $submissionOfficeId = ($userRole === 'superuser') ? (int)($payload['office_id'] ?? 0) : $userOfficeId;
        if (empty($submissionOfficeId)) {
            throw new Exception("Office ID is required.");
        }

        $logData = $payload['log'] ?? [];
        if (empty($logData)) {
            throw new Exception("No log data provided.");
        }

        // Validate the log without inserting it
        $this->logService->validateSingleStagedLog($logData, $submissionOfficeId);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Log is valid.']);
        exit;
    }

    private function listLogs(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $availableMonths = $this->logService->getAvailableMonths($officeId);
        
        // Default to 'trends' if no month parameter is explicitly provided in the URL
        $selectedMonth = $_GET['month'] ?? 'trends';

        $logs = [];
        $trendsData = [];

        if ($selectedMonth === 'trends') {
            $trendsData = $this->logService->getMonthlyTrendsData($officeId);
        } else {
            $logs = $this->logService->getAllLogs($officeId, $selectedMonth);
        }

        $this->render(__DIR__ . '/../Views/index.php', [
            'logs' => $logs,
            'currentUser' => $this->currentUser,
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $availableMonths,
            'trendsData' => $trendsData
        ]);
    }

    private function showCreateForm(string $userRole, int $userOfficeId): void {
        $offices = [];

        if ($userRole === 'superuser') {
            $officeDTOs = $this->officeService->getAllOffices();
            foreach ($officeDTOs as $dto) {
                $offices[] = [
                    'officeid' => $dto->Officeid,
                    'officename' => $dto->OfficeName
                ];
            }
        }

        $machines = $this->machineService->getAllMachines(null);

        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $latest = $this->logService->getLatestLogDate($officeId);
        if ($latest) {
            $defaultDate = date('Y-m-d', strtotime($latest . ' +1 day'));
        } else {
            $defaultDate = date('Y-m-d');
        }

        $existingLogsMap = $this->logService->getExistingLogsMap($officeId);

        $this->render(__DIR__ . '/../Views/create.php', [
            'machines' => $machines,
            'offices' => $offices,
            'userRole' => $userRole,
            'currentUser' => $this->currentUser,
            'defaultDate' => $defaultDate,
            'existingLogsMap' => $existingLogsMap
        ]);
    }

    private function createLogs(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method. Only POST is allowed for saving logs.");
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $logs = $payload['logs'] ?? [];

        $submissionOfficeId = ($userRole === 'superuser') ? (int)($payload['office_id'] ?? 0) : $userOfficeId;

        if (empty($submissionOfficeId)) {
            throw new Exception("Office ID is required.");
        }
        if (empty($logs)) {
            throw new Exception("No logs were provided to save.");
        }

        // Use the new, efficient bulk insert method
        $count = $this->logService->createBulkLogs($logs, $submissionOfficeId);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => "$count log(s) created successfully!"]);
        exit;
    }

    private function updateLog(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $id = (int)($_POST['id'] ?? 0);
        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;

        if ($id <= 0) {
            throw new Exception("Invalid Log ID provided for update.");
        }

        $data = [
            'machine_id'        => $_POST['machine_id'] ?? null,
            'operator'          => !empty($_POST['operator']) ? ucwords(strtolower(trim((string)$_POST['operator']))) : null,
            'fuel_consumed_qty' => !empty($_POST['fuel_consumed_qty']) ? (float)$_POST['fuel_consumed_qty'] : null,
            'running_hours'     => !empty($_POST['running_hours']) ? (float)$_POST['running_hours'] : null,
            'log_date'          => $_POST['log_date'] ?? null
        ];

        $this->logService->updateLogQuick($id, $whereOfficeId, $data);

        $_SESSION['message'] = 'Log updated successfully!';
        $redirectUrl = $_POST['redirect_to'] ?? '?action=list';
        header('Location: ' . $redirectUrl);
        exit;
    }

    private function deleteLog(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;

        if ($id <= 0) {
            throw new Exception("Invalid Log ID provided for deletion.");
        }

        $this->logService->deleteLog($id, $whereOfficeId);

        $_SESSION['message'] = 'Log deleted successfully!';
        $redirectUrl = $_GET['redirect_to'] ?? '?action=list';
        header('Location: ' . $redirectUrl);
        exit;
    }

    private function editLogsByDate(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $selectedDate = $_GET['date'] ?? null;

        $logs = [];
        if (!empty($selectedDate)) {
            $logs = $this->logService->getLogsByDate($officeId, $selectedDate);
        }

        $recentDates = $this->logService->getRecentDatesWithLogs($officeId);

        $this->render(__DIR__ . '/../Views/edit.php', [
            'logs' => $logs,
            'currentUser' => $this->currentUser,
            'selectedDate' => $selectedDate,
            'recentDates' => $recentDates,
            'userRole' => $userRole
        ]);
    }

    // --- AUDIT MODULE CONTROLLER METHODS ---

    private function showAuditScreen(string $userRole, int $userOfficeId): void {
        $offices = [];
        if ($userRole === 'superuser') {
            $officeDTOs = $this->officeService->getAllOffices();
            foreach ($officeDTOs as $dto) {
                $offices[] = ['officeid' => $dto->Officeid, 'officename' => $dto->OfficeName];
            }
        }

        $machineOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machines = $this->machineService->getAllMachines($machineOfficeId);

        $this->render(__DIR__ . '/../Views/audit.php', [
            'machines' => $machines,
            'offices' => $offices,
            'userRole' => $userRole,
            'currentUser' => $this->currentUser
        ]);
    }

    private function auditLogs(string $userRole, int $userOfficeId): void {
        header('Content-Type: application/json');
        try {
            $machineId = (int)($_GET['machine_id'] ?? 0);
            if ($machineId <= 0) {
                throw new Exception("Valid Machine ID is required.");
            }

            $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;
            $anomalies = $this->logService->findMachineAnomalies($machineId, $whereOfficeId);

            echo json_encode(['status' => 'success', 'data' => $anomalies]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function applyAuditFixes(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        header('Content-Type: application/json');
        try {
            $payload = json_decode(file_get_contents('php://input'), true);
            $fixes = $payload['fixes'] ?? [];

            if (empty($fixes)) {
                throw new Exception("No fixes provided.");
            }

            $successCount = 0;
            foreach ($fixes as $fix) {
                if (isset($fix['id']) && isset($fix['new_hours'])) {
                    $this->logService->applyAuditFix((int)$fix['id'], (float)$fix['new_hours']);
                    $successCount++;
                }
            }

            echo json_encode(['status' => 'success', 'message' => "Successfully applied $successCount corrections."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$controller = new LogController();
$controller->handleRequest();
