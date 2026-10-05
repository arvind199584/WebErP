<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Logs\Services;

require_once __DIR__ . '/../Models/LogModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Logs\Models\LogModel;
use Exception;
use PDOException;

class LogService {
    protected $logModel;

    public function __construct() {
        $this->logModel = new LogModel();
    }

    public function getAllLogs(?int $officeId, ?string $month = null): array {
        return $this->logModel->getAllLogs($officeId, $month);
    }

    public function getAvailableMonths(?int $officeId): array {
        return $this->logModel->getAvailableMonths($officeId);
    }

    public function getLogsByDate(?int $officeId, string $date): array {
        return $this->logModel->getLogsByDate($officeId, $date);
    }

    public function getRecentDatesWithLogs(?int $officeId): array {
        return $this->logModel->getRecentDatesWithLogs($officeId);
    }

    private function validateLogValues(array $data): void {
        if (empty($data['machine_id'])) {
            throw new Exception("Machine is required for all logs.");
        }
        if (empty($data['log_date'])) {
            throw new Exception("Log date is required for all logs.");
        }
        
        if (isset($data['fuel_consumed_qty']) && $data['fuel_consumed_qty'] !== null && $data['fuel_consumed_qty'] !== '') {
            $fuel = (float)$data['fuel_consumed_qty'];
            if (fmod($fuel * 2, 1.0) != 0.0) {
                throw new Exception("Fuel quantity must be in steps of 0.5 (e.g., 0.5, 1.0, 1.5).");
            }
        }
        if (isset($data['running_hours']) && $data['running_hours'] !== null && $data['running_hours'] !== '') {
            $hours = (float)$data['running_hours'];
            if (round($hours, 1) != $hours) {
                throw new Exception("Running hours can have at most 1 decimal place (e.g., 100.1).");
            }
        }
    }

    public function createLog(array $data): int {
        if (empty($data['machine_id']) || empty($data['log_date'])) {
            throw new Exception("Machine and Date are required.");
        }
        $this->validateLogValues($data);
        return $this->logModel->createLog($data);
    }

    public function validateSingleStagedLog(array $logData, int $officeId): void {
        $machineId = (int)($logData['machine_id'] ?? 0);
        $logDate = trim((string)($logData['log_date'] ?? ''));
        
        if (empty($machineId)) {
            throw new Exception("Machine is required.");
        }
        if (empty($logDate) || !strtotime($logDate)) {
            throw new Exception("Valid log date is required.");
        }

        $cleanData = [
            'officeid'          => $officeId,
            'machine_id'        => $machineId,
            'log_date'          => $logDate,
            'fuel_consumed_qty' => (isset($logData['fuel_consumed_qty']) && trim((string)$logData['fuel_consumed_qty']) !== '') ? (float)$logData['fuel_consumed_qty'] : null,
            'running_hours'     => (isset($logData['running_hours']) && trim((string)$logData['running_hours']) !== '') ? (float)$logData['running_hours'] : null,
            'operator'          => !empty(trim((string)($logData['operator'] ?? ''))) ? ucwords(strtolower(trim((string)$logData['operator']))) : 'AUTO-GENERATED'
        ];

        $this->validateLogValues($cleanData);
        
        // Check database for existing duplicate record
        $existing = $this->logModel->getLogsByDate($officeId, $logDate);
        foreach ($existing as $row) {
            if ($row['machine_id'] == $machineId) {
                throw new Exception("This machine already has a log entry for $logDate in the database.");
            }
        }
    }

