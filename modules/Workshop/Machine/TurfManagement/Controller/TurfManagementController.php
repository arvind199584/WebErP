<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/DashboardService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Services\DashboardService;
use Exception;

class TurfManagementController extends BaseController {
    private $dashboardService;

    public function __construct() {
        parent::__construct('Workshop/Machine/TurfManagement');
        $this->dashboardService = new DashboardService();
    }

    public function handleRequest(): void {
        if (!$this->currentUser) {
            header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
            exit;
        }

        $action = $_GET['action'] ?? 'dashboard';
        $userRole = strtolower($this->currentUser['role'] ?? '');
        $userOfficeId = (int)($this->currentUser['officeid'] ?? 0);

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'dashboard':
                case 'index':
                    $this->showDashboard($userRole, $userOfficeId);
                    break;
                case 'getBalanceData':
                    $this->getBalanceData($userRole, $userOfficeId);
                    break;
                default:
                    $this->showDashboard($userRole, $userOfficeId);
                    break;
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            $this->handleException($e, $action, 'dashboard');
        }
    }

    private function isAjaxRequest(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors') ||
               (strtolower($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') ||
               (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    private function showDashboard(string $userRole, int $userOfficeId): void {
        $requestedOffice = isset($_GET['office_id']) ? (int)$_GET['office_id'] : $userOfficeId;
        $officeId = ($userRole === 'superuser' && $requestedOffice > 0) ? $requestedOffice : (($userRole === 'superuser') ? null : $userOfficeId);
        $asOfDate = !empty($_GET['as_of_date']) ? trim($_GET['as_of_date']) : date('Y-m-d');

        $data = $this->dashboardService->getDashboardInitData($officeId, $asOfDate);

        $this->render(__DIR__ . '/../Views/dashboard.php', array_merge($data, [
            'currentUser' => $this->currentUser,
            'userRole'    => $userRole
        ]));
    }

    private function getBalanceData(string $userRole, int $userOfficeId): void {
        $requestedOffice = isset($_GET['office_id']) ? (int)$_GET['office_id'] : $userOfficeId;
        $officeId = ($userRole === 'superuser' && $requestedOffice > 0) ? $requestedOffice : (($userRole === 'superuser') ? null : $userOfficeId);
        $category = trim((string)($_GET['category'] ?? 'fuel'));
        $asOfDate = !empty($_GET['as_of_date']) ? trim((string)$_GET['as_of_date']) : date('Y-m-d');

        $balances = $this->dashboardService->getStockBalancesByCategory($officeId, $category, $asOfDate);

        header('Content-Type: application/json');
        echo json_encode([
            'status'     => 'success',
            'category'   => $category,
            'as_of_date' => $asOfDate,
            'data'       => $balances
        ]);
        exit;
    }
}

$controller = new TurfManagementController();
$controller->handleRequest();
