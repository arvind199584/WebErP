<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Indents\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/IndentService.php';
require_once __DIR__ . '/../../InventoryItems/Services/InventoryItemService.php';
require_once __DIR__ . '/../../../../../../modules/Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Indents\Services\IndentService;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Services\InventoryItemService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;
use PDO;

class IndentController extends BaseController {
    private $indentService;
    private $inventoryItemService;
    private $officeService;

    public function __construct() {
        parent::__construct('TurfManagement');
        $this->indentService = new IndentService();
        $this->inventoryItemService = new InventoryItemService();
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
                    $this->listIndents($userRole, $userOfficeId);
                    break;
                case 'showCreateForm':
                    $this->showCreateForm($userRole, $userOfficeId);
                    break;
                case 'createBulkIndent':
                    $this->createBulkIndent($userRole, $userOfficeId);
                    break;
                case 'delete':
                    $this->deleteIndent($userRole, $userOfficeId);
                    break;
                case 'viewDetails':
                    $this->viewDetails($userRole, $userOfficeId);
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

    private function listIndents(string $userRole, int $userOfficeId): void {
        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $vouchers = $this->indentService->getIndentVouchers($officeId);

        $this->render(__DIR__ . '/../Views/index.php', [
            'vouchers' => $vouchers,
            'currentUser' => $this->currentUser
        ]);
    }

    private function viewDetails(string $userRole, int $userOfficeId): void {
        $voucherNo = $_GET['voucher_no'] ?? null;
        if (!$voucherNo) throw new Exception("Voucher number is required.");

        $officeId = ($userRole === 'superuser') ? null : $userOfficeId;
        $items = $this->indentService->getVoucherItems($voucherNo, $officeId);

        // Render snippet for modal
        $this->render(__DIR__ . '/../Views/details.php', [
            'voucher_no' => $voucherNo,
            'items' => $items
        ], false);
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

        $this->render(__DIR__ . '/../Views/create.php', [
            'inventoryItems' => $inventoryItems,
            'offices' => $offices,
            'userRole' => $userRole,
            'currentUser' => $this->currentUser
        ]);
    }

    private function createBulkIndent(string $userRole, int $userOfficeId): void {
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

        $this->indentService->processBulkIndent($masterData, $items);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Indent Voucher created successfully!']);
        exit;
    }

    private function deleteIndent(string $userRole, int $userOfficeId): void {
        $voucherNo = $_GET['voucher_no'] ?? '';
        if (empty($voucherNo)) throw new Exception("Voucher number is required for deletion.");

        $whereOfficeId = ($userRole === 'superuser') ? null : $userOfficeId;

        $this->indentService->deleteIndentVoucher($voucherNo, $whereOfficeId);

        $_SESSION['message'] = 'Indent Voucher deleted successfully!';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new IndentController();
$controller->handleRequest();
