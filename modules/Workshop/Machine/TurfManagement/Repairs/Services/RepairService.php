<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Repairs\Services;

require_once __DIR__ . '/../Models/RepairModel.php';
require_once __DIR__ . '/../../SpareParts/Models/SparePartModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Repairs\Models\RepairModel;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Models\SparePartModel;
use App\Core\Database;
use Exception;
use PDO;

class RepairService {
    protected $repairModel;
    protected $sparePartModel;

    public function __construct() {
        $this->repairModel = new RepairModel();
        $this->sparePartModel = new SparePartModel();
    }

    public function getAllRepairs(?int $officeId, ?int $machineId = null): array {
        return $this->repairModel->getAllRepairs($officeId, $machineId);
    }

    public function getRepairById(int $id, ?int $officeId): ?array {
        return $this->repairModel->getRepairById($id, $officeId);
    }

    private function validateRepairData(array $data, array $items = []): void {
        if (empty($data['machine_id'])) {
            throw new Exception("Machine is required.");
        }
        if (empty($data['repair_date'])) {
            throw new Exception("Repair date is required.");
        }
        if (empty(trim((string)($data['work_done'] ?? '')))) {
            throw new Exception("Description of work done is required.");
        }
        if (isset($data['cost']) && (float)$data['cost'] < 0) {
            throw new Exception("Repair cost cannot be negative.");
        }

        $workType = $data['work_type'] ?? 'repair';
        $jobCardNo = trim((string)($data['job_card_no'] ?? ''));

        // If Service Done: Job Card, Running Hours, and Engine Oil are positively mandatory
        if ($workType === 'service_done') {
            if (empty($jobCardNo)) {
                throw new Exception("Job Card Number is mandatory when recording 'Service Done'.");
            }
            if (!isset($data['running_hours']) || trim((string)$data['running_hours']) === '') {
                throw new Exception("Running hours (meter reading) is mandatory when recording 'Service Done'.");
            }
            if (empty($items)) {
                throw new Exception("Engine Oil and any necessary consumables must be entered for 'Service Done'.");
            }

            // Engine Oil is a MUST requirement for every machine servicing
            $db = Database::getInstance()->getConnection();
            $hasEngineOil = false;
            foreach ($items as $item) {
                $pid = (int)($item['spare_part_id'] ?? 0);
                $pqty = (float)($item['quantity'] ?? 0);
                if ($pid > 0 && $pqty > 0) {
                    $stmt = $db->prepare("SELECT nomenclature FROM turf_machine_spare_parts WHERE id = :id");
                    $stmt->execute(['id' => $pid]);
                    $nom = $stmt->fetchColumn();
                    if ($nom && stripos($nom, 'Engine Oil') !== false) {
                        $hasEngineOil = true;
                        break;
                    }
                }
            }
            if (!$hasEngineOil) {
                throw new Exception("Engine Oil is a mandatory requirement for servicing. Please ensure Engine Oil quantity is specified.");
            }
        }

        // If General Repair uses spare parts: Job Card is mandatory
        if ($workType === 'repair' && !empty($items)) {
            if (empty($jobCardNo)) {
                throw new Exception("Job Card Number is required when spare parts are issued for a repair.");
            }
        }

        // Validate items validity
        if (!empty($items)) {
            $db = Database::getInstance()->getConnection();
            foreach ($items as $item) {
                $partId = (int)($item['spare_part_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                if ($partId <= 0 || $qty <= 0) {
                    throw new Exception("Invalid spare part or quantity specified.");
                }

                $stmt = $db->prepare("SELECT nomenclature, part_no, current_stock, unit FROM turf_machine_spare_parts WHERE id = :id");
                $stmt->execute(['id' => $partId]);
                $part = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$part) {
                    throw new Exception("Spare part ID #{$partId} not found in catalog.");
                }
            }
        }
    }

    public function createRepair(array $data, int $officeId, array $items = []): int {
        $data['officeid'] = $officeId;
        $this->validateRepairData($data, $items);

        // Build descriptive spare_parts_used summary if items were passed
        if (!empty($items)) {
            $db = Database::getInstance()->getConnection();
            $partsSummaries = [];
            foreach ($items as $item) {
                $stmt = $db->prepare("SELECT nomenclature, part_no, unit FROM turf_machine_spare_parts WHERE id = :id");
                $stmt->execute(['id' => $item['spare_part_id']]);
                $part = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($part) {
                    $partsSummaries[] = ($part['part_no'] ? $part['part_no'] . ' ' : '') . $part['nomenclature'] . " (Qty: " . $item['quantity'] . " " . ($part['unit'] ?? 'Pcs') . ")";
                }
            }
            if (!empty($partsSummaries)) {
                $generatedSummary = implode(", ", $partsSummaries);
                if (!empty($data['spare_parts_used'])) {
                    $data['spare_parts_used'] = trim($data['spare_parts_used']) . " | " . $generatedSummary;
                } else {
                    $data['spare_parts_used'] = $generatedSummary;
                }
            }
        }

        return $this->repairModel->createRepair($data, $items);
    }

    public function updateRepair(int $id, ?int $officeId, array $data): bool {
        $this->validateRepairData($data);
        return $this->repairModel->updateRepair($id, $officeId, $data);
    }

    public function deleteRepair(int $id, ?int $officeId): bool {
        return $this->repairModel->deleteRepair($id, $officeId);
    }

    public function getJobCardsSummary(?int $officeId, array $filters = []): array {
        return $this->repairModel->getJobCardsSummary($officeId, $filters);
    }
}
