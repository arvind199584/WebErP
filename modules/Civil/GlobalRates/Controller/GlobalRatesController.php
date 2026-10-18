<?php
declare(strict_types=1);

namespace App\Modules\Civil\GlobalRates\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/GlobalRatesService.php';

use App\Core\BaseController;
use App\Modules\Civil\GlobalRates\Services\GlobalRatesService;
use Exception;

class GlobalRatesController extends BaseController
{
    private GlobalRatesService $service;

    public function __construct()
    {
        parent::__construct('Civil/GlobalRates');
        $this->service = new GlobalRatesService();
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
                case 'updateRate':
                    $this->updateRate();
                    break;
                case 'importExcel':
                    $this->importExcel();
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
        $query = $_GET['q'] ?? null;
        $category = $_GET['category'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $data = $this->service->getRatesList($query, $category, $page, 50);
        $data['query'] = $query;
        $data['selectedCategory'] = $category;
        $data['message'] = $_SESSION['message'] ?? null;
        $data['error'] = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        // Check if HTMX partial request for table only
        $isHtmx = isset($_SERVER['HTTP_HX_REQUEST']);
        if ($isHtmx && isset($_GET['partial'])) {
            $this->render(__DIR__ . '/../Views/partials/rates_table.php', $data, false);
            return;
        }

        $this->render(__DIR__ . '/../Views/index.php', $data);
    }

    private function updateRate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $code = trim((string)($_POST['resource_code'] ?? ''));
        $marketRate = (float)($_POST['market_rate'] ?? 0.0);

        if ($code === '') {
            throw new Exception("Resource code is required.");
        }

        $this->service->updateRate($code, $marketRate);

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            echo "<span class='badge bg-success'><i class='bi bi-check-circle me-1'></i>Updated (₹" . number_format($marketRate, 2) . ")</span>";
            exit;
        }

        $_SESSION['message'] = "Market rate for resource '{$code}' updated successfully.";
        header("Location: ?action=list");
        exit;
    }

    private function importExcel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['excel_file']['tmp_name'])) {
            throw new Exception("Please upload a valid Excel (.xlsx) file.");
        }

        $tmpFile = $_FILES['excel_file']['tmp_name'];
        $updated = $this->service->importRatesFromExcel($tmpFile);

        $_SESSION['message'] = "Successfully updated {$updated} resource market rates from uploaded Excel sheet.";
        header("Location: ?action=list");
        exit;
    }
}

$controller = new GlobalRatesController();
$controller->handleRequest();
