<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Repairs\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;
use Exception;

class RepairModel extends BaseModel {
    protected $table = 'turf_machine_repairs';
    protected $itemsTable = 'turf_machine_repair_items';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void {
        $sql = "CREATE TABLE IF NOT EXISTS public.turf_machine_repairs (
            id SERIAL PRIMARY KEY,
            officeid INTEGER NOT NULL REFERENCES public.office(Officeid) ON DELETE RESTRICT,
            machine_id INTEGER NOT NULL REFERENCES public.turf_machines(id) ON DELETE RESTRICT,
            repair_date DATE NOT NULL,
            running_hours NUMERIC(10, 2),
            work_done TEXT NOT NULL,
            spare_parts_used TEXT,
            cost NUMERIC(12, 2) DEFAULT 0.00,
            repaired_by VARCHAR(255),
            status VARCHAR(50) DEFAULT 'Completed',
            remarks TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            job_card_no VARCHAR(100),
            work_type VARCHAR(50) DEFAULT 'repair'
        );";
        try {
            $this->db->exec($sql);
            $this->db->exec("ALTER TABLE public.turf_machine_repairs ADD COLUMN IF NOT EXISTS job_card_no VARCHAR(100);");
            $this->db->exec("ALTER TABLE public.turf_machine_repairs ADD COLUMN IF NOT EXISTS work_type VARCHAR(50) DEFAULT 'repair';");

            $sqlItems = "CREATE TABLE IF NOT EXISTS public.turf_machine_repair_items (
                id SERIAL PRIMARY KEY,
                repair_id INTEGER NOT NULL REFERENCES public.turf_machine_repairs(id) ON DELETE CASCADE,
                spare_part_id INTEGER NOT NULL REFERENCES public.turf_machine_spare_parts(id) ON DELETE RESTRICT,
                quantity NUMERIC(10, 2) NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );";
            $this->db->exec($sqlItems);
        } catch (\Throwable $e) {
            // Ignore if exists or handled
        }
    }

    public function getAllRepairs(?int $officeId, ?int $machineId = null): array {
        $sql = "SELECT 
                    tmr.id,
                    tmr.officeid,
                    tmr.machine_id,
                    tmr.repair_date,
                    tmr.running_hours,
                    tmr.work_done,
                    tmr.spare_parts_used,
                    tmr.cost,
                    tmr.repaired_by,
                    tmr.status,
                    tmr.remarks,
                    tmr.created_at,
                    tmr.job_card_no,
                    tmr.work_type,
                    tm.name AS machine_name,
                    o.OfficeName AS office_name,
                    (SELECT COUNT(*) FROM {$this->itemsTable} ri WHERE ri.repair_id = tmr.id) AS items_count
                FROM {$this->table} tmr
                JOIN turf_machines tm ON tmr.machine_id = tm.id
                JOIN office o ON tmr.officeid = o.Officeid";

        $whereClauses = [];
        $params = [];

        if ($officeId !== null) {
            $whereClauses[] = "tmr.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        if ($machineId !== null && $machineId > 0) {
            $whereClauses[] = "tmr.machine_id = :machine_id";
            $params['machine_id'] = $machineId;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY tmr.repair_date DESC, tmr.id DESC";

        return $this->fetchAll($sql, $params);
    }

    public function getRepairById(int $id, ?int $officeId): ?array {
        $sql = "SELECT 
                    tmr.*,
                    tm.name AS machine_name,
                    tm.service_interval_hours,
                    tm.service_done,
                    tm.service_due
                FROM {$this->table} tmr
                JOIN turf_machines tm ON tmr.machine_id = tm.id
                WHERE tmr.id = :id";
        $params = ['id' => $id];

        if ($officeId !== null) {
            $sql .= " AND tmr.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $repair = $this->fetchRow($sql, $params);
        if ($repair) {
            $repair['items'] = $this->getRepairItems($id);
        }

        return $repair ?: null;
    }

    public function getRepairItems(int $repairId): array {
        $sql = "SELECT 
                    ri.id,
                    ri.repair_id,
                    ri.spare_part_id,
                    ri.quantity,
                    sp.part_no,
                    sp.nomenclature,
                    sp.unit,
                    sp.current_stock
                FROM {$this->itemsTable} ri
                JOIN turf_machine_spare_parts sp ON ri.spare_part_id = sp.id
                WHERE ri.repair_id = :repair_id
                ORDER BY sp.nomenclature ASC";
        return $this->fetchAll($sql, ['repair_id' => $repairId]);
    }

    public function createRepair(array $data, array $items = []): int {
        $this->db->beginTransaction();
        try {
            $workType = $data['work_type'] ?? 'repair';
            $jobCardNo = !empty($data['job_card_no']) ? trim($data['job_card_no']) : null;
            $runningHours = !empty($data['running_hours']) ? (float)$data['running_hours'] : null;

            // 1. Insert header
            $sql = "INSERT INTO {$this->table} (
                        officeid, machine_id, repair_date, running_hours, work_done,
                        spare_parts_used, cost, repaired_by, status, remarks, job_card_no, work_type
                    ) VALUES (
                        :officeid, :machine_id, :repair_date, :running_hours, :work_done,
                        :spare_parts_used, :cost, :repaired_by, :status, :remarks, :job_card_no, :work_type
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'officeid'         => $data['officeid'],
                'machine_id'       => $data['machine_id'],
                'repair_date'      => $data['repair_date'],
                'running_hours'    => $runningHours,
                'work_done'        => $data['work_done'],
                'spare_parts_used' => $data['spare_parts_used'] ?? null,
                'cost'             => !empty($data['cost']) ? (float)$data['cost'] : 0.00,
                'repaired_by'      => $data['repaired_by'] ?? null,
                'status'           => $data['status'] ?? 'Completed',
                'remarks'          => $data['remarks'] ?? null,
                'job_card_no'      => $jobCardNo,
                'work_type'        => $workType
            ]);
            $repairId = (int)$this->db->lastInsertId();

            // 2. Insert line items & deduct stock
            $insertItemSql = "INSERT INTO {$this->itemsTable} (repair_id, spare_part_id, quantity) VALUES (:repair_id, :spare_part_id, :quantity)";
            $insertItemStmt = $this->db->prepare($insertItemSql);

            $updateStockSql = "UPDATE turf_machine_spare_parts 
                               SET current_stock = current_stock - :qty, 
                                   issued_qty = issued_qty + :qty, 
                                   updated_at = CURRENT_TIMESTAMP 
                               WHERE id = :part_id";
            $updateStockStmt = $this->db->prepare($updateStockSql);

            foreach ($items as $item) {
                $partId = (int)($item['spare_part_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                if ($partId > 0 && $qty > 0) {
                    $insertItemStmt->execute([
                        'repair_id'     => $repairId,
                        'spare_part_id' => $partId,
                        'quantity'      => $qty
                    ]);
                    $updateStockStmt->execute([
                        'qty'     => $qty,
                        'part_id' => $partId
                    ]);
                }
            }

            // 3. If Service Done: Update machine's service milestones & add to turf_servicing_log
            if ($workType === 'service_done' && $runningHours !== null && $runningHours > 0) {
                // Get machine's interval
                $mStmt = $this->db->prepare("SELECT service_interval_hours FROM turf_machines WHERE id = :id");
                $mStmt->execute(['id' => $data['machine_id']]);
                $interval = (float)($mStmt->fetchColumn() ?: 100);
                $nextDue = $runningHours + $interval;

                $updMachineSql = "UPDATE turf_machines 
                                  SET service_done = :service_done, 
                                      service_due = :service_due, 
                                      updated_at = CURRENT_TIMESTAMP 
                                  WHERE id = :id";
                $this->db->prepare($updMachineSql)->execute([
                    'service_done' => $runningHours,
                    'service_due'  => $nextDue,
                    'id'           => $data['machine_id']
                ]);

                // Also insert into turf_servicing_log for cross-module synchronization
                $serviceLogSql = "INSERT INTO turf_servicing_log (
                                      officeid, machine_id, service_date, hours_at_service,
                                      next_service_due, service_type, serviced_by, cost, job_card_no, remarks
                                  ) VALUES (
                                      :officeid, :machine_id, :service_date, :hours_at_service,
                                      :next_service_due, :service_type, :serviced_by, :cost, :job_card_no, :remarks
                                  )";
                $this->db->prepare($serviceLogSql)->execute([
                    'officeid'         => $data['officeid'],
                    'machine_id'       => $data['machine_id'],
                    'service_date'     => $data['repair_date'],
                    'hours_at_service' => $runningHours,
                    'next_service_due' => $nextDue,
                    'service_type'     => 'Routine Maintenance',
                    'serviced_by'      => $data['repaired_by'] ?? null,
                    'cost'             => !empty($data['cost']) ? (float)$data['cost'] : null,
                    'job_card_no'      => $jobCardNo,
                    'remarks'          => $data['work_done'] . (!empty($data['remarks']) ? ' - ' . $data['remarks'] : '')
                ]);
            }

            // 4. If save as default service kit is checked
            if (!empty($data['save_default_service_kit']) && !empty($items)) {
                $cleanKit = [];
                foreach ($items as $it) {
                    $pid = (int)($it['spare_part_id'] ?? 0);
                    $pqty = (float)($it['quantity'] ?? 0);
                    if ($pid > 0 && $pqty > 0) {
                        $cleanKit[] = ['spare_part_id' => $pid, 'quantity' => $pqty];
                    }
                }
                $kitJson = json_encode($cleanKit);
                $this->db->prepare("UPDATE turf_machines SET default_service_items = :kit, updated_at = CURRENT_TIMESTAMP WHERE id = :id")
                         ->execute(['kit' => $kitJson, 'id' => $data['machine_id']]);
            }

            // 5. If new custom work was entered and user requested to remember it
            if (!empty($data['save_custom_work']) && !empty($data['custom_work_title'])) {
                $newWork = trim($data['custom_work_title']);
                $mStmt = $this->db->prepare("SELECT custom_work_types FROM turf_machines WHERE id = :id");
                $mStmt->execute(['id' => $data['machine_id']]);
                $existing = json_decode($mStmt->fetchColumn() ?: '[]', true) ?: [];
                if ($newWork !== '' && !in_array($newWork, $existing, true)) {
                    $existing[] = $newWork;
                    $this->db->prepare("UPDATE turf_machines SET custom_work_types = :works, updated_at = CURRENT_TIMESTAMP WHERE id = :id")
                             ->execute(['works' => json_encode(array_values($existing)), 'id' => $data['machine_id']]);
                }
            }

            $this->db->commit();
            return $repairId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateRepair(int $id, ?int $officeId, array $data): bool {
        $sql = "UPDATE {$this->table} SET
                    machine_id       = :machine_id,
                    repair_date      = :repair_date,
                    running_hours    = :running_hours,
                    work_done        = :work_done,
                    spare_parts_used = :spare_parts_used,
                    cost             = :cost,
                    repaired_by      = :repaired_by,
                    status           = :status,
                    remarks          = :remarks,
                    job_card_no      = :job_card_no,
                    work_type        = :work_type,
                    updated_at       = CURRENT_TIMESTAMP
                WHERE id = :id";

        $params = [
            'id'               => $id,
            'machine_id'       => $data['machine_id'],
            'repair_date'      => $data['repair_date'],
            'running_hours'    => !empty($data['running_hours']) ? (float)$data['running_hours'] : null,
            'work_done'        => $data['work_done'],
            'spare_parts_used' => $data['spare_parts_used'] ?? null,
            'cost'             => !empty($data['cost']) ? (float)$data['cost'] : 0.00,
            'repaired_by'      => $data['repaired_by'] ?? null,
            'status'           => $data['status'] ?? 'Completed',
            'remarks'          => $data['remarks'] ?? null,
            'job_card_no'      => !empty($data['job_card_no']) ? trim($data['job_card_no']) : null,
            'work_type'        => $data['work_type'] ?? 'repair'
        ];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteRepair(int $id, ?int $officeId): bool {
        $this->db->beginTransaction();
        try {
            // Restore stock for all consumed items
            $items = $this->getRepairItems($id);
            $restoreStmt = $this->db->prepare("UPDATE turf_machine_spare_parts 
                                               SET current_stock = current_stock + :qty, 
                                                   issued_qty = issued_qty - :qty, 
                                                   updated_at = CURRENT_TIMESTAMP 
                                               WHERE id = :part_id");
            foreach ($items as $item) {
                $restoreStmt->execute([
                    'qty'     => $item['quantity'],
                    'part_id' => $item['spare_part_id']
                ]);
            }

            // Delete repair (cascade deletes repair_items)
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $params = ['id' => $id];
            if ($officeId !== null) {
                $sql .= " AND officeid = :officeid";
                $params['officeid'] = $officeId;
            }

            $stmt = $this->db->prepare($sql);
            $res = $stmt->execute($params);

            $this->db->commit();
            return $res;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getJobCardsSummary(?int $officeId, array $filters = []): array {
        $sql = "SELECT 
                    tmr.id AS repair_id,
                    tmr.job_card_no,
                    tmr.work_type,
                    tmr.repair_date,
                    tmr.running_hours,
                    tmr.work_done,
                    tmr.cost,
                    tmr.repaired_by,
                    tmr.status,
                    tm.id AS machine_id,
                    tm.name AS machine_name,
                    o.OfficeName AS office_name,
                    (SELECT COUNT(*) FROM {$this->itemsTable} ri WHERE ri.repair_id = tmr.id) AS items_count,
                    COALESCE((SELECT SUM(ri.quantity) FROM {$this->itemsTable} ri WHERE ri.repair_id = tmr.id), 0) AS total_parts_quantity
                FROM {$this->table} tmr
                JOIN turf_machines tm ON tmr.machine_id = tm.id
                JOIN office o ON tmr.officeid = o.Officeid
                WHERE tmr.job_card_no IS NOT NULL AND TRIM(tmr.job_card_no) != ''";

        $params = [];
        if ($officeId !== null) {
            $sql .= " AND tmr.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (tmr.job_card_no ILIKE :search OR tm.name ILIKE :search OR tmr.work_done ILIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['machine_id'])) {
            $sql .= " AND tmr.machine_id = :machine_id";
            $params['machine_id'] = (int)$filters['machine_id'];
        }

        if (!empty($filters['work_type'])) {
            $sql .= " AND tmr.work_type = :work_type";
            $params['work_type'] = $filters['work_type'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND tmr.repair_date >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND tmr.repair_date <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $sql .= " ORDER BY tmr.repair_date DESC, tmr.id DESC";

        $records = $this->fetchAll($sql, $params);

        // Attach items to each job card
        foreach ($records as &$rec) {
            $rec['items'] = $this->getRepairItems($rec['repair_id']);
        }

        return $records;
    }
}
