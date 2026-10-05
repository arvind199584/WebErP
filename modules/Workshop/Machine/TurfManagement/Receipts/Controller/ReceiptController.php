<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Receipts\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ReceiptService.php';
require_once __DIR__ . '/../../InventoryItems/Services/InventoryItemService.php';
require_once __DIR__ . '/../../SpareParts/Services/SparePartService.php';
require_once __DIR__ . '/../../Machines/Services/MachineService.php';
require_once __DIR__ . '/../../../../../../modules/Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Core\Database;
use App\Modules\Workshop\Machine\TurfManagement\Receipts\Services\ReceiptService;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Services\InventoryItemService;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Services\SparePartService;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;
use PDO;

class ReceiptController extends BaseController {
    private $receiptService;
    private $inventoryItemService;
    private $sparePartService;
    private $machineService;
    private $officeService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->receiptService = new ReceiptService();
        $this->inventoryItemService = new InventoryItemService();
        $this->sparePartService = new SparePartService();
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
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                    $this->listReceipts($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'createBulkReceipt':
                    $this->createBulkReceipt($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteReceipt($userRole, $userOfficeId);
                    break;
                default:
                    throw new Exception("Unknown action requested.");
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

    private function listReceipts(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $vouchers = $this->receiptService->getReceiptVouchers($officeId);

        $this->render(__DIR__ . '/../Views/index.php', [
            'vouchers' => $vouchers,
            'currentUser' => $this->currentUser
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

        $itemsDTOs = $this->inventoryItemService->getAllItems();
        $inventoryItems = [];
        foreach($itemsDTOs as $dto) {
             $inventoryItems[] = $dto->toArray();
        }

        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $spareParts = $this->sparePartService->getAllSpareParts($officeId);
        $machines = $this->machineService->getAllMachines($officeId);
        $categories = $this->inventoryItemService->getAllCategories();

        $this->render(__DIR__ . '/../Views/create.php', [
            'inventoryItems' => $inventoryItems,
            'spareParts'     => $spareParts,
            'machines'       => $machines,
            'categories'     => $categories,
            'offices'        => $offices,
            'userRole'       => $userRole,
            'currentUser'    => $this->currentUser
        ]);
    }

    private function createBulkReceipt(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $masterData = $payload['masterData'] ?? [];
        $items = $payload['items'] ?? [];

        $submissionOfficeId = ($userRole === 'superuser') ? (int)($masterData['office_id'] ?? 0) : $userOfficeId;

        if (empty($submissionOfficeId)) {
            throw new Exception("Office ID is required.");
        }

        $masterData['officeid'] = $submissionOfficeId;

        $this->receiptService->processBulkReceipt($masterData, $items);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Receipt Voucher created successfully!']);
        exit;
    }

    private function deleteReceipt(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("Entry ID is required for deletion.");

        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $this->receiptService->deleteReceiptEntry($id, $whereOfficeId);

        $_SESSION['message'] = 'Receipt entry deleted successfully!';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new ReceiptController();
$controller->handleRequest();
