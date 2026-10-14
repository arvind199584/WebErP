<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Receipts\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ReceiptService.php';
require_once __DIR__ . '/../../InventoryItems/Services/InventoryItemService.php';
require_once __DIR__ . '/../../SpareParts/Services/SparePartService.php';
require_once __DIR__ . '/../../Machines/Services/MachineService.php';
require_once __DIR__ . '/../../../../../../modules/Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Receipts\Services\ReceiptService;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Services\InventoryItemService;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Services\SparePartService;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;

class ReceiptController extends BaseController {
    private $receiptService;
    private $inventoryItemService;
    private $sparePartService;
    private $machineService;
    private $officeService;

    public function __construct() {
        parent::__construct('Workshop/Machine/TurfManagement');
        $this->receiptService = new ReceiptService();
        $this->inventoryItemService = new InventoryItemService();
        $this->sparePartService = new SparePartService();
        $this->machineService = new MachineService();
        $this->officeService = new OfficeService();
    }

    public function handleRequest(): void {
        if (!$this->currentUser) {
            header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
            exit;
        }

        $action = $_GET['action'] ?? 'list';
        $userRole = strtolower($this->currentUser['role'] ?? '');
        $userOfficeId = (int)($this->currentUser['officeid'] ?? 0);

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                    $this->listReceipts($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'fetchSourceOptions':
                    $this->fetchSourceOptions($userRole, $userOfficeId);
                    break;
                case 'fetchSourceDetails':
                    $this->fetchSourceDetails();
                    break;
                case 'getVoucherDetails':
                    $this->getVoucherDetails($userRole, $userOfficeId);
                    break;
                case 'printVoucher':
                    $this->printVoucher($userRole, $userOfficeId);
                    break;
                case 'createBulkReceipt':
                    $this->createBulkReceipt($userRole, $userOfficeId);
                    break;
                case 'addCustomConsumableItem':
                    $this->addCustomConsumableItem($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteReceipt($userRole, $userOfficeId);
                    break;
                default:
                    throw new Exception("Unknown action requested: " . htmlspecialchars($action));
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            $this->handleException($e, $action, 'list');
        }
    }

    private function isAjaxRequest(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors') ||
               (strtolower($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') ||
               (isset($_GET['format']) && $_GET['format'] === 'json');
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

        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $itemsDTOs = $this->inventoryItemService->getAllItems();
        $inventoryItems = [];
        foreach ($itemsDTOs as $dto) {
            $inventoryItems[] = $dto->toArray();
        }

        $spareParts = $this->sparePartService->getAllSpareParts($officeId);
        $machines = $this->machineService->getAllMachines($officeId);
        $categories = $this->inventoryItemService->getAllCategories();
        $sourceOptions = $this->receiptService->getSourceOptions($officeId);

        $this->render(__DIR__ . '/../Views/create.php', [
            'inventoryItems' => $inventoryItems,
            'spareParts'     => $spareParts,
            'machines'       => $machines,
            'categories'     => $categories,
            'offices'        => $offices,
            'sourceOptions'  => $sourceOptions,
            'userRole'       => $userRole,
            'currentUser'    => $this->currentUser
        ]);
    }

    private function fetchSourceOptions(string $userRole, int $userOfficeId): void {
        $requestedOfficeId = isset($_GET['office_id']) ? (int)$_GET['office_id'] : $userOfficeId;
        $officeId = ($userRole === 'superuser' && $requestedOfficeId > 0) ? $requestedOfficeId : (($userRole === 'superuser') ? null : $userOfficeId);

        $options = $this->receiptService->getSourceOptions($officeId);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $options]);
        exit;
    }

    private function fetchSourceDetails(): void {
        $sourceType = trim((string)($_GET['source_type'] ?? ''));
        $sourceId = (int)($_GET['source_id'] ?? 0);

        if (empty($sourceType) || $sourceId <= 0) {
            throw new Exception("Source type and ID are required.");
        }

        $details = $this->receiptService->getSourceContractDetails($sourceType, $sourceId);
        if (!$details) {
            throw new Exception("Source record not found.");
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $details]);
        exit;
    }

