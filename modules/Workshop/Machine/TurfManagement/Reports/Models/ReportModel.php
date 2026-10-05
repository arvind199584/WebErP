<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Reports\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class ReportModel extends BaseModel {

    public function getFuelConsumptionTrend(int $officeId, string $from, string $to): array {
        $sql = "
            SELECT
                c.log_date,
                i.description as fuel_type,
                SUM(c.fuel_consumed_qty) as total_qty
            FROM turf_consumption_log c
            JOIN turf_machines m ON c.machine_id = m.id
            JOIN turf_inventory_items i ON m.fuel_item_id = i.id
            WHERE c.officeid = :officeid
              AND c.log_date BETWEEN :from_date AND :to_date
              AND c.fuel_consumed_qty IS NOT NULL
            GROUP BY c.log_date, i.description
            ORDER BY c.log_date ASC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'from_date' => $from,
            'to_date' => $to
        ]);
    }

    public function getMachineFuelBreakdown(int $officeId, string $from, string $to): array {
        $sql = "
            SELECT
                m.name as machine_name,
                i.description as fuel_type,
                SUM(c.fuel_consumed_qty) as total_fuel
            FROM turf_consumption_log c
            JOIN turf_machines m ON c.machine_id = m.id
            JOIN turf_inventory_items i ON m.fuel_item_id = i.id
            WHERE c.officeid = :officeid
              AND c.log_date BETWEEN :from_date AND :to_date
              AND c.fuel_consumed_qty IS NOT NULL
            GROUP BY m.name, i.description
            ORDER BY total_fuel DESC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'from_date' => $from,
            'to_date' => $to
        ]);
    }

    public function getMachineHoursBreakdown(int $officeId, string $from, string $to): array {
        $sql = "
            SELECT
                m.name as machine_name,
                (MAX(c.running_hours) - MIN(c.running_hours)) as hours_run,
                MAX(c.running_hours) as latest_cumulative_reading
            FROM turf_consumption_log c
            JOIN turf_machines m ON c.machine_id = m.id
            WHERE c.officeid = :officeid
              AND c.log_date BETWEEN :from_date AND :to_date
              AND c.running_hours IS NOT NULL
            GROUP BY m.name
            HAVING (MAX(c.running_hours) - MIN(c.running_hours)) > 0
            ORDER BY hours_run DESC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'from_date' => $from,
            'to_date' => $to
        ]);
    }

    public function getSummaryTableData(int $officeId, string $from, string $to): array {
        $sql = "
            SELECT
                m.name as machine_name,
                COUNT(c.id) as days_operated,
                SUM(c.fuel_consumed_qty) as total_fuel,
                i.description as fuel_type,
                (MAX(c.running_hours) - MIN(c.running_hours)) as hours_run_in_period,
                MAX(c.running_hours) as latest_reading
            FROM turf_machines m
            LEFT JOIN turf_consumption_log c
                   ON m.id = c.machine_id
                  AND c.log_date BETWEEN :from_date AND :to_date
            LEFT JOIN turf_inventory_items i ON m.fuel_item_id = i.id
            WHERE m.officeid = :officeid
            GROUP BY m.name, i.description
            ORDER BY m.name ASC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'from_date' => $from,
            'to_date' => $to
        ]);
    }

    public function getDailyFuelConsumptionReport(int $officeId, string $from, string $to): array {
        $sql = "
            SELECT
                m.name as machine_name,
                i.description as fuel_type,
                c.log_date,
                SUM(c.fuel_consumed_qty) as daily_fuel
            FROM turf_machines m
            JOIN turf_inventory_items i ON m.fuel_item_id = i.id
            LEFT JOIN turf_consumption_log c
                   ON m.id = c.machine_id
                  AND c.log_date BETWEEN :from_date AND :to_date
            WHERE m.officeid = :officeid
            GROUP BY m.name, i.description, c.log_date
            ORDER BY m.name ASC, c.log_date ASC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'from_date' => $from,
            'to_date' => $to
        ]);
    }

    public function getDailyFuelConsumptionReportFiltered(int $officeId, string $from, string $to, string $fuelTypeFilter): array {
        $params = [
            'officeid' => $officeId,
            'from_date' => $from,
            'to_date' => $to
        ];

        $fuelFilterSql = "";
        if ($fuelTypeFilter === 'diesel') {
            $fuelFilterSql = " AND LOWER(i.description) LIKE '%diesel%' ";
        } elseif ($fuelTypeFilter === 'petrol') {
            $fuelFilterSql = " AND LOWER(i.description) LIKE '%petrol%' ";
        }

        $sql = "
            SELECT
                m.name as machine_name,
                i.description as fuel_type,
                c.log_date,
                SUM(c.fuel_consumed_qty) as daily_fuel
            FROM turf_machines m
            JOIN turf_inventory_items i ON m.fuel_item_id = i.id
            LEFT JOIN turf_consumption_log c
                   ON m.id = c.machine_id
                  AND c.log_date BETWEEN :from_date AND :to_date
            WHERE m.officeid = :officeid
              $fuelFilterSql
            GROUP BY m.name, i.description, c.log_date
            ORDER BY m.name ASC, c.log_date ASC
        ";
        return $this->fetchAll($sql, $params);
    }

    public function getMachineLogHistory(int $officeId, int $machineId, string $from, string $to): array {
        $sql = "
            SELECT
                id,
                log_date,
                recorded_by as operator,
                fuel_consumed_qty,
                running_hours as running_hours_to,
                (
                    SELECT running_hours
                    FROM turf_consumption_log c2
                    WHERE c2.machine_id = c1.machine_id
                      AND c2.log_date < c1.log_date
                      AND c2.running_hours IS NOT NULL
                      AND c2.running_hours > 0
                    ORDER BY c2.log_date DESC
                    FETCH FIRST 1 ROWS ONLY
                ) as running_hours_start
            FROM turf_consumption_log c1
            WHERE officeid = :officeid
              AND machine_id = :machine_id
              AND log_date BETWEEN :from_date AND :to_date
            ORDER BY log_date ASC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'machine_id' => $machineId,
            'from_date' => $from,
            'to_date' => $to
        ]);
    }

    public function getMachineInfo(int $machineId): ?array {
        $sql = "SELECT id, name, runduration, daily_run, has_rest_day, rest_day FROM turf_machines WHERE id = :id";
        $row = $this->fetchRow($sql, ['id' => $machineId]);
        return $row ?: null;
    }


    public function getFullMachineLogHistory(int $officeId, int $machineId): array {
        $sql = "
            SELECT
                c.id,
                c.log_date,
                c.recorded_by as operator,
                c.fuel_consumed_qty,
                c.running_hours
            FROM turf_consumption_log c
            WHERE c.officeid = :officeid
              AND c.machine_id = :machine_id
            ORDER BY c.log_date ASC
        ";
        return $this->fetchAll($sql, [
            'officeid' => $officeId,
            'machine_id' => $machineId
        ]);
    }

    public function getMonthwiseMachineStats(int $officeId, int $machineId): array {
        $sql = "
            SELECT 
                TO_CHAR(c.log_date, 'YYYY-MM') AS month_key,
                TO_CHAR(c.log_date, 'Month YYYY') AS month_display,
                SUM(COALESCE(c.fuel_consumed_qty, 0)) AS total_fuel,
                MIN(c.running_hours) AS start_hours,
                MAX(c.running_hours) AS end_hours,
                CASE 
                    WHEN COUNT(c.running_hours) = 1 THEN MAX(c.running_hours)
                    ELSE (MAX(c.running_hours) - MIN(c.running_hours)) 
                END AS net_run_hours,
                COUNT(c.id) AS days_logged,
                COUNT(c.fuel_consumed_qty) AS fuel_days_count,
                COUNT(c.running_hours) AS hours_days_count
            FROM turf_consumption_log c
            WHERE c.machine_id = :machine_id
            GROUP BY TO_CHAR(c.log_date, 'YYYY-MM'), TO_CHAR(c.log_date, 'Month YYYY')
            ORDER BY month_key DESC
        ";
        return $this->fetchAll($sql, [
            'machine_id' => $machineId
        ]);
    }

    public function executeAdHocQuery(string $sql, array $params = []): array {
        return $this->fetchAll($sql, $params);
    }
}
