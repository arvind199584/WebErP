<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/WageItemService.php';
require_once __DIR__ . '/../DTO/WageItemDTO.php';

use App\Core\BaseController;
use App\Modules\Superadmin\WagesRates\Services\WageItemService;
use App\Modules\Superadmin\WagesRates\DTO\WageItemDTO;
use Exception;
use PDOException;

class WageItemController extends BaseController
{
    private WageItemService $wageItemService;

    public function __construct()
    {
        parent::__construct('Superadmin/WagesRates');
        $this->wageItemService = new WageItemService();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'list';

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'showEditForm': $this->showEditForm(); break;
                case 'update': $this->update(); break;
                case 'delete': $this->delete(); break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $redirectAction = $this->currentUser ? 'list' : 'login';
            header("Location: ?action=$redirectAction");
            exit;
        }
    }

    private function list(): void
    {
        // Default to today if no date is provided
        $selectedDate = $_GET['date'] ?? date('Y-m-d');

        // Fetch items with calculated rates for the selected date
        $items = $this->wageItemService->getAllItemsWithRates($selectedDate);

        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/items/index.php', [
            'items' => $items,
            'selectedDate' => $selectedDate,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function showCreateForm(): void
    {
        $oldInput = $_SESSION['old_input'] ?? [];
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['old_input'], $_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/items/create.php', [
            'oldInput' => $oldInput,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = WageItemDTO::fromArray($_POST);
                $this->wageItemService->createItem($dto);

                $_SESSION['message'] = 'Wage Item created successfully!';

                if (isset($_POST['save_and_continue'])) {
                    $nextInput = $_POST;
                    $nextInput['item_name'] = '';
                    $_SESSION['old_input'] = $nextInput;
                    header('Location: ?action=showCreateForm');
                    exit;
                } else {
                    header('Location: ?action=list');
                    exit;
                }

            } catch (PDOException $e) {
                if ($e->getCode() == '23505') {
                    $_SESSION['error'] = "Error: A designation with the name '{$_POST['item_name']}' already exists.";
                } else {
                    $_SESSION['error'] = "Database Error: " . $e->getMessage();
                }
                $_SESSION['old_input'] = $_POST;
                header('Location: ?action=showCreateForm');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                $_SESSION['old_input'] = $_POST;
                header('Location: ?action=showCreateForm');
                exit;
            }
        }
    }

    private function showEditForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->wageItemService->getItemById($id);
        if (!$item) throw new Exception("Item not found.");

        $this->render(__DIR__ . '/../Views/items/edit.php', ['item' => $item]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = WageItemDTO::fromArray($_POST);
                $this->wageItemService->updateItem($dto);
                $_SESSION['message'] = 'Wage Item updated successfully!';
                header('Location: ?action=list');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') {
                    $_SESSION['error'] = "Error: A designation with the name '{$_POST['item_name']}' already exists.";
                } else {
                    $_SESSION['error'] = "Database Error: " . $e->getMessage();
                }
                header('Location: ?action=list');
                exit;
            }
        }
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->wageItemService->deleteItem($id);
        $_SESSION['message'] = 'Wage Item deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new WageItemController();
$controller->handleRequest();
