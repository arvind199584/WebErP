<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Employee\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/EmployeeService.php';
require_once __DIR__ . '/../DTO/EmployeeDTO.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';
require_once __DIR__ . '/../../../../modules/Admin/AA_ES/Services/AA_ES_Service.php';

use App\Core\BaseController;
use App\Modules\HumanResource\Employee\Services\EmployeeService;
use App\Modules\HumanResource\Employee\DTO\EmployeeDTO;
use App\Modules\Admin\Agreement\Services\AgreementService;
use Exception;

class EmployeeController extends BaseController {
    private EmployeeService $employeeService;
    public function __construct() {
        parent::__construct('Employee');
        $this->employeeService = new EmployeeService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'showEditForm': $this->showEditForm(); break;
                case 'update': $this->update(); break;
                case 'process_batch': $this->processBatch(); break;
                case 'delete': $this->delete(); break;
                case 'deleteAll': $this->deleteAll(); break; // New
                case 'downloadTemplate': $this->downloadTemplate(); break;
                case 'upload': $this->upload(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $selectedAgreementId = $_GET['agreement_id'] ?? null;
        $employees = $selectedAgreementId ? $this->employeeService->getEmployeesByAgreement((int)$selectedAgreementId) : [];

        $this->render(__DIR__ . '/../Views/index.php', [
            'employees' => $employees,
            'agreements' => $agreements,
            'selectedAgreementId' => $selectedAgreementId,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['message'], $_SESSION['error']);
    }

    private function showEditForm(): void {
        $id = (int)($_GET['id'] ?? 0);
        $employee = $this->employeeService->getEmployeeById($id);
        if (!$employee) {
            throw new Exception("Employee not found.");
        }

        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        
        $agreementData = [];
        foreach ($agreements as $agreement) {
            $fullAgreement = $agreementService->getAgreementById((int)$agreement['id']);
            if ($fullAgreement) {
                $scopeItems = [];
                if ($fullAgreement->scope === null) {
                    $aaEsService = new \App\Modules\AA_ES\Services\AA_ES_Service();
                    $aa_es = $aaEsService->getAA_ESById($fullAgreement->aa_es_id);
                    if ($aa_es && $aa_es->type === 'Manpower') {
                        foreach ($aa_es->boq['Items'] as $item) { $scopeItems[] = $item['description']; }
                    }
                } else {
                    $latestScope = end($fullAgreement->scope);
                    $scopeItems = array_keys($latestScope['Items']);
                }
                $agreementData[$agreement['id']] = ['start' => $fullAgreement->period_from, 'end' => $fullAgreement->period_to, 'scope' => $scopeItems];
            }
        }

        require_once __DIR__ . '/../../../Superadmin/Office/Services/OfficeService.php';
        $officeService = new \App\Modules\Superadmin\Office\Services\OfficeService();
        $offices = $officeService->getAllOffices();

        $this->render(__DIR__ . '/../Views/edit.php', [
            'employee' => $employee,
            'agreements' => $agreements,
            'agreementDataJson' => json_encode($agreementData),
            'offices' => $offices
        ]);
    }

    private function update(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $dto = new EmployeeDTO(
                (int)$this->currentUser['officeid'],
                (int)$_POST['agreement_id'],
                $_POST['full_name'],
                $_POST['designation'],
                $_POST['joining_date'],
                !empty($_POST['leaving_date']) ? $_POST['leaving_date'] : null,
                !empty($_POST['default_rest_day']) ? $_POST['default_rest_day'] : null,
                isset($_POST['is_reliever']),
                $_POST['account_no'] ?? '', $_POST['ifsc'] ?? '', $_POST['bank_name'] ?? '',
                $_POST['uan_no'] ?? '', $_POST['esic_no'] ?? '',
                null, // inherited_from_id
                null, // id
                !empty($_POST['deployed_office_id']) ? (int)$_POST['deployed_office_id'] : null
            );
            $this->employeeService->updateEmployee($id, $dto);
            $_SESSION['message'] = 'Employee updated successfully!';
            header('Location: ?action=list&agreement_id=' . $_POST['agreement_id']);
            exit;
        }
    }

    private function showCreateForm(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $agreementData = [];
        foreach ($agreements as $agreement) {
            $fullAgreement = $agreementService->getAgreementById((int)$agreement['id']);
            if ($fullAgreement) {
                $scopeItems = [];
                if ($fullAgreement->scope === null) {
                    $aaEsService = new \App\Modules\AA_ES\Services\AA_ES_Service();
                    $aa_es = $aaEsService->getAA_ESById($fullAgreement->aa_es_id);
                    if ($aa_es && $aa_es->type === 'Manpower') {
                        foreach ($aa_es->boq['Items'] as $item) { $scopeItems[] = $item['description']; }
                    }
                } else {
                    $latestScope = end($fullAgreement->scope);
                    $scopeItems = array_keys($latestScope['Items']);
                }
                $agreementData[$agreement['id']] = ['start' => $fullAgreement->period_from, 'end' => $fullAgreement->period_to, 'scope' => $scopeItems];
            }
        }
        require_once __DIR__ . '/../../../Superadmin/Office/Services/OfficeService.php';
        $officeService = new \App\Modules\Superadmin\Office\Services\OfficeService();
        $offices = $officeService->getAllOffices();

        $this->render(__DIR__ . '/../Views/create.php', [
            'agreements' => $agreements, 
            'agreementDataJson' => json_encode($agreementData), 
            'selectedAgreementId' => $_SESSION['selected_agreement_id'] ?? null,
            'offices' => $offices
        ]);
        unset($_SESSION['selected_agreement_id']);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dto = new EmployeeDTO(
                (int)$this->currentUser['officeid'],
                (int)$_POST['agreement_id'],
                $_POST['full_name'],
                $_POST['designation'],
                $_POST['joining_date'],
                !empty($_POST['leaving_date']) ? $_POST['leaving_date'] : null,
                !empty($_POST['default_rest_day']) ? $_POST['default_rest_day'] : null,
                isset($_POST['is_reliever']),
                $_POST['account_no'] ?? '', $_POST['ifsc'] ?? '', $_POST['bank_name'] ?? '',
                $_POST['uan_no'] ?? '', $_POST['esic_no'] ?? '',
                null, // inherited_from_id
                null, // id
                !empty($_POST['deployed_office_id']) ? (int)$_POST['deployed_office_id'] : null
            );
            $this->employeeService->createEmployee($dto);
            if (isset($_POST['save_and_continue'])) {
                $_SESSION['message'] = 'Employee created. Add another.';
                $_SESSION['selected_agreement_id'] = $_POST['agreement_id'];
                header('Location: ?action=showCreateForm');
            } else {
                $_SESSION['message'] = 'Employee created successfully!';
                header('Location: ?action=list&agreement_id=' . $_POST['agreement_id']);
            }
            exit;
        }
    }

