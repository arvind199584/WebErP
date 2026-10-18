<?php
declare(strict_types=1);

namespace App\Modules\Civil\Estimates\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/EstimateService.php';
require_once __DIR__ . '/../../../Superadmin/Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Civil\Estimates\Services\EstimateService;
use App\Modules\Superadmin\Office\Services\OfficeService;
use Exception;

class EstimateController extends BaseController
{
    private EstimateService $service;

    public function __construct()
    {
        parent::__construct('Civil/Estimates');
        $this->service = new EstimateService();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'list';

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                case 'index':
                    $this->list();
                    break;
                case 'showCreateForm':
                    $this->showCreateForm();
                    break;
                case 'create':
                    $this->create();
                    break;
                case 'view':
                    $this->view();
                    break;
                case 'boqBuilder':
                    $this->boqBuilder();
                    break;
                case 'searchDsr':
                    $this->searchDsr();
                    break;
                case 'addItem':
                    $this->addItem();
                    break;
                case 'deleteItem':
                    $this->deleteItem();
                    break;
                default:
                    throw new Exception("Unknown action '{$action}' requested.");
            }
        } catch (\Throwable $e) {
            $this->renderErrorScreen($e, $action);
        }
    }

    private function list(): void
    {
        $officeId = ($this->currentUser['role'] === 'superuser') ? null : (int)$this->currentUser['officeid'];
        $estimates = $this->service->getAllEstimates($officeId);

        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'estimates' => $estimates,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function showCreateForm(): void
    {
        $officeService = new OfficeService();
        $offices = ($this->currentUser['role'] === 'superuser') ? $officeService->getAllOffices() : [];

        $this->render(__DIR__ . '/../Views/create.php', [
            'offices' => $offices,
            'defaultOfficeId' => (int)($this->currentUser['officeid'] ?? 1)
        ]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $officeId = ($this->currentUser['role'] === 'superuser') 
            ? (int)($_POST['officeid'] ?? $this->currentUser['officeid']) 
            : (int)$this->currentUser['officeid'];

        $data = [
            'officeid' => $officeId,
            'estimate_no' => trim((string)$_POST['estimate_no']),
            'work_name' => trim((string)$_POST['work_name']),
            'description' => trim((string)($_POST['description'] ?? '')),
            'contingency_percent' => (float)($_POST['contingency_percent'] ?? 3.0),
        ];

        $estimateId = $this->service->createEstimate($data, (int)$this->currentUser['id']);
        $_SESSION['message'] = "Estimate '{$data['estimate_no']}' created! Now add DSR items to the BoQ.";
        header("Location: ?action=boqBuilder&id={$estimateId}");
        exit;
    }

    private function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $details = $this->service->getEstimateDetails($id);

        $this->render(__DIR__ . '/../Views/view.php', $details);
    }

    private function boqBuilder(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $details = $this->service->getEstimateDetails($id);

        $this->render(__DIR__ . '/../Views/boq_builder.php', $details);
    }

    private function searchDsr(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $results = ($q !== '') ? $this->service->searchDsr($q) : [];

        // Render search results partial without layout
        $this->render(__DIR__ . '/../Views/partials/dsr_search_results.php', ['results' => $results], false);
    }

    private function addItem(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $estimateId = (int)($_POST['estimate_id'] ?? 0);
        $itemData = [
            'item_code' => trim((string)$_POST['item_code']),
            'quantity' => (float)($_POST['quantity'] ?? 0.0),
            'remarks' => trim((string)($_POST['remarks'] ?? ''))
        ];

        $details = $this->service->addItemToEstimate($estimateId, $itemData);

        // Check if HTMX request
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            $this->render(__DIR__ . '/../Views/partials/boq_table.php', $details, false);
            return;
        }

        header("Location: ?action=boqBuilder&id={$estimateId}");
        exit;
    }

    private function deleteItem(): void
    {
        $estimateId = (int)($_GET['estimate_id'] ?? 0);
        $itemId = (int)($_GET['item_id'] ?? 0);

        $details = $this->service->removeItem($itemId, $estimateId);

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            $this->render(__DIR__ . '/../Views/partials/boq_table.php', $details, false);
            return;
        }

        header("Location: ?action=boqBuilder&id={$estimateId}");
        exit;
    }
}

$controller = new EstimateController();
$controller->handleRequest();
