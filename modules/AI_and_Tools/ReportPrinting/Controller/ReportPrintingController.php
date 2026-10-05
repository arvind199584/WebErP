<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ReportService.php';
require_once __DIR__ . '/../Services/BillPrintService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\AI_and_Tools\ReportPrinting\Services\ReportService;
use App\Modules\AI_and_Tools\ReportPrinting\Services\BillPrintService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use Exception;

class ReportPrintingController extends BaseController {
    private ReportService $reportService;
    private BillPrintService $billPrintService;

    public function __construct() {
        parent::__construct('AI_and_Tools/ReportPrinting');
        $this->reportService = new ReportService();
        $this->billPrintService = new BillPrintService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission('view');
            switch ($action) {
                case 'index': $this->index(); break;
                case 'printBill': $this->printBill(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function index(): void {
        $reportType = $_GET['report_type'] ?? null;
        $reportData = [];

        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();

        if ($reportType) {
            switch ($reportType) {
                case 'bill':
                    $fromDate = $_GET['from_date'] ?? null;
                    $toDate = $_GET['to_date'] ?? null;
                    $reportData = $this->reportService->getBillStatusReport($fromDate, $toDate);
                    break;
                case 'agreement':
                    $status = $_GET['status'] ?? 'Active';
                    $reportData = $this->reportService->getAgreementReport($status);
                    break;
                case 'attendance':
                    $agreementId = $_GET['agreement_id'] ?? null;
                    $month = $_GET['month'] ?? date('Y-m');
                    if ($agreementId) {
                        $reportData = $this->reportService->getAttendanceReport((int)$agreementId, $month);
                    }
                    break;
                case 'wages':
                    $agreementId = $_GET['agreement_id'] ?? null;
                    $fromDate = $_GET['from_date'] ?? null;
                    $toDate = $_GET['to_date'] ?? null;
                    if ($agreementId && $fromDate && $toDate) {
                        $reportData = $this->reportService->getWagesVerificationReport((int)$agreementId, $fromDate, $toDate);
                    }
                    break;
            }
        }

        $this->render(__DIR__ . '/../Views/index.php', [
            'agreements' => $agreements,
            'reportType' => $reportType,
            'reportData' => $reportData,
            'filters' => $_GET
        ]);
    }

    private function printBill(): void {
        $id = (int)$_GET['id'];
        $printData = $this->billPrintService->getBillPrintData($id);
        if (!$printData) { echo "<h1>Error: Bill not found.</h1>"; exit; }
        extract($printData);
        include __DIR__ . '/../Views/print/master.php';
        exit;
    }
}

$controller = new ReportPrintingController();
$controller->handleRequest();
