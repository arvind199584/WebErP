<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Logs\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class LogModel extends BaseModel {
    protected $table = 'turf_consumption_log';

    public function beginTransaction() {
        if ($this->db->inTransaction()) {
            try {
                $this->db->rollBack();
            } catch (\Throwable $e) {
                // Ignore
            }
        }
        $this->db->beginTransaction();
    }

    public function commit() {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollBack() {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function getAllLogs(?int $officeId, ?string $month = null): array {
        $sql = "SELECT
                    tcl.id,
                    tcl.officeid,
                    tcl.machine_id,
                    tcl.log_date,
                    tcl.fuel_consumed_qty,
                    tcl.running_hours,
                    tcl.recorded_by AS operator,
                    tm.name AS machine_name,
                    o.OfficeName as office_name
                FROM {$this->table} tcl
                JOIN turf_machines tm ON tcl.machine_id = tm.id
                JOIN office o ON tcl.officeid = o.Officeid";

        $params = [];
        $whereClauses = [];

        if ($officeId !== null) {
            $whereClauses[] = "tcl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        if ($month !== null) {
            $whereClauses[] = "to_char(tcl.log_date, 'YYYY-MM') = :month";
            $params['month'] = $month;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY tcl.log_date DESC, tcl.id DESC";

        return $this->fetchAll($sql, $params);
    }

    public function getAvailableMonths(?int $officeId): array {
        $sql = "SELECT DISTINCT to_char(log_date, 'YYYY-MM') AS log_month
                FROM {$this->table} tcl";
        
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE tcl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY log_month DESC";
        
        return array_column($this->fetchAll($sql, $params), 'log_month');
    }

    public function getLogsByDate(?int $officeId, string $date): array {
        $sql = "SELECT
                    tcl.id,
                    tcl.officeid,
                    tcl.machine_id,
                    tcl.log_date,
                    tcl.fuel_consumed_qty,
                    tcl.running_hours,
                    tcl.recorded_by AS operator,
                    tm.name AS machine_name,
                    o.OfficeName as office_name,
                    i.description AS fuel_desc
                FROM {$this->table} tcl
                JOIN turf_machines tm ON tcl.machine_id = tm.id
                JOIN office o ON tcl.officeid = o.Officeid
                LEFT JOIN turf_inventory_items i ON tm.fuel_item_id = i.id
                WHERE tcl.log_date = :log_date";

        $params = ['log_date' => $date];

        if ($officeId !== null) {
            $sql .= " AND tcl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY tm.name ASC, tcl.id DESC";

        return $this->fetchAll($sql, $params);
    }

    public function getRecentDatesWithLogs(?int $officeId): array {
        $sql = "SELECT DISTINCT log_date 
                FROM {$this->table} tcl";
        
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE tcl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY log_date DESC LIMIT 10";
        
        return array_column($this->fetchAll($sql, $params), 'log_date');
    }

    public function createLog(array $data): int {
        $sql = "INSERT INTO {$this->table} (officeid, machine_id, log_date, fuel_consumed_qty, running_hours, recorded_by)
                VALUES (:officeid, :machine_id, :log_date, :fuel_consumed_qty, :running_hours, :operator)";
        return $this->executeInsert($sql, $data);
    }

    public function createBulkLogs(array $logs): int {
        if (empty($logs)) {
            return 0;
        }
        $sql = "INSERT INTO {$this->table} (officeid, machine_id, log_date, fuel_consumed_qty, running_hours, recorded_by)
                VALUES (:officeid, :machine_id, :log_date, :fuel_consumed_qty, :running_hours, :operator)";
        $stmt = $this->db->prepare($sql);
        $count = 0;
        foreach ($logs as $data) {
            $stmt->execute([
                'officeid'          => $data['officeid'],
                'machine_id'        => $data['machine_id'],
                'log_date'          => $data['log_date'],
                'fuel_consumed_qty' => $data['fuel_consumed_qty'],
                'running_hours'     => $data['running_hours'],
                'operator'          => $data['operator']
            ]);
            $count++;
        }
        return $count;
    }

    public function updateLogQuick(int $id, ?int $officeId, array $data): bool {
        $sql = "UPDATE {$this->table} SET
                    machine_id = :machine_id,
                    log_date = :log_date,
                    fuel_consumed_qty = :fuel_consumed_qty,
                    running_hours = :running_hours,
                    recorded_by = :operator,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $params = [
            'id' => $id,
            'machine_id' => $data['machine_id'],
            'log_date' => $data['log_date'],
            'fuel_consumed_qty' => $data['fuel_consumed_qty'],
            'running_hours' => $data['running_hours'],
            'operator' => $data['operator']
        ];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteLog(int $id, ?int $officeId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];
        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // --- AUDIT MODULE METHODS ---

    /**
     * Gets a chronological history of running hours for a specific machine.
     * Includes all records (even 0 or null) so we can detect intentional omissions.
     */
    public function getMachineChronologicalHistoryFull(int $machineId, ?int $officeId): array {
        $sql = "SELECT id, log_date, running_hours
                FROM {$this->table}
                WHERE machine_id = :machine_id";

        $params = ['machine_id' => $machineId];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY log_date ASC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Directly updates just the running_hours for a specific log ID.
     */
    public function updateRunningHours(int $id, float $hours): bool {
        $sql = "UPDATE {$this->table} SET running_hours = :hours WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id, 'hours' => $hours]);
    }

    public function getLatestLogDate(?int $officeId): ?string {
        $sql = "SELECT MAX(log_date) FROM {$this->table}";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?: null;
    }

    public function getMonthlyTrendsData(?int $officeId): array {
        $sql = "SELECT 
                    to_char(tcl.log_date, 'YYYY-MM') AS log_month,
                    SUM(CASE WHEN LOWER(i.description) LIKE '%petrol%' THEN tcl.fuel_consumed_qty ELSE 0 END) AS total_petrol,
                    SUM(CASE WHEN LOWER(i.description) LIKE '%diesel%' THEN tcl.fuel_consumed_qty ELSE 0 END) AS total_diesel
                FROM {$this->table} tcl
                JOIN turf_machines tm ON tcl.machine_id = tm.id
                LEFT JOIN turf_inventory_items i ON tm.fuel_item_id = i.id";
        
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE tcl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        
        $sql .= " GROUP BY log_month
                  ORDER BY log_month ASC";
        
        return $this->fetchAll($sql, $params);
    }

    public function getExistingLogsMap(?int $officeId): array {
        $sql = "SELECT machine_id, to_char(log_date, 'YYYY-MM-DD') AS log_date FROM {$this->table}";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $rows = $this->fetchAll($sql, $params);
        $map = [];
        foreach ($rows as $row) {
            $key = $row['machine_id'] . '_' . $row['log_date'];
            $map[$key] = true;
        }
        return $map;
    }
}
