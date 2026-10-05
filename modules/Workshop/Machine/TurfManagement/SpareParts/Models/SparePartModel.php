<?php
namespace App\Modules\Workshop\Machine\TurfManagement\SpareParts\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class SparePartModel extends BaseModel {
    protected $table = 'turf_machine_spare_parts';

    public function __construct() {
        parent::__construct();
        $this->ensureTableAndData();
    }

    private function ensureTableAndData(): void {
        $sql = "CREATE TABLE IF NOT EXISTS public.turf_machine_spare_parts (
            id SERIAL PRIMARY KEY,
            officeid INTEGER NOT NULL REFERENCES public.office(Officeid) ON DELETE RESTRICT,
            machine_id INTEGER NOT NULL REFERENCES public.turf_machines(id) ON DELETE RESTRICT,
            compatible_machine_ids JSONB DEFAULT '[]'::jsonb,
            group_name VARCHAR(255),
            part_no VARCHAR(100),
            nomenclature VARCHAR(255) NOT NULL,
            received_qty NUMERIC(12, 2) DEFAULT 0.00,
            issued_qty NUMERIC(12, 2) DEFAULT 0.00,
            current_stock NUMERIC(12, 2) DEFAULT 0.00,
            unit VARCHAR(50) DEFAULT 'Pcs',
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );";
        try {
            $this->db->exec($sql);
            $this->db->exec("ALTER TABLE public.turf_machine_spare_parts ADD COLUMN IF NOT EXISTS compatible_machine_ids JSONB DEFAULT '[]'::jsonb;");
            $this->db->exec("ALTER TABLE public.turf_machine_spare_parts ADD COLUMN IF NOT EXISTS group_name VARCHAR(255);");
            
            $seedSqlFile = __DIR__ . '/../../../../../../scratch/seed_spare_parts.sql';
            if (file_exists($seedSqlFile)) {
                $seedSql = file_get_contents($seedSqlFile);
                $this->db->exec($seedSql);
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }

    public function getSparePartsByMachine(int $machineId, ?int $officeId): array {
        $sql = "SELECT sp.*, tm.name AS machine_name 
                FROM {$this->table} sp
                JOIN turf_machines tm ON sp.machine_id = tm.id
                WHERE (sp.machine_id = :machine_id OR sp.compatible_machine_ids @> jsonb_build_array(:machine_id_int::int))";
        $params = ['machine_id' => $machineId, 'machine_id_int' => $machineId];

        if ($officeId !== null) {
            $sql .= " AND sp.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY sp.nomenclature ASC";
        return $this->fetchAll($sql, $params);
    }

    public function getAllSpareParts(?int $officeId, ?int $machineId = null): array {
        $sql = "SELECT sp.*, tm.name AS machine_name 
                FROM {$this->table} sp
                JOIN turf_machines tm ON sp.machine_id = tm.id";
        $where = [];
        $params = [];

        if ($officeId !== null) {
            $where[] = "sp.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        if ($machineId !== null && $machineId > 0) {
            $where[] = "(sp.machine_id = :machine_id OR sp.compatible_machine_ids @> jsonb_build_array(:machine_id_int::int))";
            $params['machine_id'] = $machineId;
            $params['machine_id_int'] = $machineId;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY sp.group_name ASC, sp.nomenclature ASC";
        return $this->fetchAll($sql, $params);
    }

    public function createSparePart(array $data): int {
        $data['current_stock'] = ($data['received_qty'] ?? 0) - ($data['issued_qty'] ?? 0);
        $sql = "INSERT INTO {$this->table} (officeid, machine_id, part_no, nomenclature, received_qty, issued_qty, current_stock, unit)
                VALUES (:officeid, :machine_id, :part_no, :nomenclature, :received_qty, :issued_qty, :current_stock, :unit)";
        return $this->executeInsert($sql, [
            'officeid'      => $data['officeid'],
            'machine_id'    => $data['machine_id'],
            'part_no'       => $data['part_no'] ?? null,
            'nomenclature'  => $data['nomenclature'],
            'received_qty'  => (float)($data['received_qty'] ?? 0),
            'issued_qty'    => (float)($data['issued_qty'] ?? 0),
            'current_stock' => (float)$data['current_stock'],
            'unit'          => $data['unit'] ?? 'Pcs'
        ]);
    }
}
