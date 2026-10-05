<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\WaterLog\Services;

require_once __DIR__ . '/../Models/WaterLogModel.php';
require_once __DIR__ . '/../DTO/WaterLogDTO.php';

use App\Modules\Workshop\Machine\TurfManagement\WaterLog\Models\WaterLogModel;
use App\Modules\Workshop\Machine\TurfManagement\WaterLog\DTO\WaterLogDTO;

class WaterLogService
{
    private WaterLogModel $waterLogModel;

    public function __construct()
    {
        $this->waterLogModel = new WaterLogModel();
    }

    public function getDb(): \PDO
    {
        return $this->waterLogModel->getDb();
    }

    public function findByDate(string $date, int $office_id): ?array
    {
        return $this->waterLogModel->findByDate($date, $office_id);
    }

    public function getAll(int $office_id): array
    {
        $logs = $this->waterLogModel->getAll($office_id);
        $cumulativeConsumption = 0;
        $currentMonth = null;

        // Sort logs by date ascending to calculate cumulative consumption correctly
        usort($logs, fn($a, $b) => strcmp($a['date'], $b['date']));

        $processedLogs = [];
        foreach ($logs as $log) {
            $month = substr($log['date'], 0, 7); // Format: YYYY-MM
            if ($month !== $currentMonth) {
                $currentMonth = $month;
                $cumulativeConsumption = 0;
            }

            $morningConsumption = (($log['morning_closing'] ?? 0) - ($log['morning_opening'] ?? 0)) * 1000;
            $eveningConsumption = (($log['evening_closing'] ?? 0) - ($log['evening_opening'] ?? 0)) * 1000;
            $totalConsumption = $morningConsumption + $eveningConsumption;
            $cumulativeConsumption += $totalConsumption;

            $processedLogs[] = array_merge($log, [
                'morning_consumption' => $morningConsumption,
                'evening_consumption' => $eveningConsumption,
                'total_consumption' => $totalConsumption,
                'cumulative_consumption' => $cumulativeConsumption,
            ]);
        }

        // Sort back to descending for display
        usort($processedLogs, fn($a, $b) => strcmp($b['date'], $a['date']));

        return $processedLogs;
    }

    public function getById(int $id, int $office_id): ?array
    {
        return $this->waterLogModel->getById($id, $office_id);
    }

    public function create(WaterLogDTO $dto): void
    {
        $this->waterLogModel->create($dto);
    }

    public function update(WaterLogDTO $dto): void
    {
        $this->waterLogModel->update($dto);
    }

    public function delete(int $id, int $office_id): void
    {
        $this->waterLogModel->delete($id, $office_id);
    }
}
