<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/WageRateService.php';
require_once __DIR__ . '/../DTO/WageOrderDTO.php';

use App\Core\BaseController;
use App\Modules\Superadmin\WagesRates\Services\WageRateService;
use App\Modules\Superadmin\WagesRates\DTO\WageOrderDTO;
use Exception;

class WageRateController extends BaseController
{
    private WageRateService $wageRateService;

    public function __construct()
    {
        parent::__construct('Superadmin/WagesRates');
        $this->wageRateService = new WageRateService();
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
                case 'view': $this->view(); break;
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
        $orders = $this->wageRateService->getAllOrders();
        $currentRates = $this->wageRateService->getCurrentRates();
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'orders' => $orders,
            'currentRates' => $currentRates,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function showCreateForm(): void
    {
        $items = $this->wageRateService->getAllItems();
        $this->render(__DIR__ . '/../Views/create.php', ['items' => $items]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process the form data
            $authority = $_POST['authority'];
            $letter_no = $_POST['letter_no'];
            $letter_date = $_POST['letter_date'];
            $valid_from = $_POST['valid_from'];

            // Collect rates from the dynamic form fields
            $rates = [];
            foreach ($_POST['rates'] as $key => $value) {
                if (!empty($value)) {
                    $rates[$key] = (float)$value;
                }
            }

            $dto = new WageOrderDTO($authority, $letter_no, $letter_date, $valid_from, $rates);
            $this->wageRateService->addOrder($dto);

            $_SESSION['message'] = 'Wage Order created successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $order = $this->wageRateService->getOrderById($id);
        if (!$order) throw new Exception("Order not found.");

        $this->render(__DIR__ . '/../Views/view.php', ['order' => $order]);
    }
}

$controller = new WageRateController();
$controller->handleRequest();
