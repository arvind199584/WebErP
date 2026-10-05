<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/WagesReportService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\ReportPrinting\Services\WagesReportService;
use App\Modules\Agreement\Services\AgreementService;

class WagesReportController extends BaseController {
    private WagesReportService $service;

    public function __construct() {
        parent::__construct('ReportPrinting');
        $this->service = new WagesReportService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'index': $this->index(); break;
            case 'print': $this->print(); break;
        }
    }

    private function index(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $this->render(__DIR__ . '/../Views/Wages/index.php', ['agreements' => $agreements]);
    }

    private function print(): void {
        $agreementId = (int)$_GET['agreement_id'];
        $dueFrom = $_GET['due_from'];
        $dueTo = $_GET['due_to'];
        $drawnOn = $_GET['drawn_on'];

        $reportData = $this->service->getWagesReport($agreementId, $dueFrom, $dueTo, $drawnOn);

        // RENDER WITHOUT LAYOUT
        $this->render(__DIR__ . '/../Views/Wages/print.php', [
            'header' => $reportData['header'],
            'data' => $reportData['data'],
            'params' => ['from' => $dueFrom, 'to' => $dueTo, 'drawn' => $drawnOn]
        ], false);
    }
}

$controller = new WagesReportController();
$controller->handleRequest();
