<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Reports\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../../../../../../modules/Superadmin/Office/Services/OfficeService.php';
require_once __DIR__ . '/../Services/ReportService.php';
require_once __DIR__ . '/../../Machines/Services/MachineService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Reports\Services\ReportService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use Exception;

class ReportController extends BaseController {
    protected $reportService;
    protected $officeService;
    protected $machineService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->reportService = new ReportService();
        $this->officeService = new OfficeService();
        $this->machineService = new MachineService();
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

        $action = $_GET['action'] ?? 'index';

        try {
            switch ($action) {
                case 'index':
                    $this->showDashboard();
                    break;
                case 'getChartData':
                    $this->getChartData();
                    break;
                case 'getMachineLogData':
                    $this->getMachineLogData();
                    break;
                case 'getMachineAuditData':
                    $this->getMachineAuditData();
                    break;
                case 'calculateBackfill':
                    $this->calculateBackfill();
                    break;
                case 'getMonthwiseMachineStats':
                    $this->getMonthwiseMachineStats();
                    break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (\Throwable $e) {
            if ($this->isAjaxRequest()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /index.php');
            }
            exit;
        }
    }


    private function showDashboard(): void {
        $userRole = strtolower($this->currentUser['role']);
        $userOfficeId = (int)$this->currentUser['officeid'];
        $offices = [];

        if ($userRole === 'superuser') {
            $officeDTOs = $this->officeService->getAllOffices();
            foreach ($officeDTOs as $dto) {
                $offices[] = ['officeid' => $dto->Officeid, 'officename' => $dto->OfficeName];
            }
        }

        $machineOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machines = $this->machineService->getAllMachines($machineOfficeId);

        $this->render(__DIR__ . '/../Views/index.php', [
            'offices' => $offices,
            'machines' => $machines,
            'userRole' => $userRole,
            'currentUser' => $this->currentUser
        ]);
    }

    private function getChartData(): void {
        header('Content-Type: application/json');
        try {
            $userRole = strtolower($this->currentUser['role']);
            $userOfficeId = (int)$this->currentUser['officeid'];

            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $to = $_GET['to'] ?? date('Y-m-d');
            $fuelType = $_GET['fuel_type'] ?? 'all';

            $requestedOfficeId = isset($_GET['office_id']) && $_GET['office_id'] !== '' ? (int)$_GET['office_id'] : $userOfficeId;
            $officeIdToQuery = ($userRole === 'superuser') ? $requestedOfficeId : $userOfficeId;

            $data = $this->reportService->getDashboardData($officeIdToQuery, $from, $to, $fuelType);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function getMachineLogData(): void {
        header('Content-Type: application/json');
        try {
            $userRole = strtolower($this->currentUser['role']);
            $userOfficeId = (int)$this->currentUser['officeid'];

            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $to = $_GET['to'] ?? date('Y-m-d');
            $machineId = (int)($_GET['machine_id'] ?? 0);
            $displayMode = $_GET['display_mode'] ?? 'auto';

            if ($machineId <= 0) throw new Exception("Machine ID is required.");

            $requestedOfficeId = isset($_GET['office_id']) && $_GET['office_id'] !== '' ? (int)$_GET['office_id'] : $userOfficeId;
            $officeIdToQuery = ($userRole === 'superuser') ? $requestedOfficeId : $userOfficeId;

            $data = $this->reportService->getMachineLogData($officeIdToQuery, $machineId, $from, $to, $displayMode);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function getMachineAuditData(): void {
        header('Content-Type: application/json');
        try {
            $userRole = strtolower($this->currentUser['role']);
            $userOfficeId = (int)$this->currentUser['officeid'];

            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $to = $_GET['to'] ?? date('Y-m-d');
            $machineId = (int)($_GET['machine_id'] ?? 0);

            if ($machineId <= 0) throw new Exception("Machine ID is required.");

            $requestedOfficeId = isset($_GET['office_id']) && $_GET['office_id'] !== '' ? (int)$_GET['office_id'] : $userOfficeId;
            $officeIdToQuery = ($userRole === 'superuser') ? $requestedOfficeId : $userOfficeId;

            $data = $this->reportService->getMachineAuditData($officeIdToQuery, $machineId, $from, $to);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function calculateBackfill(): void {
        header('Content-Type: application/json');
        try {
            $userRole = strtolower($this->currentUser['role']);
            $userOfficeId = (int)$this->currentUser['officeid'];

            $machineId = (int)($_GET['machine_id'] ?? 0);
            $month = $_GET['month'] ?? date('Y-m'); // Expected YYYY-MM

            if ($machineId <= 0) throw new Exception("Machine ID is required.");

            $requestedOfficeId = isset($_GET['office_id']) && $_GET['office_id'] !== '' ? (int)$_GET['office_id'] : $userOfficeId;
            $officeIdToQuery = ($userRole === 'superuser') ? $requestedOfficeId : $userOfficeId;

            $data = $this->reportService->calculateBackfillData($officeIdToQuery, $machineId, $month);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function getMonthwiseMachineStats(): void {
        header('Content-Type: application/json');
        try {
            $userRole = strtolower($this->currentUser['role']);
            $userOfficeId = (int)$this->currentUser['officeid'];

            $machineId = (int)($_GET['machine_id'] ?? 0);
            if ($machineId <= 0) throw new Exception("Machine ID is required.");

            $requestedOfficeId = isset($_GET['office_id']) && $_GET['office_id'] !== '' ? (int)$_GET['office_id'] : $userOfficeId;
            $officeIdToQuery = ($userRole === 'superuser') ? $requestedOfficeId : $userOfficeId;

            $result = $this->reportService->getMonthwiseMachineStats($officeIdToQuery, $machineId);

            echo json_encode(['status' => 'success', 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$controller = new ReportController();
$controller->handleRequest();
