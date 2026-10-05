<?php
declare(strict_types=1);
namespace App\Modules\Admin\SupplyOrder\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/SupplyOrderService.php';
require_once __DIR__ . '/../DTO/SupplyOrderDTO.php';
require_once __DIR__ . '/../../AA_ES/Services/AA_ES_Service.php';
require_once __DIR__ . '/../../../Superadmin/Agency/Services/AgencyService.php';

use App\Core\BaseController;
use App\Modules\Admin\SupplyOrder\Services\SupplyOrderService;
use App\Modules\Admin\SupplyOrder\DTO\SupplyOrderDTO;
use App\Modules\Admin\AA_ES\Services\AA_ES_Service;
use App\Modules\Superadmin\Agency\Services\AgencyService;
use Exception;

class SupplyOrderController extends BaseController {
    private SupplyOrderService $supplyOrderService;
    public function __construct() {
        parent::__construct('SupplyOrder');
        $this->supplyOrderService = new SupplyOrderService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $supplyOrders = $this->supplyOrderService->getAllSupplyOrders();
        $this->render(__DIR__ . '/../Views/index.php', ['supplyOrders' => $supplyOrders]);
    }

    private function showCreateForm(): void {
        $aaEsService = new AA_ES_Service();
        $agencyService = new AgencyService();
        $this->render(__DIR__ . '/../Views/create.php', [
            'aa_es_list' => $aaEsService->getAllAA_ES(),
            'agencies' => $agencyService->getAllAgencies()
        ]);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $aaEsService = new AA_ES_Service();
            $aa_es = $aaEsService->getAA_ESById((int)$_POST['aa_es_id']);
            if (!$aa_es) throw new Exception("AA & ES not found.");

            $dto = new SupplyOrderDTO(
                (int)$this->currentUser['officeid'],
                (int)$_POST['aa_es_id'],
                (int)$_POST['agency_id'],
                $_POST['supply_order_no'],
                $aa_es->estimated_cost,
                (float)$_POST['tendered_amount'],
                (float)$_POST['service_charge_percent']
            );
            $this->supplyOrderService->createSupplyOrder($dto);
            $_SESSION['message'] = 'Supply Order created successfully!';
            header('Location: ?action=list');
            exit;
        }
    }
}

$controller = new SupplyOrderController();
$controller->handleRequest();
