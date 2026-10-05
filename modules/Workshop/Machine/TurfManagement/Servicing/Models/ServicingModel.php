<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Servicing\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class ServicingModel extends BaseModel {
    protected $table = 'turf_servicing_log';

    public function getAllServicingLogs(?int $officeId, ?int $machineId = null): array {
        $sql = "SELECT
                    tsl.id,
                    tsl.officeid,
                    tsl.machine_id,
                    tsl.service_date,
                    tsl.hours_at_service,
                    tsl.next_service_due,
                    tsl.service_type,
                    tsl.serviced_by,
                    tsl.cost,
                    tsl.job_card_no,
                    tsl.remarks,
                    tsl.created_at,
                    tm.name AS machine_name,
                    o.OfficeName AS office_name
                FROM {$this->table} tsl
                JOIN turf_machines tm ON tsl.machine_id = tm.id
                JOIN office o ON tsl.officeid = o.Officeid";

        $params = [];
        $whereClauses = [];

        if ($officeId !== null) {
            $whereClauses[] = "tsl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        if ($machineId !== null && $machineId > 0) {
            $whereClauses[] = "tsl.machine_id = :machine_id";
            $params['machine_id'] = $machineId;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY tsl.service_date DESC, tsl.id DESC";

        return $this->fetchAll($sql, $params);
    }

    public function getServicingById(int $id, ?int $officeId): ?array {
        $sql = "SELECT
                    tsl.*,
                    tm.name AS machine_name
                FROM {$this->table} tsl
                JOIN turf_machines tm ON tsl.machine_id = tm.id
                WHERE tsl.id = :id";

        $params = ['id' => $id];

        if ($officeId !== null) {
            $sql .= " AND tsl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $row = $this->fetchRow($sql, $params);
        return $row ?: null;
    }

    public function createServicing(array $data): int {
        $sql = "INSERT INTO {$this->table} (
                    officeid, machine_id, service_date, hours_at_service, 
                    next_service_due, service_type, serviced_by, cost, job_card_no, remarks
                ) VALUES (
                    :officeid, :machine_id, :service_date, :hours_at_service, 
                    :next_service_due, :service_type, :serviced_by, :cost, :job_card_no, :remarks
                )";

        return $this->executeInsert($sql, [
            'officeid'         => $data['officeid'],
            'machine_id'       => $data['machine_id'],
            'service_date'     => $data['service_date'],
            'hours_at_service' => $data['hours_at_service'],
            'next_service_due' => $data['next_service_due'] ?? 100,
            'service_type'     => $data['service_type'] ?? 'Routine Maintenance',
            'serviced_by'      => $data['serviced_by'] ?? null,
            'cost'             => $data['cost'] ?? null,
            'job_card_no'      => $data['job_card_no'] ?? null,
            'remarks'          => $data['remarks'] ?? null
        ]);
    }

    public function updateServicing(int $id, ?int $officeId, array $data): bool {
        $sql = "UPDATE {$this->table} SET
                    machine_id = :machine_id,
                    service_date = :service_date,
                    hours_at_service = :hours_at_service,
                    next_service_due = :next_service_due,
                    service_type = :service_type,
                    serviced_by = :serviced_by,
                    cost = :cost,
                    job_card_no = :job_card_no,
                    remarks = :remarks
                WHERE id = :id";

        $params = [
            'id'               => $id,
            'machine_id'       => $data['machine_id'],
            'service_date'     => $data['service_date'],
            'hours_at_service' => $data['hours_at_service'],
            'next_service_due' => $data['next_service_due'] ?? 100,
            'service_type'     => $data['service_type'] ?? 'Routine Maintenance',
            'serviced_by'      => $data['serviced_by'] ?? null,
            'cost'             => $data['cost'] ?? null,
            'job_card_no'      => $data['job_card_no'] ?? null,
            'remarks'          => $data['remarks'] ?? null
        ];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteServicing(int $id, ?int $officeId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Gets machines with service status (latest service vs current running hours)
     */
    public function getMachineServiceDueStatus(?int $officeId): array {
        $sql = "SELECT 
                    tm.id AS machine_id,
                    tm.name AS machine_name,
                    COALESCE(tm.service_interval_hours, 100) AS service_interval_hours,
                    latest_serv.service_date AS last_service_date,
                    latest_serv.hours_at_service AS last_service_hours,
                    COALESCE(latest_serv.next_service_due, tm.service_interval_hours, 100) AS next_service_due,
                    latest_log.max_hours AS current_running_hours,
                    latest_log.last_meter_date AS current_meter_date
                FROM turf_machines tm
                LEFT JOIN (
                    SELECT DISTINCT ON (machine_id) machine_id, service_date, hours_at_service, next_service_due
                    FROM {$this->table}
                    ORDER BY machine_id, service_date DESC, id DESC
                ) latest_serv ON tm.id = latest_serv.machine_id
                LEFT JOIN (
                    SELECT machine_id, MAX(running_hours) AS max_hours, MAX(log_date) AS last_meter_date
                    FROM turf_consumption_log
                    GROUP BY machine_id
                ) latest_log ON tm.id = latest_log.machine_id";

        $whereClauses = ["tm.runduration = TRUE"];
        $params = [];

        if ($officeId !== null) {
            $whereClauses[] = "tm.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " WHERE " . implode(" AND ", $whereClauses);
        $sql .= " ORDER BY tm.name ASC";

        return $this->fetchAll($sql, $params);
    }
}
