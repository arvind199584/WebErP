<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Models;

require_once __DIR__ . '/../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class DashboardModel extends BaseModel {

    /**
     * Get fleet health breakdown (Working, Off-Road, Service Due).
     */
    public function getFleetHealthSummary(?int $officeId): array {
        $params = [];
        $whereOffice = "";
        if ($officeId !== null) {
            $whereOffice = " AND tm.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        // 1. Status counts
        $statusSql = "SELECT status, COUNT(*) as count 
                      FROM turf_machines tm 
                      WHERE 1=1 {$whereOffice} 
                      GROUP BY status";
        $statusRows = $this->fetchAll($statusSql, $params);
        $statusCounts = [];
        $totalMachines = 0;
        foreach ($statusRows as $row) {
            $statusCounts[$row['status']] = (int)$row['count'];
            $totalMachines += (int)$row['count'];
        }

        // 2. Off-Road machines list
        $offRoadSql = "SELECT tm.id, tm.name, tm.make, tm.status, o.OfficeName as office_name
                       FROM turf_machines tm
                       JOIN office o ON tm.officeid = o.Officeid
                       WHERE tm.status != 'Working' {$whereOffice}
                       ORDER BY tm.name ASC";
        $offRoadMachines = $this->fetchAll($offRoadSql, $params);

        // 3. Service Due machines
        // Machines tracking hours where cumulative meter reading is within 20 hours of service interval or overdue
        $serviceSql = "SELECT 
                           tm.id, 
                           tm.name, 
                           tm.make, 
                           tm.status, 
                           COALESCE(tm.service_interval_hours, 100) AS service_interval_hours,
                           latest_serv.hours_at_service AS last_service_hours,
                           COALESCE(latest_serv.next_service_due, tm.service_interval_hours, 100) AS next_service_due_interval,
                           COALESCE(latest_log.max_hours, 0) AS current_meter_hours,
                           o.OfficeName AS office_name
                       FROM turf_machines tm
                       JOIN office o ON tm.officeid = o.Officeid
                       LEFT JOIN (
                           SELECT DISTINCT ON (machine_id) machine_id, service_date, hours_at_service, next_service_due
                           FROM turf_servicing_log
                           ORDER BY machine_id, service_date DESC, id DESC
                       ) latest_serv ON tm.id = latest_serv.machine_id
                       LEFT JOIN (
                           SELECT machine_id, MAX(running_hours) AS max_hours
                           FROM turf_consumption_log
                           WHERE running_hours > 0
                           GROUP BY machine_id
                       ) latest_log ON tm.id = latest_log.machine_id
                       WHERE (tm.runduration = TRUE OR latest_log.max_hours > 0) {$whereOffice}
                       ORDER BY tm.name ASC";

        $allHourMachines = $this->fetchAll($serviceSql, $params);
        $serviceDueMachines = [];

        foreach ($allHourMachines as $m) {
            $interval = (float)($m['next_service_due_interval'] ?: $m['service_interval_hours'] ?: 100);
            $lastServiceHours = $m['last_service_hours'] !== null ? (float)$m['last_service_hours'] : 0.0;
            $currentHours = (float)($m['current_meter_hours'] ?? 0.0);

            // Target meter reading when service is due: last service meter + interval
            $dueAtMeter = $lastServiceHours + $interval;
            $remaining = $dueAtMeter - $currentHours;

            // Flag as service due if remaining <= 20 hours (or overdue) and meter has active readings
            if ($currentHours > 0 && $remaining <= 20.0) {
                $m['total_hours_run']  = $currentHours;
                $m['service_due']      = $dueAtMeter;
                $m['hours_to_service'] = round($remaining, 1);
                $serviceDueMachines[]  = $m;
            }
        }

        // Sort by urgency: most overdue first
        usort($serviceDueMachines, function($a, $b) {
            return ($a['hours_to_service'] <=> $b['hours_to_service']);
        });

        return [
            'total_machines'        => $totalMachines,
            'working_count'         => $statusCounts['Working'] ?? 0,
            'off_road_count'        => count($offRoadMachines),
            'service_due_count'     => count($serviceDueMachines),
            'off_road_machines'     => $offRoadMachines,
            'service_due_machines'  => $serviceDueMachines
        ];
    }

    /**
     * Get Fuel & Lubricants Stock Balance as of a given date.
     * Incorporates Receipts, Ledger Issues, and Machine Log Consumptions since legacy balance date (2026-06-30).
     */
    public function getFuelBalances(?int $officeId, string $asOfDate): array {
        $params = ['as_of_date' => $asOfDate];
        $whereOffice = "";
        $whereOfficeLog = "";

        if ($officeId !== null) {
            $whereOffice = " AND officeid = :officeid";
            $whereOfficeLog = " AND tcl.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        // Fetch all fuel and lubricant items
        $sql = "SELECT 
                    i.id,
                    i.description,
                    i.ac_unit,
                    c.name as category_name,
                    c.code as category_code,
                    COALESCE(r.total_inward, 0) as total_inward,
                    (COALESCE(iss.total_outward, 0) + COALESCE(cl.total_consumed, 0)) as total_outward,
                    (COALESCE(r.total_inward, 0) - (COALESCE(iss.total_outward, 0) + COALESCE(cl.total_consumed, 0))) as balance_qty
                FROM turf_inventory_items i
                JOIN turf_inventory_categories c ON i.category_id = c.id
                -- Receipts up to asOfDate
                LEFT JOIN (
                    SELECT item_id, SUM(quantity) as total_inward
                    FROM turf_inventory_ledger
                    WHERE transaction_type = 'RECEIPT' 
                      AND transaction_date <= :as_of_date
                      {$whereOffice}
                    GROUP BY item_id
                ) r ON i.id = r.item_id
                -- Ledger Issues up to asOfDate
                LEFT JOIN (
                    SELECT item_id, SUM(quantity) as total_outward
                    FROM turf_inventory_ledger
                    WHERE transaction_type = 'ISSUE' 
                      AND transaction_date <= :as_of_date
                      {$whereOffice}
                    GROUP BY item_id
                ) iss ON i.id = iss.item_id
                -- Machine Consumptions after 2026-06-30 up to asOfDate
                LEFT JOIN (
                    SELECT tm.fuel_item_id, SUM(tcl.fuel_consumed_qty) as total_consumed
                    FROM turf_consumption_log tcl
                    JOIN turf_machines tm ON tcl.machine_id = tm.id
                    WHERE tcl.log_date > '2026-06-30'
                      AND tcl.log_date <= :as_of_date
                      {$whereOfficeLog}
                    GROUP BY tm.fuel_item_id
                ) cl ON i.id = cl.fuel_item_id
                WHERE c.code IN ('fuel', 'lubricants')
                   OR i.id IN (1, 2, 85, 86)
                ORDER BY 
                    CASE 
                        WHEN i.id = 1 THEN 1 -- Petrol
                        WHEN i.id = 2 THEN 2 -- Diesel
                        WHEN i.id = 86 THEN 3 -- 2T Oil
                        ELSE 4 
                    END,
                    i.description ASC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get Chemical & Fertilizer Stock Balances as of a given date.
     */
    public function getChemicalBalances(?int $officeId, string $asOfDate): array {
        $params = ['as_of_date' => $asOfDate];
        $whereOffice = "";

        if ($officeId !== null) {
            $whereOffice = " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql = "SELECT 
                    i.id,
                    i.description,
                    i.ac_unit,
                    c.name as category_name,
                    c.code as category_code,
                    COALESCE(r.total_inward, 0) as total_inward,
                    COALESCE(iss.total_outward, 0) as total_outward,
                    (COALESCE(r.total_inward, 0) - COALESCE(iss.total_outward, 0)) as balance_qty
                FROM turf_inventory_items i
                JOIN turf_inventory_categories c ON i.category_id = c.id
                LEFT JOIN (
                    SELECT item_id, SUM(quantity) as total_inward
                    FROM turf_inventory_ledger
                    WHERE transaction_type = 'RECEIPT' 
                      AND transaction_date <= :as_of_date
                      {$whereOffice}
                    GROUP BY item_id
                ) r ON i.id = r.item_id
                LEFT JOIN (
                    SELECT item_id, SUM(quantity) as total_outward
                    FROM turf_inventory_ledger
                    WHERE transaction_type = 'ISSUE' 
                      AND transaction_date <= :as_of_date
                      {$whereOffice}
                    GROUP BY item_id
                ) iss ON i.id = iss.item_id
                WHERE c.code NOT IN ('fuel', 'lubricants', 'stationary', 'sanitary_item')
                  AND i.id NOT IN (1, 2, 85, 86)
                ORDER BY c.name ASC, i.description ASC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get Spare Parts Live Stock.
     */
    public function getSparePartsStock(?int $officeId): array {
        $params = [];
        $whereOffice = "";

        if ($officeId !== null) {
            $whereOffice = " AND sp.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql = "SELECT 
                    sp.id,
                    sp.part_no,
                    sp.nomenclature,
                    sp.group_name,
                    sp.received_qty,
                    sp.issued_qty,
                    sp.current_stock,
                    sp.unit,
                    tm.name as machine_name,
                    o.OfficeName as office_name
                FROM turf_machine_spare_parts sp
                JOIN office o ON sp.officeid = o.Officeid
                LEFT JOIN turf_machines tm ON sp.machine_id = tm.id
                WHERE 1=1 {$whereOffice}
                ORDER BY sp.group_name ASC NULLS LAST, sp.nomenclature ASC";

        return $this->fetchAll($sql, $params);
    }
}