    public function createBulkLogs(array $logs, int $officeId): int {
        if (empty($logs)) {
            throw new Exception("No logs provided for bulk creation.");
        }

        // Pre-validate all logs BEFORE starting the transaction
        $sanitizedLogs = [];
        foreach ($logs as $index => $logData) {
            $machineId = (int)($logData['machine_id'] ?? 0);
            $logDate = trim((string)($logData['log_date'] ?? ''));
            
            if (empty($machineId)) {
                throw new Exception("Machine is required for all logs (Row " . ($index + 1) . ").");
            }
            if (empty($logDate) || !strtotime($logDate)) {
                throw new Exception("Valid log date is required for all logs (Row " . ($index + 1) . ").");
            }

            $cleanData = [
                'officeid'          => $officeId,
                'machine_id'        => $machineId,
                'log_date'          => $logDate,
                'fuel_consumed_qty' => (isset($logData['fuel_consumed_qty']) && trim((string)$logData['fuel_consumed_qty']) !== '') ? (float)$logData['fuel_consumed_qty'] : null,
                'running_hours'     => (isset($logData['running_hours']) && trim((string)$logData['running_hours']) !== '') ? (float)$logData['running_hours'] : null,
                'operator'          => !empty(trim((string)($logData['operator'] ?? ''))) ? ucwords(strtolower(trim((string)$logData['operator']))) : 'AUTO-GENERATED'
            ];

            $this->validateLogValues($cleanData);
            $sanitizedLogs[] = $cleanData;
        }

        $this->logModel->beginTransaction();
        try {
            $createdCount = 0;
            foreach ($sanitizedLogs as $cleanData) {
                $this->logModel->createLog($cleanData);
                $createdCount++;
            }
            $this->logModel->commit();
            return $createdCount;
        } catch (PDOException $e) {
            $this->logModel->rollBack();
            if ($e->getCode() == '23505' || strpos($e->getMessage(), 'unique_machine_date') !== false) {
                preg_match('/Key \(machine_id, log_date\)=\(\d+, (.*?)\)/', $e->getMessage(), $matches);
                $dateStr = isset($matches[1]) ? " on " . $matches[1] : "";
                throw new Exception("Database Constraint Error: One of the logs you are trying to insert already exists in the database$dateStr. The entire batch has been cancelled. Please check your data.", 0, $e);
            }
            throw new Exception("Database Error: " . $e->getMessage(), 0, $e);
        } catch (Exception $e) {
            $this->logModel->rollBack();
            throw $e;
        }
    }

    public function updateLogQuick(int $id, ?int $officeId, array $data): bool {
        if (empty($data['log_date'])) {
            throw new Exception("Date is required.");
        }
        $this->validateLogValues($data);
        return $this->logModel->updateLogQuick($id, $officeId, $data);
    }

    public function deleteLog(int $id, ?int $officeId): bool {
        return $this->logModel->deleteLog($id, $officeId);
    }

    public function findMachineAnomalies(int $machineId, ?int $officeId): array {
        $history = $this->logModel->getMachineChronologicalHistoryFull($machineId, $officeId);
        $anomalies = [];

        if (count($history) < 3) return $anomalies;

        $lastValidValue = -1;

        foreach ($history as $index => &$row) {
            $val = (float)($row['running_hours'] ?? 0);

            if ($val <= 0) {
                $row['is_anomaly'] = true;
                $row['type'] = 'Missing Data (Blank/Zero)';
                $row['running_hours'] = 0;
            } else {
                if ($val >= $lastValidValue) {
                    $row['is_anomaly'] = false;
                    $lastValidValue = $val;
                } else {
                    $row['is_anomaly'] = true;
                    $row['type'] = 'Impossible Drop';
                }
            }
        }
        unset($row);

        foreach ($history as $index => $row) {
            if ($row['is_anomaly']) {
                $prevAnchor = null;
                for ($i = $index - 1; $i >= 0; $i--) {
                    if (!$history[$i]['is_anomaly']) {
                        $prevAnchor = $history[$i];
                        break;
                    }
                }

                $nextAnchor = null;
                for ($i = $index + 1; $i < count($history); $i++) {
                    if (!$history[$i]['is_anomaly']) {
                        $nextAnchor = $history[$i];
                        break;
                    }
                }

                if (!$prevAnchor || !$nextAnchor) continue;

                $prevVal = (float)$prevAnchor['running_hours'];
                $nextVal = (float)$nextAnchor['running_hours'];
                $totalGap = $nextVal - $prevVal;

                $randomWeight = mt_rand(40, 60) / 100;
                $proposedFix = $prevVal + ($totalGap * $randomWeight);

                $proposedFix = round($proposedFix * 2) / 2;

                if ($proposedFix <= $prevVal) $proposedFix = $prevVal + 0.5;
                if ($proposedFix >= $nextVal) $proposedFix = $nextVal - 0.5;

                $anomalies[] = [
                    'id' => $row['id'],
                    'log_date' => $row['log_date'],
                    'prev_val' => $prevVal,
                    'curr_val' => $row['running_hours'],
                    'next_val' => $nextVal,
                    'proposed_val' => $proposedFix,
                    'type' => $row['type']
                ];
            }
        }

        return $anomalies;
    }

    public function applyAuditFix(int $logId, float $newHours): bool {
        return $this->logModel->updateRunningHours($logId, $newHours);
    }

    public function getLatestLogDate(?int $officeId): ?string {
        return $this->logModel->getLatestLogDate($officeId);
    }

    public function getMonthlyTrendsData(?int $officeId): array {
        return $this->logModel->getMonthlyTrendsData($officeId);
    }

    public function getExistingLogsMap(?int $officeId): array {
        return $this->logModel->getExistingLogsMap($officeId);
    }
}
