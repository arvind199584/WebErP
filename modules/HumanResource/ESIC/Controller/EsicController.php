<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\ESIC\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/EsicService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\HumanResource\ESIC\Services\EsicService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use Exception;

class EsicController extends BaseController {
    private EsicService $esicService;
    public function __construct() {
        parent::__construct('ESIC');
        $this->esicService = new EsicService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            // Permissions would be added here
            switch ($action) {
                case 'list': $this->list(); break;
                case 'generate': $this->generate(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $selectedAgreementId = $_GET['agreement_id'] ?? null;
        $selectedMonth = $_GET['month'] ?? date('Y-m');

        $employees = $selectedAgreementId ? $this->esicService->getContributionStatusForMonth((int)$selectedAgreementId, $selectedMonth) : [];

        $this->render(__DIR__ . '/../Views/index.php', [
            'agreements' => $agreements, 'employees' => $employees,
            'selectedAgreementId' => $selectedAgreementId, 'selectedMonth' => $selectedMonth
        ]);
    }

    private function generate(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $employeeIds = $_POST['employee_ids'] ?? [];
            $month = $_POST['month'];

            foreach ($employeeIds as $employeeId) {
                $this->esicService->generateContribution((int)$employeeId, (int)$this->currentUser['officeid'], $month);
            }

            $_SESSION['message'] = count($employeeIds) . ' ESIC contribution(s) generated successfully!';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}

$controller = new EsicController();
$controller->handleRequest();
