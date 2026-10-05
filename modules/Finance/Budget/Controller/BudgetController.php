<?php

declare(strict_types=1);

namespace App\Modules\Finance\Budget\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/BudgetService.php';
require_once __DIR__ . '/../DTO/BudgetDTO.php';
require_once __DIR__ . '/../../../Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Finance\Budget\Services\BudgetService;
use App\Modules\Finance\Budget\DTO\BudgetDTO;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;
use PDOException;

class BudgetController extends BaseController
{
    private BudgetService $budgetService;

    public function __construct()
    {
        parent::__construct('Budget');
        $this->budgetService = new BudgetService();
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
            // REDIRECT TO ROOT TO BREAK THE LOOP
            header("Location: /");
            exit;
        }
    }

    private function list(): void
    {
        $budgets = $this->budgetService->getAllBudgets();
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'budgets' => $budgets,
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

        if (!isset($oldInput['fy'])) {
            $oldInput['fy'] = $this->budgetService->getCurrentFY();
        }

        $offices = [];
        if ($this->currentUser['role'] === 'superuser') {
            $officeService = new OfficeService();
            $offices = $officeService->getAllOffices();
        }

        $this->render(__DIR__ . '/../Views/create.php', [
            'oldInput' => $oldInput,
            'offices' => $offices,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = BudgetDTO::fromRequest($_POST, (int)$this->currentUser['officeid']);
                $this->budgetService->createBudget($dto);
                $_SESSION['message'] = 'Budget entry created successfully!';
                if (isset($_POST['save_and_continue'])) {
                    $_SESSION['old_input'] = ['fy' => $_POST['fy'], 'officeid' => $_POST['officeid'] ?? null];
                    header('Location: ?action=showCreateForm');
                    exit;
                } else {
                    header('Location: ?action=list');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = ($e->getCode() == '23505') ? "Error: A budget entry with this Code already exists." : "Database Error: " . $e->getMessage();
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
        $budget = $this->budgetService->getBudgetById($id);
        if (!$budget) throw new Exception("Budget entry not found.");
        $offices = [];
        if ($this->currentUser['role'] === 'superuser') {
            $officeService = new OfficeService();
            $offices = $officeService->getAllOffices();
        }
        $this->render(__DIR__ . '/../Views/edit.php', ['budget' => $budget, 'offices' => $offices]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = BudgetDTO::fromRequest($_POST, (int)$this->currentUser['officeid']);
                $this->budgetService->updateBudget($dto);
                $_SESSION['message'] = 'Budget entry updated successfully!';
                header('Location: ?action=list');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = ($e->getCode() == '23505') ? "Error: A budget entry with this Code already exists." : "Database Error: " . $e->getMessage();
                header('Location: ?action=list');
                exit;
            }
        }
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->budgetService->deleteBudget($id);
        $_SESSION['message'] = 'Budget entry deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new BudgetController();
$controller->handleRequest();
