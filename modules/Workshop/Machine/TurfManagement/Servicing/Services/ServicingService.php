<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Servicing\Services;

require_once __DIR__ . '/../Models/ServicingModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Servicing\Models\ServicingModel;
use Exception;

class ServicingService {
    protected $servicingModel;

    public function __construct() {
        $this->servicingModel = new ServicingModel();
    }

    public function getAllServicingLogs(?int $officeId, ?int $machineId = null): array {
        return $this->servicingModel->getAllServicingLogs($officeId, $machineId);
    }

    public function getServicingById(int $id, ?int $officeId): ?array {
        return $this->servicingModel->getServicingById($id, $officeId);
    }

    private function validateServicingData(array $data, ?array $machine = null): void {
        if (empty($data['machine_id'])) {
            throw new Exception("Machine is required.");
        }
        if (empty($data['service_date'])) {
            throw new Exception("Service date is required.");
        }

        // If machine tracks run duration, hours_at_service is required
        if ($machine && !empty($machine['runduration'])) {
            if (!isset($data['hours_at_service']) || trim((string)$data['hours_at_service']) === '') {
                throw new Exception("Machine Hours at Service is required for this machine.");
            }
            if ((float)$data['hours_at_service'] < 0) {
                throw new Exception("Hours at Service cannot be negative.");
            }
        }

        if (isset($data['cost']) && (float)$data['cost'] < 0) {
            throw new Exception("Cost cannot be negative.");
        }
    }

    public function createServicing(array $data, int $officeId, ?array $machine = null): int {
        $data['officeid'] = $officeId;
        $this->validateServicingData($data, $machine);

        return $this->servicingModel->createServicing($data);
    }

    public function updateServicing(int $id, ?int $officeId, array $data): bool {
        $this->validateServicingData($data);
        return $this->servicingModel->updateServicing($id, $officeId, $data);
    }

    public function deleteServicing(int $id, ?int $officeId): bool {
        return $this->servicingModel->deleteServicing($id, $officeId);
    }

    public function getMachineServiceDueStatus(?int $officeId): array {
        return $this->servicingModel->getMachineServiceDueStatus($officeId);
    }
}
