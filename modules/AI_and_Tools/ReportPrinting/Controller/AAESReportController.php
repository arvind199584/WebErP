<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AAESReportService.php';

use App\Core\BaseController;
use App\Modules\ReportPrinting\Services\AAESReportService;

class AAESReportController extends BaseController {
    private AAESReportService $service;

    public function __construct() {
        parent::__construct('ReportPrinting');
        $this->service = new AAESReportService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'index': $this->index(); break;
            case 'print': $this->print(); break;
        }
    }

    private function index(): void {
        $aa_es_list = $this->service->getAllAAES();
        $this->render(__DIR__ . '/../Views/AAES/index.php', ['aa_es_list' => $aa_es_list]);
    }

    private function print(): void {
        $id = (int)$_GET['id'];
        $details = $this->service->getAAESDetails($id);
        // RENDER WITHOUT LAYOUT
        $this->render(__DIR__ . '/../Views/AAES/print.php', $details, false);
    }
}

$controller = new AAESReportController();
$controller->handleRequest();
