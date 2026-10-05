<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ExpenditureReportService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';
require_once __DIR__ . '/../../Agency/Services/AgencyService.php';

use App\Core\BaseController;
use App\Modules\ReportPrinting\Services\ExpenditureReportService;
use App\Modules\Agreement\Services\AgreementService;
use App\Modules\Agency\Services\AgencyService;

class ExpenditureReportController extends BaseController {
    private ExpenditureReportService $service;

    public function __construct() {
        parent::__construct('ReportPrinting');
        $this->service = new ExpenditureReportService();
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
        $this->render(__DIR__ . '/../Views/Expenditure/index.php', ['agreements' => $agreements]);
    }

    private function print(): void {
        $agreementId = (int)$_GET['agreement_id'];
        $reportData = $this->service->getExpenditureByAgreement($agreementId);

        $agreementService = new AgreementService();
        $agreement = $agreementService->getAgreementById($agreementId);

        $agencyName = "N/A";
        if ($agreement) {
            $agencyService = new AgencyService();
            $agency = $agencyService->getAgencyById($agreement->agency_id);
            if ($agency) {
                $agencyName = $agency->name;
            }
        }

        // RENDER WITHOUT LAYOUT FOR CLEAN PRINT
        $this->render(__DIR__ . '/../Views/Expenditure/print.php', [
            'reportData' => $reportData,
            'agreement' => $agreement,
            'agencyName' => $agencyName
        ], false); // Set useLayout to false
    }
}

$controller = new ExpenditureReportController();
$controller->handleRequest();
