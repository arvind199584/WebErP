<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\HandReciept\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/HandReceiptService.php';
require_once __DIR__ . '/../Services/HandReceiptSchemaService.php';

use App\Core\BaseController;
use App\Modules\HandReciept\Services\HandReceiptService;
use App\Modules\HandReciept\Services\HandReceiptSchemaService;
use Exception;

class HandRecieptController extends BaseController {
    private HandReceiptService $service;

    public function __construct() {
        parent::__construct('HandReciept');
        $schemaService = new HandReceiptSchemaService();
        $schemaService->initSchema();
        $this->service = new HandReceiptService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'getBudgetsAjax': $this->getBudgetsAjax(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $receipts = $this->service->getAllReceipts();
        $this->render(__DIR__ . '/../Views/index.php', ['receipts' => $receipts]);
    }

    private function showCreateForm(): void {
        $isSuperUser = ($this->currentUser['role'] === 'superuser');
        $offices = $isSuperUser ? $this->service->getAllOffices() : [];
        $budgets = !$isSuperUser ? $this->service->getBudgetsByOffice((int)$this->currentUser['officeid']) : [];

        $this->render(__DIR__ . '/../Views/create.php', [
            'budgets' => $budgets,
            'offices' => $offices,
            'isSuperUser' => $isSuperUser
        ]);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;

            // Handle Custom Category
            if ($_POST['category_select'] === 'Custom') {
                $data['category'] = $_POST['custom_category'];
            } else {
                $data['category'] = $_POST['category_select'];
            }

            if ($this->currentUser['role'] !== 'superuser') {
                $data['officeid'] = $this->currentUser['officeid'];
            }

            $data['budget_implication'] = isset($_POST['budget_implication']);

            if ($this->service->createReceipt($data)) {
                $_SESSION['message'] = "Hand Receipt created successfully!";
                header("Location: ?action=list");
                exit;
            }
        }
    }

    private function getBudgetsAjax(): void {
        header('Content-Type: application/json');
        $officeId = (int)($_GET['office_id'] ?? 0);
        $budgets = $this->service->getBudgetsByOffice($officeId);
        echo json_encode($budgets);
        exit;
    }
}

$controller = new HandRecieptController();
$controller->handleRequest();
