<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Machines\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/MachineService.php';
require_once __DIR__ . '/../../InventoryItems/Services/InventoryItemService.php';
require_once __DIR__ . '/../../SpareParts/Services/SparePartService.php';
require_once __DIR__ . '/../../../../../../modules/Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Services\InventoryItemService;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Services\SparePartService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;

class MachineController extends BaseController {
    protected $machineService;
    protected $inventoryItemService;
    protected $sparePartService;
    protected $officeService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->machineService = new MachineService();
        $this->inventoryItemService = new InventoryItemService();
        $this->sparePartService = new SparePartService();
        $this->officeService = new OfficeService();
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
                    $this->listMachines($userRole, $userOfficeId);
                    break;
                case 'status':
                    $this->showMachineStatus($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'showEditForm':
                    $this->showEditForm($userRole, $userOfficeId);
                    break;
                case 'create':
                    $this->createMachine($userRole, $userOfficeId);
                    break;
                case 'update':
                    $this->updateMachine($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteMachine($userRole, $userOfficeId);
                    break;
                case 'getOfficeMachines':
                    $this->getOfficeMachines($userRole, $userOfficeId);
                    break;
                default:
                    throw new Exception("Unknown action requested: " . htmlspecialchars($action));
            }
        } catch (Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(500);
                echo "Error: " . htmlspecialchars($e->getMessage());
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?action=list');
            }
            exit;
        }
    }

    private function listMachines(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $machines = $this->machineService->getAllMachines($officeId);
        $this->render(__DIR__ . '/../Views/index.php', [
            'machines' => $machines,
            'currentUser' => $this->currentUser
        ]);
    }

    private function showMachineStatus(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        if ($userRole === 'superuser' && isset($_GET['office_id']) && $_GET['office_id'] !== '') {
            $officeId = (int)$_GET['office_id'];
        }

        $threshold = isset($_GET['threshold']) && is_numeric($_GET['threshold']) ? (float)$_GET['threshold'] : 20.0;
        $statusFilter = $_GET['status_filter'] ?? 'alerts'; // 'alerts', 'overdue', 'due_soon', 'good', 'all'

        $machines = $this->machineService->getMachinesWithServiceStatus($officeId, $threshold);

        $countOverdue = 0;
        $countDueSoon = 0;
        $countGood = 0;
        $countNoData = 0;

        foreach ($machines as $m) {
            if ($m['status_key'] === 'overdue') $countOverdue++;
            elseif ($m['status_key'] === 'due_soon') $countDueSoon++;
            elseif ($m['status_key'] === 'good') $countGood++;
            else $countNoData++;
        }

        $totalAlerts = $countOverdue + $countDueSoon;

        $offices = [];
        if ($userRole === 'superuser') {
            $officeDTOs = $this->officeService->getAllOffices();
            foreach ($officeDTOs as $dto) {
                $offices[] = ['officeid' => $dto->Officeid, 'officename' => $dto->OfficeName];
            }
        }

        $this->render(__DIR__ . '/../Views/status.php', [
            'machines'       => $machines,
            'countOverdue'   => $countOverdue,
            'countDueSoon'   => $countDueSoon,
            'countGood'      => $countGood,
            'countNoData'    => $countNoData,
            'totalAlerts'    => $totalAlerts,
            'totalFleet'     => count($machines),
            'statusFilter'   => $statusFilter,
            'threshold'      => $threshold,
            'selectedOffice' => $officeId,
            'offices'        => $offices,
            'currentUser'    => $this->currentUser,
            'userRole'       => $userRole
        ]);
    }

    private function showCreateForm(string $userRole, int $userOfficeId): void {
        $offices = [];
        if ($userRole === 'superuser') {
            $officeDTOs = $this->officeService->getAllOffices();
            foreach ($officeDTOs as $dto) {
                $offices[] = ['officeid' => $dto->Officeid, 'officename' => $dto->OfficeName];
            }
        }

        $inventoryItemsDTOs = $this->inventoryItemService->getFuelItems();
        $inventoryItems = array_map(fn($dto) => $dto->toArray(), $inventoryItemsDTOs);

        $this->render(__DIR__ . '/../Views/create.php', [
            'offices' => $offices,
            'inventoryItems' => $inventoryItems,
            'userRole' => $userRole
        ], false);
    }

    private function showEditForm(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $machine = $this->machineService->getMachineById($id, $whereOfficeId);
        if (!$machine) throw new Exception("Machine not found.");

        $offices = [];
        if ($userRole === 'superuser') {
            $officeDTOs = $this->officeService->getAllOffices();
            foreach ($officeDTOs as $dto) {
                $offices[] = ['officeid' => $dto->Officeid, 'officename' => $dto->OfficeName];
            }
        }

        $inventoryItemsDTOs = $this->inventoryItemService->getFuelItems();
        $inventoryItems = array_map(fn($dto) => $dto->toArray(), $inventoryItemsDTOs);

        $targetOfficeId = ($userRole === 'superuser') ? (int)($machine['officeid'] ?? $userOfficeId) : $userOfficeId;
        $spareParts = $this->sparePartService->getAllSpareParts($targetOfficeId);

        // Prioritize parts assigned specifically to this machine first, then alphabetical
        usort($spareParts, function($a, $b) use ($id) {
            $aMatch = ($a['machine_id'] == $id) ? 0 : 1;
            $bMatch = ($b['machine_id'] == $id) ? 0 : 1;
            if ($aMatch !== $bMatch) {
                return $aMatch <=> $bMatch;
            }
            return strcasecmp($a['nomenclature'] ?? '', $b['nomenclature'] ?? '');
        });

        $this->render(__DIR__ . '/../Views/edit.php', [
            'machine' => $machine,
            'offices' => $offices,
            'inventoryItems' => $inventoryItems,
            'spareParts' => $spareParts,
            'userRole' => $userRole
        ], false);
    }

    private function createMachine(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submissionOfficeId = ($userRole === 'superuser') ? (int)($_POST['office_id'] ?? 0) : $userOfficeId;
            if (empty($submissionOfficeId)) throw new Exception("Office ID is required.");

            $data = $_POST;
            $data['officeid'] = $submissionOfficeId;

            $this->machineService->createMachine($data);

            $_SESSION['message'] = 'Machine created successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function updateMachine(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $submissionOfficeId = ($userRole === 'superuser') ? (int)($_POST['office_id'] ?? 0) : $userOfficeId;
            if (empty($submissionOfficeId)) throw new Exception("Office ID is required.");

            $data = $_POST;
            if (isset($_POST['service_items_json'])) {
                $decoded = json_decode($_POST['service_items_json'], true);
                if (is_array($decoded)) {
                    $data['default_service_items'] = $decoded;
                }
            }

            $this->machineService->updateMachine($id, $submissionOfficeId, $data);

            $_SESSION['message'] = 'Machine updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function deleteMachine(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $this->machineService->deleteMachine($id, $whereOfficeId);

        $_SESSION['message'] = 'Machine deleted successfully!';
        header('Location: ?action=list');
        exit;
    }

    private function getOfficeMachines(string $userRole, int $userOfficeId): void {
        header('Content-Type: application/json');
        try {
            $officeId = ($userRole === 'superuser') ? (int)($_GET['office_id'] ?? 0) : $userOfficeId;
            $machines = $this->machineService->getAllMachines($officeId);
            echo json_encode(['success' => true, 'machines' => $machines]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$controller = new MachineController();
$controller->handleRequest();