    private function getVoucherDetails(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("Voucher ID is required.");

        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $voucher = $this->receiptService->getVoucherById($id, $officeId);

        if (!$voucher) throw new Exception("Voucher not found.");

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $voucher]);
        exit;
    }

    private function printVoucher(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("Voucher ID is required.");

        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $voucher = $this->receiptService->getVoucherById($id, $officeId);

        if (!$voucher) throw new Exception("Voucher not found.");

        $this->render(__DIR__ . '/../Views/print.php', [
            'voucher'     => $voucher,
            'currentUser' => $this->currentUser
        ]);
    }

    private function createBulkReceipt(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method. Expected POST.");
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) {
            throw new Exception("Invalid JSON payload.");
        }

        $masterData = $payload['masterData'] ?? [];
        $stagedItems = $payload['stagedItems'] ?? $payload['items'] ?? [];

        $submissionOfficeId = ($userRole === 'superuser') ? (int)($masterData['office_id'] ?? $masterData['officeid'] ?? 0) : $userOfficeId;

        if (empty($submissionOfficeId)) {
            throw new Exception("Office ID is required.");
        }

        $masterData['officeid'] = $submissionOfficeId;

        $result = $this->receiptService->processMultiRvReceipt($masterData, $stagedItems);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    private function addCustomConsumableItem(string $userRole, int $userOfficeId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method. Expected POST.");
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $description = trim((string)($input['description'] ?? ''));
        $acUnit = trim((string)($input['ac_unit'] ?? $input['unit'] ?? 'Pcs'));
        $categoryId = !empty($input['category_id']) ? (int)$input['category_id'] : null;
        $categoryCode = trim((string)($input['category_code'] ?? $input['store_category'] ?? ''));

        if ($description === '') {
            throw new Exception("Item description is required.");
        }
        if ($acUnit === '') {
            $acUnit = 'Pcs';
        }

        $db = \App\Core\Database::getInstance()->getConnection();

        // If categoryId is not provided, resolve from categoryCode
        if (empty($categoryId) && !empty($categoryCode)) {
            $catStmt = $db->prepare("SELECT id FROM turf_inventory_categories WHERE code = :code OR LOWER(name) LIKE :likeCode LIMIT 1");
            $catStmt->execute(['code' => $categoryCode, 'likeCode' => "%{$categoryCode}%"]);
            $resolvedCat = $catStmt->fetchColumn();
            if ($resolvedCat) {
                $categoryId = (int)$resolvedCat;
            }
        }

        // Check if an item with the same description already exists (case-insensitive)
        $checkStmt = $db->prepare("SELECT i.id, i.description, i.ac_unit, i.category_id, c.name as category_name, c.code as category_code 
                                  FROM turf_inventory_items i 
                                  LEFT JOIN turf_inventory_categories c ON i.category_id = c.id 
                                  WHERE LOWER(TRIM(i.description)) = LOWER(TRIM(:desc)) LIMIT 1");
        $checkStmt->execute(['desc' => $description]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'          => 'success',
                'message'         => "Item '{$existing['description']}' already exists in catalog and has been selected.",
                'item'            => $existing,
                'already_existed' => true
            ]);
            exit;
        }

        // Insert new consumable item into turf_inventory_items
        $insertSql = "INSERT INTO turf_inventory_items (description, ac_unit, category_id, created_at, updated_at) 
                      VALUES (:desc, :unit, :cat_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) 
                      RETURNING id";
        $stmt = $db->prepare($insertSql);
        $stmt->execute([
            'desc'   => $description,
            'unit'   => $acUnit,
            'cat_id' => $categoryId
        ]);
        $newId = (int)$stmt->fetchColumn();

        $catName = '';
        $catCode = '';
        if ($categoryId) {
            $catDetailsStmt = $db->prepare("SELECT name, code FROM turf_inventory_categories WHERE id = :id");
            $catDetailsStmt->execute(['id' => $categoryId]);
            $catRow = $catDetailsStmt->fetch(\PDO::FETCH_ASSOC);
            if ($catRow) {
                $catName = $catRow['name'];
                $catCode = $catRow['code'];
            }
        }

        $newItem = [
            'id'            => $newId,
            'description'   => $description,
            'ac_unit'       => $acUnit,
            'category_id'   => $categoryId,
            'category_name' => $catName,
            'category_code' => $catCode
        ];

        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'success',
            'message' => "Item '{$description}' added to " . ($catName ? "'{$catName}'" : "consumables") . " successfully!",
            'item'    => $newItem
        ]);
        exit;
    }

    private function deleteReceipt(string $userRole, int $userOfficeId): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("Voucher ID is required for deletion.");

        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $this->receiptService->deleteReceiptEntry($id, $whereOfficeId);

        $_SESSION['message'] = 'Receipt Voucher and associated ledger entries deleted successfully!';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new ReceiptController();
$controller->handleRequest();
