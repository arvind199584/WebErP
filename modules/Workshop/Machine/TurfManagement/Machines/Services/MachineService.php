<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Machines\Services;

require_once __DIR__ . '/../Models/MachineModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Machines\Models\MachineModel;
use Exception;

class MachineService {
    protected $machineModel;

    public function __construct() {
        $this->machineModel = new MachineModel();
    }

    public function getAllMachines(?int $officeId): array {
        return $this->machineModel->getAllMachines($officeId);
    }

    public function getMachineById(int $id, ?int $officeId): ?array {
        return $this->machineModel->getMachineById($id, $officeId);
    }

    public function createMachine(array $data): int {
        if (empty($data['name'])) {
            throw new Exception("Machine name is required.");
        }
        return $this->machineModel->createMachine($this->sanitizeMachineData($data));
    }

    public function updateMachine(int $id, int $officeId, array $data): bool {
        if (empty($data['name'])) {
            throw new Exception("Machine name is required.");
        }
        $data['officeid'] = $officeId;
        $sanitized = $this->sanitizeMachineData($data);
        if (isset($data['default_service_items'])) {
            $sanitized['default_service_items'] = $data['default_service_items'];
        }
        return $this->machineModel->updateMachine($id, $officeId, $sanitized);
    }

    public function deleteMachine(int $id, ?int $officeId): bool {
        return $this->machineModel->deleteMachine($id, $officeId);
    }

    public function addCustomServiceType(int $machineId, string $newType): array {
        return $this->machineModel->addCustomServiceType($machineId, $newType);
    }

    public function addCustomWorkType(int $machineId, string $newWork): array {
        return $this->machineModel->addCustomWorkType($machineId, $newWork);
    }

    public function updateDefaultServiceItems(int $machineId, array $items): bool {
        return $this->machineModel->updateDefaultServiceItems($machineId, $items);
    }

    public function updateServiceMilestone(int $machineId, float $serviceDoneHours, float $serviceDueHours): bool {
        return $this->machineModel->updateServiceMilestone($machineId, $serviceDoneHours, $serviceDueHours);
    }

    public function getMachinesWithServiceStatus(?int $officeId = null, float $thresholdHours = 20.0): array {
        return $this->machineModel->getMachinesWithServiceStatus($officeId, $thresholdHours);
    }

    private function sanitizeMachineData(array $data): array {
        $runduration = (isset($data['runduration']) && $data['runduration'] == '1') ? 1 : 0;
        $daily_run = ($runduration === 1 && isset($data['daily_run']) && $data['daily_run'] == '1') ? 1 : 0;
        $has_rest_day = ($daily_run === 1 && isset($data['has_rest_day']) && $data['has_rest_day'] == '1') ? 1 : 0;
        $rest_day = ($has_rest_day === 1) ? (int)($data['rest_day'] ?? 4) : 4;

        return [
            'name' => $data['name'],
            'make' => $data['make'] ?? null,
            'fuel_item_id' => !empty($data['fuel_item_id']) ? (int)$data['fuel_item_id'] : null,
            'status' => $data['status'] ?? 'Working',
            'officeid' => $data['officeid'] ?? null,
            'runduration' => $runduration,
            'daily_run' => $daily_run,
            'has_rest_day' => $has_rest_day,
            'rest_day' => $rest_day,
            'service_interval_hours' => !empty($data['service_interval_hours']) ? (float)$data['service_interval_hours'] : 100,
            'engine_oil_qty' => !empty($data['engine_oil_qty']) ? (float)$data['engine_oil_qty'] : 0.00,
        ];
    }
}
