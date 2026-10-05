<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/BillReportService.php';
require_once __DIR__ . '/../../Payment/Services/PaymentService.php';

use App\Core\BaseController;
use App\Modules\ReportPrinting\Services\BillReportService;
use App\Modules\Payment\Services\PaymentService;

class BillReportController extends BaseController {
    private BillReportService $service;

    public function __construct() {
        parent::__construct('ReportPrinting');
        $this->service = new BillReportService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'index': $this->index(); break;
            case 'print': $this->print(); break;
        }
    }

    private function index(): void {
        $paymentService = new PaymentService();
        $bills = $paymentService->getAllBills();
        $this->render(__DIR__ . '/../Views/Bill/index.php', ['bills' => $bills]);
    }

    private function print(): void {
        $billId = (int)$_GET['bill_id'];
        $reportData = $this->service->getBillPrintData($billId);

        // RESTORED: Use the master.php template which includes the 4 pages
        $this->render(__DIR__ . '/../Views/print/master.php', $reportData, false);
    }
}

$controller = new BillReportController();
$controller->handleRequest();
