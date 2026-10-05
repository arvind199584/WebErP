<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Controller;

require_once __DIR__ . '/../../../../vendor/autoload.php'; // Assuming PhpSpreadsheet is installed via Composer
require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AttendanceReportService.php';
require_once __DIR__ . '/../../../../modules/Admin/Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\AI_and_Tools\ReportPrinting\Services\AttendanceReportService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceReportController extends BaseController {
    private AttendanceReportService $service;

    public function __construct() {
        parent::__construct('AI_and_Tools/ReportPrinting');
        $this->service = new AttendanceReportService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        switch ($action) {
            case 'index': $this->index(); break;
            case 'print': $this->print(); break;
            case 'exportExcel': $this->exportExcel(); break; // New action for Excel export
        }
    }

    private function index(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $this->render(__DIR__ . '/../Views/Attendance/index.php', ['agreements' => $agreements]);
    }

    private function print(): void {
        $agreementId = (int)$_GET['agreement_id'];
        $month = $_GET['month'];
        $reportData = $this->service->getAttendanceReport($agreementId, $month);
        // RENDER WITHOUT LAYOUT
        $this->render(__DIR__ . '/../Views/Attendance/print.php', [
            'header' => $reportData['header'],
            'data' => $reportData['data'],
            'daysInMonth' => $reportData['daysInMonth'],
            'month' => $month
        ], false);
    }

    private function exportExcel(): void {
        $agreementId = (int)$_GET['agreement_id'];
        $month = $_GET['month'];
        $reportData = $this->service->getAttendanceReport($agreementId, $month);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance Report');

        // Add header information
        $sheet->setCellValue('A1', 'Agreement: ' . ($reportData['header']['agreement_name'] ?? 'N/A'));
        $sheet->setCellValue('A2', 'Month: ' . $month);
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        // Add table headers
        $column = 'A';
        $row = 4;
        $sheet->setCellValue($column++ . $row, 'Employee Name');
        $sheet->setCellValue($column++ . $row, 'Employee ID');
        $sheet->setCellValue($column++ . $row, 'Designation');
        $sheet->setCellValue($column++ . $row, 'Total Present');
        $sheet->setCellValue($column++ . $row, 'Total Absent');
        $sheet->setCellValue($column++ . $row, 'Total Rest Day');

        // Add day headers
        for ($i = 1; $i <= $reportData['daysInMonth']; $i++) {
            $sheet->setCellValue($column++ . $row, $i);
        }
        $sheet->getStyle('A' . $row . ':' . --$column . $row)->getFont()->setBold(true);

        // Add data rows
        $row++;
        foreach ($reportData['data'] as $employee) {
            $column = 'A';
            $sheet->setCellValue($column++ . $row, $employee['name']);
            $sheet->setCellValue($column++ . $row, $employee['id']);
            $sheet->setCellValue($column++ . $row, $employee['designation']);
            $sheet->setCellValue($column++ . $row, $employee['summary']['P']);
            $sheet->setCellValue($column++ . $row, $employee['summary']['A']);
            $sheet->setCellValue($column++ . $row, $employee['summary']['R']);

            foreach ($employee['days'] as $dayStatus) {
                $sheet->setCellValue($column++ . $row, $dayStatus);
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Attendance_Report_' . $month . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

$controller = new AttendanceReportController();
$controller->handleRequest();