    private function processBatch(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agreementId = (int)$_POST['agreement_id'];
            $designation = $_POST['designation'];
            $namesRaw = $_POST['names_list'] ?? '';

            if (!$agreementId || !$designation || !$namesRaw) {
                $_SESSION['error'] = "Missing required fields.";
                header('Location: ?action=showCreateForm');
                exit;
            }

            $agreementService = new AgreementService();
            $agreement = $agreementService->getAgreementById($agreementId);
            $startDate = $agreement->period_from;
            $endDate = $agreement->period_to;

            $names = preg_split('/[,\n\r]+/', $namesRaw);
            $dtos = [];
            $batchNames = [];

            foreach ($names as $name) {
                $name = trim($name);
                if (empty($name)) continue;

                $finalName = $this->getUniqueName($name, $agreementId, $batchNames);
                $batchNames[] = $finalName;

                $dtos[] = new EmployeeDTO((int)$this->currentUser['officeid'], $agreementId, $finalName, $designation, $startDate, $endDate, 'Sunday', false, '', '', '', '', '');
            }

            $result = $this->employeeService->batchCreateEmployees($dtos);
            $_SESSION['message'] = "Batch processed: {$result['success']} employees created.";
            header('Location: ?action=list&agreement_id=' . $agreementId);
            exit;
        }
    }

    private function getUniqueName(string $name, int $agreementId, array $batchNames): string {
        $roman = ['', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
        $count = 1;
        while (true) {
            $suffix = ($count > 1) ? "-" . $roman[$count-1] : "";
            $testName = $name . $suffix;
            if (!in_array($testName, $batchNames) && !$this->employeeService->nameExists($testName, $agreementId)) {
                return $testName;
            }
            $count++;
            if ($count > 10) break;
        }
        return $name . "-" . uniqid();
    }

    private function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        $agreementId = (int)($_GET['agreement_id'] ?? 0);
        $this->employeeService->deleteEmployee($id);
        header('Location: ?action=list&agreement_id=' . $agreementId);
        exit;
    }

    private function deleteAll(): void {
        $agreementId = (int)($_GET['agreement_id'] ?? 0);
        if ($agreementId > 0) {
            $this->employeeService->deleteAllByAgreement($agreementId);
            $_SESSION['message'] = "All employees for this agreement have been deleted.";
        }
        header('Location: ?action=list&agreement_id=' . $agreementId);
        exit;
    }

    private function downloadTemplate(): void {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="employee_batch_template.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Names (comma separated or new line)', 'Designation']);
        fclose($output);
        exit;
    }

    private function upload(): void {
        header('Location: ?action=list');
        exit;
    }
}

$controller = new EmployeeController();
$controller->handleRequest();
