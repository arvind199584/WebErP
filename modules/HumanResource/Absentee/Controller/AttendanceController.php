<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Absentee\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AttendanceService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\HumanResource\Absentee\Services\AttendanceService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use Exception;

class AttendanceController extends BaseController {
    private AttendanceService $attendanceService;
    public function __construct() {
        parent::__construct('Attendance');
        $this->attendanceService = new AttendanceService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index': $this->index(); break;
                case 'mark': $this->showMarkSheet(); break;
                case 'saveMark': $this->saveMark(); break;
                case 'edit': $this->showEditSheet(); break;
                case 'saveSheet': $this->saveSheet(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=index");
            exit;
        }
    }

    private function index(): void {
        $this->render(__DIR__ . '/../Views/index.php');
    }

    private function showMarkSheet(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $selectedAgreementId = $_GET['agreement_id'] ?? null;
        $selectedMonth = $_GET['month'] ?? date('Y-m');

        $employees = $selectedAgreementId ? $this->attendanceService->getUnmarkedEmployees((int)$selectedAgreementId, $selectedMonth) : [];

        $this->render(__DIR__ . '/../Views/mark.php', [
            'agreements' => $agreements, 'employees' => $employees,
            'selectedAgreementId' => $selectedAgreementId, 'selectedMonth' => $selectedMonth
        ]);
    }

    private function saveMark(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->attendanceService->saveEmployeeAttendance(
                (int)$_POST['employee_id'], (int)$this->currentUser['officeid'], $_POST['month'],
                $_POST['present_days'] ?? '', $_POST['absent_days'] ?? '',
                $_POST['leave_days'] ?? '', $_POST['rest_days'] ?? ''
            );
            $_SESSION['message'] = 'Attendance saved for employee.';
            header('Location: ?action=mark&agreement_id=' . $_POST['agreement_id'] . '&month=' . $_POST['month']);
            exit;
        }
    }

    private function showEditSheet(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $selectedAgreementId = $_GET['agreement_id'] ?? null;
        $selectedMonth = $_GET['month'] ?? date('Y-m');

        $attendanceData = $selectedAgreementId ? $this->attendanceService->getAttendanceGridForMonth((int)$selectedAgreementId, $selectedMonth) : [];

        $this->render(__DIR__ . '/../Views/edit_sheet.php', [
            'agreements' => $agreements, 'attendanceData' => $attendanceData,
            'selectedAgreementId' => $selectedAgreementId, 'selectedMonth' => $selectedMonth
        ]);
    }

    private function saveSheet(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->attendanceService->saveAttendanceSheet($_POST['attendance'], (int)$this->currentUser['officeid']);
            $_SESSION['message'] = 'Attendance sheet updated successfully!';
            header('Location: ?action=edit&agreement_id=' . $_POST['agreement_id'] . '&month=' . $_POST['month']);
            exit;
        }
    }
}

$controller = new AttendanceController();
$controller->handleRequest();
