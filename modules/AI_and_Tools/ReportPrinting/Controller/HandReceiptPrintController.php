<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/HandReceiptPrintService.php';

use App\Core\BaseController;
use App\Modules\ReportPrinting\Services\HandReceiptPrintService;

class HandReceiptPrintController extends BaseController {
    private HandReceiptPrintService $service;

    public function __construct() {
        parent::__construct('ReportPrinting');
        $this->service = new HandReceiptPrintService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'index': $this->index(); break;
            case 'print': $this->print(); break;
        }
    }

    private function index(): void {
        $receipts = $this->service->getAllHandReceipts();
        $this->render(__DIR__ . '/../Views/HandReceipt/index.php', ['receipts' => $receipts]);
    }

    private function print(): void {
        $id = (int)$_GET['id'];
        $details = $this->service->getHandReceiptDetails($id);
        // RENDER WITHOUT LAYOUT
        $this->render(__DIR__ . '/../Views/HandReceipt/print.php', $details, false);
    }
}

$controller = new HandReceiptPrintController();
$controller->handleRequest();
