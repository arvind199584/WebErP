<?php
declare(strict_types=1);

namespace App\Modules\Admin\Bills\Payment\Controller;

require_once __DIR__ . '/../../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/PaymentService.php';
require_once __DIR__ . '/../Services/ValidationService.php';
require_once __DIR__ . '/../Services/BillPrintService.php';
require_once __DIR__ . '/../DTO/PaymentDTO.php';
require_once __DIR__ . '/../../../Agreement/Services/AgreementService.php';
require_once __DIR__ . '/../../../../Finance/Budget/Services/BudgetService.php';

use App\Core\BaseController;
use App\Modules\Admin\Bills\Payment\Services\PaymentService;
use App\Modules\Admin\Bills\Payment\Services\ValidationService;
use App\Modules\Admin\Bills\Payment\Services\BillPrintService;
use App\Modules\Admin\Bills\Payment\DTO\PaymentDTO;
use App\Modules\Admin\Agreement\Services\AgreementService;
use App\Modules\Finance\Budget\Services\BudgetService;
use Exception;

class PaymentController extends BaseController {
    private PaymentService $paymentService;
    private ValidationService $validationService;
    private BillPrintService $billPrintService;

    public function __construct() {
        parent::__construct('Payment');
        $this->paymentService = new PaymentService();
        $this->validationService = new ValidationService();
        $this->billPrintService = new BillPrintService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'generateBillItemsAjax': $this->generateBillItemsAjax(); break;
                case 'getDailyAttendanceAjax': $this->getDailyAttendanceAjax(); break;
                case 'updateStatus': $this->updateStatus(); break;
                case 'edit': $this->edit(); break;
                case 'delete': $this->delete(); break;
                case 'print': $this->print(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            if (isset($_GET['action']) && strpos($_GET['action'], 'Ajax') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $searchTerm = $_GET['search'] ?? null;
        $bills = $this->paymentService->getAllBills($searchTerm);
        $this->render(__DIR__ . '/../Views/index.php', ['bills' => $bills, 'searchTerm' => $searchTerm, 'message' => $_SESSION['message'] ?? null, 'error' => $_SESSION['error'] ?? null]);
        unset($_SESSION['message'], $_SESSION['error']);
    }

    private function showCreateForm(): void {
        $step = (int)($_GET['step'] ?? 1);
        $data = $_SESSION['payment_create_data'] ?? [];
        $viewData = ['data' => $data];
        if ($step === 1) {
            $agreementService = new AgreementService();
            $viewData['agreements'] = $agreementService->getAllAgreements();
        } elseif ($step === 3) {
            $viewData['calculations'] = $this->paymentService->calculateBillAmounts($data['bill_items'] ?? [], 'Manpower', (int)($data['agreement_id'] ?? 0));
            $viewData['previous'] = $this->paymentService->getPreviousExpenditure((int)$data['agreement_id'], $data['bill_date']);
        }
        $this->render(__DIR__ . '/../Views/create_step' . $step . '.php', $viewData);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step = (int)$_POST['step'];
            if (isset($_POST['bill_items'])) $_POST['bill_items'] = json_decode($_POST['bill_items'], true);
            $_SESSION['payment_create_data'] = array_merge($_SESSION['payment_create_data'] ?? [], $_POST);
            $data = $_SESSION['payment_create_data'];
            try {
                if ($step === 1) $this->validationService->validateStep1($data);
                if ($step < 3) { header('Location: ?action=showCreateForm&step=' . ($step + 1)); exit; }
                else {
                    // DYNAMIC BUDGET RESOLUTION
                    $budgetService = new BudgetService();
                    $agreementService = new AgreementService();

                    // 1. Get the Budget Code from the Agreement
                    $agreement = $agreementService->getAgreementById((int)$data['agreement_id']);
                    $budgetRecord = $budgetService->getBudgetById((int)$agreement->budget_id); // This is the original budget row
                    $budgetCode = $budgetRecord->code;

                    // 2. Resolve the correct ID for the Bill Date
                    $resolvedBudgetId = $budgetService->resolveBudgetId($budgetCode, $data['bill_date'], (int)$this->currentUser['officeid']);

                    // 3. Update data with resolved ID
                    $data['budget_id'] = $resolvedBudgetId;

                    $dto = new PaymentDTO($data, (int)$this->currentUser['officeid']);
                    $this->paymentService->createBill($dto);
                    unset($_SESSION['payment_create_data']);
                    $_SESSION['message'] = 'Bill created successfully!';
                    header('Location: ?action=list');
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?action=showCreateForm&step=' . $step);
                exit;
            }
        }
    }

    private function generateBillItemsAjax(): void {
        header('Content-Type: application/json');
        try {
            $agreementId = (int)$_GET['agreement_id'];
            $fromDate = $_GET['from_date'];
            $toDate = $_GET['to_date'];
            $billItems = $this->paymentService->generateManpowerBillItems($agreementId, $fromDate, $toDate);
            echo json_encode(['success' => true, 'bill_items' => $billItems]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    private function getDailyAttendanceAjax(): void {
        header('Content-Type: application/json');
        try {
            $agreementId = (int)$_GET['agreement_id'];
            $fromDate = $_GET['from_date'];
            $toDate = $_GET['to_date'];
            if (!$agreementId || !$fromDate || !$toDate) throw new Exception("Missing parameters.");
            $summary = $this->paymentService->getDailyAttendanceSummary($agreementId, $fromDate, $toDate);
            echo json_encode(['success' => true, 'summary' => $summary]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    private function updateStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->paymentService->updateBillStatus((int)$_POST['id'], $_POST['status'], $_POST['status_date']);
            $_SESSION['message'] = "Bill status updated.";
            header('Location: ?action=list');
            exit;
        }
    }

    private function edit(): void {
        $id = (int)$_GET['id'];
        $bill = $this->paymentService->getBillById($id);
        $this->render(__DIR__ . '/../Views/edit.php', ['bill' => $bill]);
    }

    private function delete(): void {
        $id = (int)$_GET['id'];
        try {
            $this->paymentService->deleteBill($id);
            $_SESSION['message'] = 'Bill deleted successfully!';
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: ?action=list');
        exit;
    }

    private function print(): void {
        $id = (int)$_GET['id'];
        $filePath = $this->billPrintService->generateDocument($id, $_GET['type'] ?? 'word');
        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            readfile($filePath);
            unlink($filePath);
            exit;
        }
        die("Error: Could not generate file.");
    }
}

$controller = new PaymentController();
$controller->handleRequest();
