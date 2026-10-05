<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Servicing\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ServicingService.php';
require_once __DIR__ . '/../../Machines/Services/MachineService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Servicing\Services\ServicingService;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use Exception;

class ServicingController extends BaseController {
    private $servicingService;
    private $machineService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->servicingService = new ServicingService();
        $this->machineService = new MachineService();
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
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                    $this->listServicingLogs($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'create':
                    $this->createServicing($userRole, $userOfficeId);
                    break;
                case 'showEditForm':
                    $this->showEditForm($userRole, $userOfficeId);
                    break;
                case 'update':
                    $this->updateServicing($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteServicing($userRole, $userOfficeId);
                    break;
                case 'addCustomServiceType':
                    $this->addCustomServiceType();
                    break;
                default:
                    throw new Exception("Unknown action requested: " . htmlspecialchars($action));
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ?action=list');
            exit;
        }
    }

    private function listServicingLogs(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : null;

        $servicingLogs = $this->servicingService->getAllServicingLogs($officeId, $machineId);
        $machines = $this->machineService->getAllMachines($officeId);
        $dueStatuses = $this->servicingService->getMachineServiceDueStatus($officeId);

        // Stats
        $totalCost = array_sum(array_column($servicingLogs, 'cost'));
        $totalServices = count($servicingLogs);

        $this->render(__DIR__ . '/../Views/index.php', [
            'servicingLogs'  => $servicingLogs,
            'machines'       => $machines,
            'dueStatuses'    => $dueStatuses,
            'selectedMachine' => $machineId,
            'totalCost'      => $totalCost,
            'totalServices'  => $totalServices,
            'currentUser'    => $this->currentUser,
            'userRole'       => $userRole
        ]);
    }

    private function showCreateForm(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machines = $this->machineService->getAllMachines($officeId);

        $selectedMachineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : 0;

        $this->render(__DIR__ . '/../Views/create.php', [
            'machines'          => $machines,
            'selectedMachineId' => $selectedMachineId,
            'currentUser'       => $this->currentUser,
            'userRole'          => $userRole
        ]);
    }

    private function createServicing(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $submissionOfficeId = ($userRole === 'superuser') ? (int)($_POST['office_id'] ?? $userOfficeId) : $userOfficeId;

        $data = [
            'machine_id'       => (int)($_POST['machine_id'] ?? 0),
            'service_date'     => trim((string)($_POST['service_date'] ?? date('Y-m-d'))),
            'hours_at_service' => (float)($_POST['hours_at_service'] ?? 0),
            'next_service_due' => (float)($_POST['next_service_due'] ?? 100),
            'service_type'     => trim((string)($_POST['service_type'] ?? 'Routine Maintenance')),
            'serviced_by'      => !empty($_POST['serviced_by']) ? trim((string)$_POST['serviced_by']) : null,
            'cost'             => (isset($_POST['cost']) && $_POST['cost'] !== '') ? (float)$_POST['cost'] : null,
            'job_card_no'      => !empty($_POST['job_card_no']) ? trim((string)$_POST['job_card_no']) : null,
            'remarks'          => !empty($_POST['remarks']) ? trim((string)$_POST['remarks']) : null
        ];

        $machine = $this->machineService->getMachineById($data['machine_id'], ($userRole === 'superuser') ? null : $userOfficeId);
        $this->servicingService->createServicing($data, $submissionOfficeId, $machine);

        $_SESSION['message'] = 'Servicing record logged successfully!';
        header('Location: ?action=list');
        exit;
    }

    private function showEditForm(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $servicing = $this->servicingService->getServicingById($id, $officeId);
        if (!$servicing) {
            throw new Exception("Servicing record not found.");
        }

        $machines = $this->machineService->getAllMachines($officeId);

        $this->render(__DIR__ . '/../Views/edit.php', [
            'servicing'   => $servicing,
            'machines'    => $machines,
            'currentUser' => $this->currentUser,
            'userRole'    => $userRole
        ]);
    }

    private function updateServicing(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $id = (int)($_POST['id'] ?? 0);
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $data = [
            'machine_id'       => (int)($_POST['machine_id'] ?? 0),
            'service_date'     => trim((string)($_POST['service_date'] ?? date('Y-m-d'))),
            'hours_at_service' => (float)($_POST['hours_at_service'] ?? 0),
            'next_service_due' => (float)($_POST['next_service_due'] ?? 100),
            'service_type'     => trim((string)($_POST['service_type'] ?? 'Routine Maintenance')),
            'serviced_by'      => !empty($_POST['serviced_by']) ? trim((string)$_POST['serviced_by']) : null,
            'cost'             => (isset($_POST['cost']) && $_POST['cost'] !== '') ? (float)$_POST['cost'] : null,
            'job_card_no'      => !empty($_POST['job_card_no']) ? trim((string)$_POST['job_card_no']) : null,
            'remarks'          => !empty($_POST['remarks']) ? trim((string)$_POST['remarks']) : null
        ];

        $this->servicingService->updateServicing($id, $officeId, $data);

        $_SESSION['message'] = 'Servicing record updated successfully!';
        header('Location: ?action=list');
        exit;
    }

    private function addCustomServiceType(): void {
        header('Content-Type: application/json');
        try {
            $machineId = (int)($_POST['machine_id'] ?? 0);
            $newType = trim((string)($_POST['new_service_type'] ?? ''));

            if ($machineId <= 0 || $newType === '') {
                echo json_encode(['success' => false, 'message' => 'Machine and Service Type name are required.']);
                exit;
            }

            $types = $this->machineService->addCustomServiceType($machineId, $newType);
            echo json_encode(['success' => true, 'types' => $types]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function deleteServicing(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        if ($id <= 0) {
            throw new Exception("Invalid ID provided.");
        }

        $this->servicingService->deleteServicing($id, $officeId);

        $_SESSION['message'] = 'Servicing record deleted successfully!';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new ServicingController();
$controller->handleRequest();
