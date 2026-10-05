<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ESICReportService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\ReportPrinting\Services\ESICReportService;
use App\Modules\Agreement\Services\AgreementService;
use Exception;

class ESICReportController extends BaseController {
    private ESICReportService $service;

    public function __construct() {
        parent::__construct('ReportPrinting');
        $this->service = new ESICReportService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission('view');
            switch ($action) {
                case 'index': $this->index(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function index(): void {
        $agreementId = (int)($_GET['agreement_id'] ?? 0);
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $reportData = [];
        if ($agreementId && $fromDate && $toDate) {
            $reportData = $this->service->getESICReport($agreementId, $fromDate, $toDate);
        }

        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();

        $this->render(__DIR__ . '/../Views/ESIC/index.php', [
            'reportData' => $reportData,
            'agreements' => $agreements,
            'filters' => $_GET
        ]);
    }
}

$controller = new ESICReportController();
$controller->handleRequest();
