<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Repairs\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/RepairService.php';
require_once __DIR__ . '/../../Machines/Services/MachineService.php';
require_once __DIR__ . '/../../SpareParts/Services/SparePartService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Repairs\Services\RepairService;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Services\SparePartService;
use Exception;

class RepairController extends BaseController {
    private $repairService;
    private $machineService;
    private $sparePartService;

    public function __construct() {
        parent::__construct('Workshop/Machine/TurfManagement');
        $this->repairService = new RepairService();
        $this->machineService = new MachineService();
        $this->sparePartService = new SparePartService();
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
                    $this->listRepairs($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'create':
                    $this->createRepair($userRole, $userOfficeId);
                    break;
                case 'showEditForm':
                    $this->showEditForm($userRole, $userOfficeId);
                    break;
                case 'update':
                    $this->updateRepair($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteRepair($userRole, $userOfficeId);
                    break;
                case 'getSpareParts':
                    $this->getSparePartsByMachine($userRole, $userOfficeId);
                    break;
                case 'getMachineDetails':
                    $this->getMachineDetails($userRole, $userOfficeId);
                    break;
                case 'jobCards':
                    $this->viewJobCards($userRole, $userOfficeId);
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

    private function listRepairs(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : null;

        $repairs = $this->repairService->getAllRepairs($officeId, $machineId);
        $machines = $this->machineService->getAllMachines($officeId);

        $totalCost = array_sum(array_column($repairs, 'cost'));
        $totalRepairs = count($repairs);

        $this->render(__DIR__ . '/../Views/index.php', [
            'repairs'         => $repairs,
            'machines'        => $machines,
            'selectedMachine' => $machineId,
            'totalCost'       => $totalCost,
            'totalRepairs'    => $totalRepairs,
            'currentUser'     => $this->currentUser,
            'userRole'        => $userRole
        ]);
    }

    private function showCreateForm(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machines = $this->machineService->getAllMachines($officeId);
        $selectedMachineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : null;

        $this->render(__DIR__ . '/../Views/create.php', [
            'machines'          => $machines,
            'selectedMachineId' => $selectedMachineId,
            'currentUser'       => $this->currentUser,
            'userRole'          => $userRole
        ]);
    }

    private function createRepair(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $officeId = ($userRole === 'superuser') ? (int)($data['office_id'] ?? $userOfficeId) : $userOfficeId;

            $items = [];
            if (!empty($data['items_json'])) {
                $decoded = json_decode($data['items_json'], true);
                if (is_array($decoded)) {
                    $items = $decoded;
                }
            }

            $this->repairService->createRepair($data, $officeId, $items);

            $_SESSION['message'] = 'Machine repair work logged successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function showEditForm(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $repair = $this->repairService->getRepairById($id, $officeId);
        if (!$repair) {
            throw new Exception("Repair entry not found.");
        }

        $machines = $this->machineService->getAllMachines($officeId);

        $this->render(__DIR__ . '/../Views/edit.php', [
            'repair'      => $repair,
            'machines'    => $machines,
            'currentUser' => $this->currentUser,
            'userRole'    => $userRole
        ]);
    }

    private function updateRepair(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $data = $_POST;
            $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

            $this->repairService->updateRepair($id, $officeId, $data);

            $_SESSION['message'] = 'Machine repair log updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function getSparePartsByMachine(string $userRole, int $userOfficeId): void {
        header('Content-Type: application/json');
        try {
            $machineId = (int)($_GET['machine_id'] ?? 0);
            $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

            if ($machineId <= 0) {
                echo json_encode(['success' => false, 'parts' => []]);
                exit;
            }

            $parts = $this->sparePartService->getSparePartsByMachine($machineId, $officeId);
            echo json_encode(['success' => true, 'parts' => $parts]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'parts' => []]);
        }
        exit;
    }

    private function getMachineDetails(string $userRole, int $userOfficeId): void {
        header('Content-Type: application/json');
        try {
            $machineId = (int)($_GET['machine_id'] ?? 0);
            $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

            if ($machineId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid machine ID.']);
                exit;
            }

            $machine = $this->machineService->getMachineById($machineId, $officeId);
            if (!$machine) {
                echo json_encode(['success' => false, 'message' => 'Machine not found.']);
                exit;
            }

            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT MAX(running_hours) FROM turf_consumption_log WHERE machine_id = :id");
            $stmt->execute(['id' => $machineId]);
            $currentMeter = $stmt->fetchColumn();

            $parts = $this->sparePartService->getSparePartsByMachine($machineId, $officeId);

            echo json_encode([
                'success' => true,
                'machine' => [
                    'id'                     => $machine['id'],
                    'name'                   => $machine['name'],
                    'make'                   => $machine['make'],
                    'runduration'            => (bool)$machine['runduration'],
                    'current_meter'          => $currentMeter !== false ? (float)$currentMeter : null,
                    'service_interval_hours' => (float)($machine['service_interval_hours'] ?? 100),
                    'service_done'           => (float)($machine['service_done'] ?? 0),
                    'service_due'            => (float)($machine['service_due'] ?? 100),
                    'engine_oil_qty'         => (float)($machine['engine_oil_qty'] ?? 0.00),
                    'custom_work_types'      => json_decode($machine['custom_work_types'] ?? '[]', true) ?: [],
                    'default_service_items'  => json_decode($machine['default_service_items'] ?? '[]', true) ?: []
                ],
                'parts'   => $parts
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function viewJobCards(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $filters = [
            'search'     => $_GET['search'] ?? '',
            'machine_id' => !empty($_GET['machine_id']) ? (int)$_GET['machine_id'] : null,
            'work_type'  => $_GET['work_type'] ?? '',
            'from_date'  => $_GET['from_date'] ?? '',
            'to_date'    => $_GET['to_date'] ?? ''
        ];

        $jobCards = $this->repairService->getJobCardsSummary($officeId, $filters);
        $machines = $this->machineService->getAllMachines($officeId);

        $totalJobCards = count($jobCards);
        $totalCost = array_sum(array_column($jobCards, 'cost'));
        $totalPartsIssued = array_sum(array_column($jobCards, 'total_parts_quantity'));

        $this->render(__DIR__ . '/../Views/job_cards.php', [
            'jobCards'         => $jobCards,
            'machines'         => $machines,
            'filters'          => $filters,
            'totalJobCards'    => $totalJobCards,
            'totalCost'        => $totalCost,
            'totalPartsIssued' => $totalPartsIssued,
            'currentUser'      => $this->currentUser,
            'userRole'         => $userRole
        ]);
    }

    private function deleteRepair(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $this->repairService->deleteRepair($id, $officeId);

        $_SESSION['message'] = 'Repair log entry deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

// Controller instantiation for direct execution
$controller = new RepairController();
$controller->handleRequest();
