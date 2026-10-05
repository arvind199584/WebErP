<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\WaterLog\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/WaterLogService.php';
require_once __DIR__ . '/../DTO/WaterLogDTO.php';
require_once __DIR__ . '/../../../../../../vendor/autoload.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\WaterLog\Services\WaterLogService;
use App\Modules\Workshop\Machine\TurfManagement\WaterLog\DTO\WaterLogDTO;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WaterLogController extends BaseController
{
    private WaterLogService $waterLogService;

    public function __construct()
    {
        parent::__construct('TurfManagement');
        $this->waterLogService = new WaterLogService();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'list';

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                    $this->list();
                    break;
                case 'create':
                    $this->create();
                    break;
                case 'edit':
                    $this->edit();
                    break;
                case 'update':
                    $this->update();
                    break;
                case 'delete':
                    $this->delete();
                    break;
                case 'exportTemplate':
                    $this->exportTemplate();
                    break;
                case 'import':
                    $this->import();
                    break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void
    {
        $logs = $this->waterLogService->getAll((int)$this->currentUser['officeid']);
        $this->render(__DIR__ . '/../Views/index.php', ['logs' => $logs]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dto = WaterLogDTO::fromRequest($_POST, (int)$this->currentUser['officeid']);
            $dto->validate();
            $this->waterLogService->create($dto);
            $_SESSION['message'] = 'Log created successfully!';
            header('Location: ?action=list');
            exit;
        }
        $this->render(__DIR__ . '/../Views/create.php');
    }

    private function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $log = $this->waterLogService->getById($id, (int)$this->currentUser['officeid']);
        if (!$log) {
            throw new Exception("Log not found.");
        }
        $this->render(__DIR__ . '/../Views/edit.php', ['log' => $log]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dto = WaterLogDTO::fromRequest($_POST, (int)$this->currentUser['officeid']);
            $dto->validate();
            $this->waterLogService->update($dto);
            $_SESSION['message'] = 'Log updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->waterLogService->delete($id, (int)$this->currentUser['officeid']);
        $_SESSION['message'] = 'Log deleted successfully!';
        header('Location: ?action=list');
        exit;
    }

    private function exportTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('WaterLog Template');

        $headers = ['date', 'morning_opening', 'morning_closing', 'evening_opening', 'evening_closing'];
        $sheet->fromArray($headers, null, 'A1');

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="waterlog_template.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    private function import(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
            throw new Exception("Invalid request.");
        }

        $file = $_FILES['excel_file']['tmp_name'];
        if (!file_exists($file)) {
            throw new Exception("File not found.");
        }

        $fileType = IOFactory::identify($file);
        $reader = IOFactory::createReader($fileType);
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        // Skip header row
        unset($sheetData[1]);

        $db = $this->waterLogService->getDb();
        $officeId = (int)$this->currentUser['officeid'];

        try {
            $db->beginTransaction();

            foreach ($sheetData as $row) {
                if (empty($row['A'])) continue; // Skip empty rows

                $rawDate = $row['A'];
                $timestamp = is_numeric($rawDate) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float)$rawDate) : strtotime((string)$rawDate);
                
                if (!$timestamp) {
                    throw new Exception("Invalid date format in row: " . implode(', ', $row));
                }
                
                $date = date('Y-m-d', $timestamp);
                
                $data = [
                    'date' => $date,
                    'morning_opening' => $row['B'],
                    'morning_closing' => $row['C'],
                    'evening_opening' => $row['D'],
                    'evening_closing' => $row['E'],
                ];

                $existing = $this->waterLogService->findByDate($date, $officeId);
                
                if ($existing) {
                    $data['id'] = $existing['id'];
                }

                $dto = WaterLogDTO::fromRequest($data, $officeId);
                $dto->validate();

                if ($existing) {
                    $this->waterLogService->update($dto);
                } else {
                    $this->waterLogService->create($dto);
                }
            }

            $db->commit();
            $_SESSION['message'] = 'Data imported successfully!';
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception("Import failed: " . $e->getMessage());
        }

        header('Location: ?action=list');
        exit;
    }
}

$controller = new WaterLogController();
$controller->handleRequest();
