<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Wages\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/WagesService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';
require_once __DIR__ . '/../../Employee/Services/EmployeeService.php';

use App\Core\BaseController;
use App\Modules\HumanResource\Wages\Services\WagesService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use App\Modules\HumanResource\Employee\Services\EmployeeService;
use Exception;

class WagesController extends BaseController {
    private WagesService $wagesService;
    public function __construct() {
        parent::__construct('Wages');
        $this->wagesService = new WagesService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index': $this->index(); break;
                case 'showProcess': $this->showProcess(); break;
                case 'showReport': $this->showReport(); break;
                case 'showRecalculate': $this->showRecalculate(); break;
                case 'recalculate': $this->recalculate(); break;
                case 'showBatchDebit': $this->showBatchDebit(); break;
                case 'processBatchDebit': $this->processBatchDebit(); break;
                case 'generate': $this->generate(); break;
                case 'generateAll': $this->generateAll(); break;
                case 'addDebit': $this->addDebit(); break;
                case 'viewLedger': $this->viewLedger(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
            header("Location: ?action=index");
            exit;
        }
    }

    private function index(): void {
        $this->render(__DIR__ . '/../Views/index.php');
    }

    private function showProcess(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $selectedAgreementId = $_GET['agreement_id'] ?? null;
        $dashboardData = $selectedAgreementId ? $this->wagesService->getEmployeeDashboard((int)$selectedAgreementId) : [];
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);
        $this->render(__DIR__ . '/../Views/process.php', ['agreements' => $agreements, 'dashboardData' => $dashboardData, 'selectedAgreementId' => $selectedAgreementId, 'message' => $message, 'error' => $error]);
    }

    private function showBatchDebit(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $this->render(__DIR__ . '/../Views/batch_debit.php', ['agreements' => $agreements]);
    }

    private function processBatchDebit(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agreementId = (int)$_POST['agreement_id'];
            $wagesFrom = $_POST['wages_from'];
            $wagesTo = $_POST['wages_to'];
            $paymentDate = $_POST['payment_date'];
            $remark = $_POST['remark'];

            $count = $this->wagesService->addBatchDebitEntry($agreementId, (int)$this->currentUser['officeid'], $wagesFrom, $wagesTo, $paymentDate, $remark);
            $_SESSION['message'] = "Batch payment processed for $count employees.";
            header('Location: ?action=showProcess&agreement_id=' . $agreementId);
            exit;
        }
    }

    private function showRecalculate(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $this->render(__DIR__ . '/../Views/recalculate.php', ['agreements' => $agreements]);
    }

    private function recalculate(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agreementId = (int)$_POST['agreement_id'];
            $from = $_POST['from_date'];
            $to = $_POST['to_date'];
            $count = $this->wagesService->recalculateWagesForAgreement($agreementId, (int)$this->currentUser['officeid'], $from, $to);
            $_SESSION['message'] = "Recalculated wages for $count employees.";
            header('Location: ?action=showProcess&agreement_id=' . $agreementId);
            exit;
        }
    }

    private function showReport(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $selectedAgreementId = $_GET['agreement_id'] ?? null;
        $dueFrom = $_GET['due_from'] ?? date('Y-m-01');
        $dueTo = $_GET['due_to'] ?? date('Y-m-t');
        $drawnOn = $_GET['drawn_on'] ?? date('Y-m-d');
        $reportData = $selectedAgreementId ? $this->wagesService->getWageSummaryReport((int)$selectedAgreementId, $dueFrom, $dueTo, $drawnOn) : [];
        $this->render(__DIR__ . '/../Views/report.php', ['agreements' => $agreements, 'reportData' => $reportData, 'selectedAgreementId' => $selectedAgreementId, 'dueFrom' => $dueFrom, 'dueTo' => $dueTo, 'drawnOn' => $drawnOn]);
    }

    private function generate(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->wagesService->generateWagesForPeriod((int)$_POST['employee_id'], (int)$this->currentUser['officeid'], $_POST['from_date'], $_POST['to_date']);
            $_SESSION['message'] = 'Wages generated successfully!';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    private function generateAll(): void {
        $agreementId = (int)($_GET['agreement_id'] ?? 0);
        if ($agreementId > 0) {
            $count = $this->wagesService->generateWagesForAllEmployees($agreementId, (int)$this->currentUser['officeid']);
            $_SESSION['message'] = "Wages generated for $count employees.";
        }
        header('Location: ?action=showProcess&agreement_id=' . $agreementId);
        exit;
    }

    private function addDebit(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->wagesService->addDebitEntry((int)$_POST['employee_id'], (int)$this->currentUser['officeid'], $_POST['date'], (float)$_POST['amount'], $_POST['remark']);
            $_SESSION['message'] = 'Debit entry added successfully!';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    private function viewLedger(): void {
        $employeeId = (int)$_GET['employee_id'];
        $flatLedger = $this->wagesService->getFullWageLedger($employeeId);
        $groupedLedger = [];
        foreach ($flatLedger as $entry) {
            $month = date('Y-m', strtotime($entry['transaction_date']));
            $groupedLedger[$month][] = $entry;
        }
        $this->render(__DIR__ . '/../Views/ledger.php', ['ledger' => $groupedLedger]);
    }
}

$controller = new WagesController();
$controller->handleRequest();
