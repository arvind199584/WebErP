<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Machines\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class MachineModel extends BaseModel {
    protected $table = 'turf_machines';

    public function __construct() {
        parent::__construct();
        $this->ensureServiceTypesColumn();
    }

    private function ensureServiceTypesColumn(): void {
        try {
            $this->db->exec("ALTER TABLE public.turf_machines ADD COLUMN IF NOT EXISTS custom_service_types JSONB DEFAULT '[]'::jsonb;");
            $this->db->exec("ALTER TABLE public.turf_machines ADD COLUMN IF NOT EXISTS custom_work_types JSONB DEFAULT '[]'::jsonb;");
            $this->db->exec("ALTER TABLE public.turf_machines ADD COLUMN IF NOT EXISTS default_service_items JSONB DEFAULT '[]'::jsonb;");
            $this->db->exec("ALTER TABLE public.turf_machines ADD COLUMN IF NOT EXISTS engine_oil_qty NUMERIC(10, 2) DEFAULT 0.00;");
        } catch (\Throwable $e) {
            // Ignore if column exists
        }
    }

    public function getAllMachines(?int $officeId): array {
        $sql = "SELECT
                    m.*,
                    i.description as fuel_type_name,
                    o.OfficeName as office_name
                FROM {$this->table} m
                LEFT JOIN turf_inventory_items i ON m.fuel_item_id = i.id
                JOIN office o ON m.officeid = o.Officeid";

        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE m.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY o.OfficeName, m.name";
        return $this->fetchAll($sql, $params);
    }

    public function getMachineById(int $id, ?int $officeId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];
        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        return $this->fetchRow($sql, $params);
    }

    public function createMachine(array $data): int {
        $serviceInterval = (float)($data['service_interval_hours'] ?? 100);
        $serviceDone = (float)($data['service_done'] ?? 0.00);
        $serviceDue = isset($data['service_due']) && $data['service_due'] !== '' 
            ? (float)$data['service_due'] 
            : ($serviceDone + $serviceInterval);
        $engineOilQty = (float)($data['engine_oil_qty'] ?? 0.00);

        $defaultServiceItemsJson = null;
        if (isset($data['default_service_items'])) {
            $defaultServiceItemsJson = is_string($data['default_service_items'])
                ? $data['default_service_items']
                : json_encode($data['default_service_items']);
        }

        $sql = "INSERT INTO {$this->table} (officeid, name, make, fuel_item_id, runduration, status, daily_run, has_rest_day, rest_day, service_interval_hours, service_interval, service_done, service_due, engine_oil_qty"
                . ($defaultServiceItemsJson !== null ? ", default_service_items" : "") . ")
                VALUES (:officeid, :name, :make, :fuel_item_id, :runduration, :status, :daily_run, :has_rest_day, :rest_day, :service_interval_hours, :service_interval, :service_done, :service_due, :engine_oil_qty"
                . ($defaultServiceItemsJson !== null ? ", :default_service_items" : "") . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':officeid', (int)$data['officeid'], PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':make', $data['make'] ?? null, ($data['make'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':fuel_item_id', !empty($data['fuel_item_id']) ? (int)$data['fuel_item_id'] : null, !empty($data['fuel_item_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':runduration', filter_var($data['runduration'] ?? false, FILTER_VALIDATE_BOOLEAN), PDO::PARAM_BOOL);
        $stmt->bindValue(':status', $data['status'] ?? 'Working');
        $stmt->bindValue(':daily_run', filter_var($data['daily_run'] ?? false, FILTER_VALIDATE_BOOLEAN), PDO::PARAM_BOOL);
        $stmt->bindValue(':has_rest_day', filter_var($data['has_rest_day'] ?? false, FILTER_VALIDATE_BOOLEAN), PDO::PARAM_BOOL);
        $stmt->bindValue(':rest_day', (int)($data['rest_day'] ?? 4), PDO::PARAM_INT);
        $stmt->bindValue(':service_interval_hours', $serviceInterval);
        $stmt->bindValue(':service_interval', $serviceInterval);
        $stmt->bindValue(':service_done', $serviceDone);
        $stmt->bindValue(':service_due', $serviceDue);
        $stmt->bindValue(':engine_oil_qty', $engineOilQty);

        if ($defaultServiceItemsJson !== null) {
            $stmt->bindValue(':default_service_items', $defaultServiceItemsJson);
        }

        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function updateMachine(int $id, int $officeId, array $data): bool {
        $serviceInterval = (float)($data['service_interval_hours'] ?? 100);
        $serviceDone = (float)($data['service_done'] ?? 0.00);
        $serviceDue = isset($data['service_due']) && $data['service_due'] !== '' 
            ? (float)$data['service_due'] 
            : ($serviceDone + $serviceInterval);
        $engineOilQty = (float)($data['engine_oil_qty'] ?? 0.00);

        $defaultServiceItemsJson = null;
        if (isset($data['default_service_items'])) {
            $defaultServiceItemsJson = is_string($data['default_service_items'])
                ? $data['default_service_items']
                : json_encode($data['default_service_items']);
        }

        $sql = "UPDATE {$this->table} SET
                    name = :name,
                    make = :make,
                    fuel_item_id = :fuel_item_id,
                    runduration = :runduration,
                    status = :status,
                    officeid = :officeid,
                    daily_run = :daily_run,
                    has_rest_day = :has_rest_day,
                    rest_day = :rest_day,
                    service_interval_hours = :service_interval_hours,
                    service_interval = :service_interval,
                    service_done = :service_done,
                    service_due = :service_due,
                    engine_oil_qty = :engine_oil_qty"
                    . ($defaultServiceItemsJson !== null ? ", default_service_items = :default_service_items" : "") . ",
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':make', $data['make'] ?? null, ($data['make'] ?? null) !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':fuel_item_id', !empty($data['fuel_item_id']) ? (int)$data['fuel_item_id'] : null, !empty($data['fuel_item_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':runduration', filter_var($data['runduration'] ?? false, FILTER_VALIDATE_BOOLEAN), PDO::PARAM_BOOL);
        $stmt->bindValue(':status', $data['status'] ?? 'Working');
        $stmt->bindValue(':officeid', $officeId, PDO::PARAM_INT);
        $stmt->bindValue(':daily_run', filter_var($data['daily_run'] ?? false, FILTER_VALIDATE_BOOLEAN), PDO::PARAM_BOOL);
        $stmt->bindValue(':has_rest_day', filter_var($data['has_rest_day'] ?? false, FILTER_VALIDATE_BOOLEAN), PDO::PARAM_BOOL);
        $stmt->bindValue(':rest_day', (int)($data['rest_day'] ?? 4), PDO::PARAM_INT);
        $stmt->bindValue(':service_interval_hours', $serviceInterval);
        $stmt->bindValue(':service_interval', $serviceInterval);
        $stmt->bindValue(':service_done', $serviceDone);
        $stmt->bindValue(':service_due', $serviceDue);
        $stmt->bindValue(':engine_oil_qty', $engineOilQty);

        if ($defaultServiceItemsJson !== null) {
            $stmt->bindValue(':default_service_items', $defaultServiceItemsJson);
        }

        return $stmt->execute();
    }

    public function deleteMachine(int $id, ?int $officeId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];
        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function addCustomServiceType(int $machineId, string $newType): array {
        $machine = $this->getMachineById($machineId, null);
        if (!$machine) return [];

        $existing = json_decode($machine['custom_service_types'] ?? '[]', true) ?: [];
        $newTypeClean = trim($newType);
        
        if ($newTypeClean !== '' && !in_array($newTypeClean, $existing, true)) {
            $existing[] = $newTypeClean;
            $updatedJson = json_encode(array_values($existing));
            $sql = "UPDATE {$this->table} SET custom_service_types = :json_types WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['json_types' => $updatedJson, 'id' => $machineId]);
        }

        return $existing;
    }

    public function addCustomWorkType(int $machineId, string $newWork): array {
        $machine = $this->getMachineById($machineId, null);
        if (!$machine) return [];

        $existing = json_decode($machine['custom_work_types'] ?? '[]', true) ?: [];
        $newWorkClean = trim($newWork);

        if ($newWorkClean !== '' && !in_array($newWorkClean, $existing, true)) {
            $existing[] = $newWorkClean;
            $updatedJson = json_encode(array_values($existing));
            $sql = "UPDATE {$this->table} SET custom_work_types = :json_works WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['json_works' => $updatedJson, 'id' => $machineId]);
        }

        return $existing;
    }

    public function updateDefaultServiceItems(int $machineId, array $items): bool {
        $cleaned = [];
        foreach ($items as $item) {
            $partId = (int)($item['spare_part_id'] ?? 0);
            $qty = (float)($item['quantity'] ?? 0);
            if ($partId > 0 && $qty > 0) {
                $cleaned[] = [
                    'spare_part_id' => $partId,
                    'quantity' => $qty
                ];
            }
        }
        $jsonItems = json_encode($cleaned);
        $sql = "UPDATE {$this->table} SET default_service_items = :json_items, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['json_items' => $jsonItems, 'id' => $machineId]);
    }

    public function updateServiceMilestone(int $machineId, float $serviceDoneHours, float $serviceDueHours): bool {
        $sql = "UPDATE {$this->table} SET 
                    service_done = :service_done, 
                    service_due = :service_due, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'service_done' => $serviceDoneHours,
            'service_due' => $serviceDueHours,
            'id' => $machineId
        ]);
    }

    /**
     * Retrieves all machines with comprehensive service due / overdue calculations.
     * Evaluates current meter reading from consumption logs against service intervals & servicing history.
     */
    public function getMachinesWithServiceStatus(?int $officeId = null, float $thresholdHours = 20.0): array {
        $sql = "SELECT 
                    tm.id, 
                    tm.name, 
                    tm.make,
                    tm.runduration,
                    tm.status AS machine_status,
                    tm.service_interval_hours, 
                    tm.service_done, 
                    tm.service_due, 
                    tm.engine_oil_qty,
                    tm.default_service_items,
                    tm.fuel_item_id,
                    inv.description AS fuel_name,
                    latest_serv.service_date AS last_service_date, 
                    latest_serv.hours_at_service AS last_service_hours, 
                    latest_serv.next_service_due AS log_next_due,
                    latest_serv.serviced_by,
                    latest_serv.job_card_no AS last_job_card_no,
                    latest_log.max_hours AS current_meter,
                    latest_log.last_run_date,
                    o.Officeid AS office_id,
                    o.OfficeName AS office_name
                FROM {$this->table} tm
                JOIN office o ON tm.officeid = o.Officeid
                LEFT JOIN turf_inventory_items inv ON tm.fuel_item_id = inv.id
                LEFT JOIN (
                    SELECT DISTINCT ON (machine_id) machine_id, service_date, hours_at_service, next_service_due, serviced_by, job_card_no
                    FROM turf_servicing_log 
                    ORDER BY machine_id, service_date DESC, id DESC
                ) latest_serv ON tm.id = latest_serv.machine_id 
                LEFT JOIN (
                    SELECT machine_id, MAX(running_hours) AS max_hours, MAX(log_date) AS last_run_date 
                    FROM turf_consumption_log 
                    WHERE running_hours IS NOT NULL
                    GROUP BY machine_id
                ) latest_log ON tm.id = latest_log.machine_id";

        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE tm.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY tm.name ASC";
        $rows = $this->fetchAll($sql, $params);

        $results = [];
        foreach ($rows as $row) {
            $interval = (float)($row['service_interval_hours'] ?: 100);
            $currentMeter = $row['current_meter'] !== null ? (float)$row['current_meter'] : null;

            // Determine last service milestone and next due reading
            $lastServiceHours = null;
            $nextServiceDue = null;

            if ((float)$row['service_done'] > 0 && (float)$row['service_due'] > (float)$row['service_done']) {
                $lastServiceHours = (float)$row['service_done'];
                $nextServiceDue = (float)$row['service_due'];
            } elseif ($row['last_service_hours'] !== null) {
                $lastServiceHours = (float)$row['last_service_hours'];
                $logNext = (float)$row['log_next_due'];
                if ($logNext > $lastServiceHours) {
                    $nextServiceDue = $logNext;
                } else {
                    $nextServiceDue = $lastServiceHours + $interval;
                }
            } else {
                $lastServiceHours = (float)($row['service_done'] ?? 0);
                $nextServiceDue = (float)($row['service_due'] ?: $interval);
            }

            // Status determination
            $remainingHours = null;
            $hoursOverdue = 0.0;
            $serviceProgressPercent = 0.0;

            if ($currentMeter !== null && $currentMeter > 0) {
                $remainingHours = round($nextServiceDue - $currentMeter, 2);
                $runSinceLastService = max(0.0, $currentMeter - $lastServiceHours);
                $serviceProgressPercent = ($interval > 0) ? min(150.0, round(($runSinceLastService / $interval) * 100, 1)) : 0.0;

                if ($remainingHours <= 0) {
                    $hoursOverdue = abs($remainingHours);
                    $statusKey = 'overdue';
                    $statusLabel = 'Service Overdue';
                    $statusBadge = 'danger';
                    $sortOrder = 1;
                } elseif ($remainingHours <= $thresholdHours) {
                    $statusKey = 'due_soon';
                    $statusLabel = 'Due Soon';
                    $statusBadge = 'warning';
                    $sortOrder = 2;
                } else {
                    $statusKey = 'good';
                    $statusLabel = 'On Schedule';
                    $statusBadge = 'success';
                    $sortOrder = 3;
                }
            } else {
                $statusKey = 'no_data';
                $statusLabel = ($row['runduration'] ? 'No Meter Logs' : 'Non-Metered');
                $statusBadge = 'secondary';
                $sortOrder = 4;
            }

            $row['interval_hours'] = $interval;
            $row['computed_meter'] = $currentMeter;
            $row['last_service_hours'] = $lastServiceHours;
            $row['next_service_due'] = $nextServiceDue;
            $row['remaining_hours'] = $remainingHours;
            $row['hours_overdue'] = $hoursOverdue;
            $row['progress_percent'] = $serviceProgressPercent;
            $row['status_key'] = $statusKey;
            $row['status_label'] = $statusLabel;
            $row['status_badge'] = $statusBadge;
            $row['sort_order'] = $sortOrder;
            $row['default_service_items'] = json_decode($row['default_service_items'] ?? '[]', true) ?: [];

            $results[] = $row;
        }

        // Sort by priority: Overdue first (highest overdue hours first), then Due Soon (lowest remaining hours first), then Good, then No data
        usort($results, function($a, $b) {
            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] <=> $b['sort_order'];
            }
            if ($a['sort_order'] === 1) {
                return $b['hours_overdue'] <=> $a['hours_overdue'];
            }
            if ($a['sort_order'] === 2) {
                return $a['remaining_hours'] <=> $b['remaining_hours'];
            }
            if ($a['sort_order'] === 3) {
                return $a['remaining_hours'] <=> $b['remaining_hours'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return $results;
    }
}
