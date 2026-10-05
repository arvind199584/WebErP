<?php
namespace App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/InventoryItemService.php';
require_once __DIR__ . '/../DTO/InventoryItemDTO.php';
require_once __DIR__ . '/../../SpareParts/Services/SparePartService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Services\InventoryItemService;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\DTO\InventoryItemDTO;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Services\SparePartService;
use Exception;

class InventoryItemController extends BaseController {
    private $inventoryItemService;
    private $sparePartService;

    public function __construct() {
        parent::__construct('Workshop/Machine/TurfManagement');
        $this->inventoryItemService = new InventoryItemService();
        $this->sparePartService = new SparePartService();
    }

    public function handleRequest(): void {
        if (!$this->currentUser) {
            header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
            exit;
        }

        $action = $_GET['action'] ?? 'list';

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                    $this->listItems();
                    break;
                case 'showCreateForm':
                    $this->showCreateForm();
                    break;
                case 'create':
                    $this->createItem();
                    break;
                case 'showEditForm':
                    $this->showEditForm();
                    break;
                case 'update':
                    $this->updateItem();
                    break;
                case 'delete':
                    $this->deleteItem();
                    break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ?action=list');
            exit;
        }
    }

    private function listItems(): void {
        $itemDTOs = $this->inventoryItemService->getAllItems();

        // Translate DTOs to simple arrays for the View
        $items = [];
        foreach ($itemDTOs as $dto) {
            $items[] = $dto->toArray();
        }

        // Fetch Machine Spare Parts from turf_machine_spare_parts table
        $userRole = strtolower($this->currentUser['role'] ?? 'user');
        $officeId = ($userRole === 'superuser') ? null : (int)($this->currentUser['officeid'] ?? 2);
        $spareParts = $this->sparePartService->getAllSpareParts($officeId);

        $this->render(__DIR__ . '/../Views/index.php', [
            'items'      => $items,
            'spareParts' => $spareParts,
            'currentUser' => $this->currentUser
        ]);
    }

    private function showCreateForm(): void {
        $categories = $this->inventoryItemService->getAllCategories();
        $this->render(__DIR__ . '/../Views/create.php', [
            'categories' => $categories
        ]);
    }

    private function createItem(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
            $dto = new InventoryItemDTO(
                $_POST['description'] ?? '',
                $_POST['ac_unit'] ?? '',
                null,
                $categoryId
            );

            $this->inventoryItemService->createItem($dto);

            $_SESSION['message'] = 'Inventory item created successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function showEditForm(): void {
        $id = (int)($_GET['id'] ?? 0);
        $dto = $this->inventoryItemService->getItemById($id);

        if (!$dto) {
            throw new Exception("Inventory item not found.");
        }

        $categories = $this->inventoryItemService->getAllCategories();

        $this->render(__DIR__ . '/../Views/edit.php', [
            'item' => $dto->toArray(),
            'categories' => $categories
        ]);
    }

    private function updateItem(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
            $dto = new InventoryItemDTO(
                $_POST['description'] ?? '',
                $_POST['ac_unit'] ?? '',
                (int)($_POST['id'] ?? 0),
                $categoryId
            );

            $this->inventoryItemService->updateItem($dto);

            $_SESSION['message'] = 'Inventory item updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function deleteItem(): void {
        $id = (int)($_GET['id'] ?? 0);
        $this->inventoryItemService->deleteItem($id);

        $_SESSION['message'] = 'Inventory item deleted successfully!';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new InventoryItemController();
$controller->handleRequest();
