<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\Deviation\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/DeviationService.php';
require_once __DIR__ . '/../../../Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\Admin\Bills\Deviation\Services\DeviationService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use Exception;

class DeviationController extends BaseController {
    private DeviationService $service;

    public function __construct() {
        parent::__construct('Deviation');
        $this->service = new DeviationService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'getOriginalBOQAjax': $this->getOriginalBOQAjax(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $deviations = $this->service->getAllDeviations();
        $this->render(__DIR__ . '/../Views/index.php', ['deviations' => $deviations]);
    }

    private function showCreateForm(): void {
        $step = (int)($_GET['step'] ?? 1);
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();

        $viewPath = __DIR__ . "/../Views/create_step{$step}.php";
        $this->render($viewPath, ['agreements' => $agreements]);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step = (int)$_POST['step'];
            $_SESSION['deviation_create_data'] = array_merge($_SESSION['deviation_create_data'] ?? [], $_POST);

            if ($step < 2) {
                header("Location: ?action=showCreateForm&step=" . ($step + 1));
                exit;
            }

            $data = $_SESSION['deviation_create_data'];
            $data['officeid'] = $this->currentUser['officeid'];

            // Parse BOQ from JSON string sent by JS
            if (isset($data['boq_json'])) {
                $data['boq'] = json_decode($data['boq_json'], true);
            }

            if ($this->service->createDeviation($data)) {
                unset($_SESSION['deviation_create_data']);
                $_SESSION['message'] = "Deviation proposal created successfully!";
                header("Location: ?action=list");
                exit;
            }
        }
    }

    private function getOriginalBOQAjax(): void {
        header('Content-Type: application/json');
        $agreementId = (int)$_GET['agreement_id'];
        $boq = $this->service->getOriginalBOQ($agreementId);
        echo json_encode($boq);
        exit;
    }
}

$controller = new DeviationController();
$controller->handleRequest();
