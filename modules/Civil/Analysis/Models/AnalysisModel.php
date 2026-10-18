<?php
declare(strict_types=1);

namespace App\Modules\Civil\Analysis\Models;

require_once __DIR__ . '/../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class AnalysisModel extends BaseModel
{
    public function getAllJustifications(?int $officeId = null): array
    {
        $sql = "SELECT j.*, e.estimate_no, e.work_name, o.OfficeName
                FROM civil_justifications j
                LEFT JOIN civil_estimates e ON j.estimate_id = e.id
                LEFT JOIN office o ON j.officeid = o.Officeid";
        $params = [];

        if ($officeId !== null) {
            $sql .= " WHERE j.officeid = :officeid";
            $params[':officeid'] = $officeId;
        }

        $sql .= " ORDER BY j.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getJustificationById(int $id): ?array
    {
        $sql = "SELECT j.*, e.estimate_no, e.work_name, o.OfficeName
                FROM civil_justifications j
                LEFT JOIN civil_estimates e ON j.estimate_id = e.id
                LEFT JOIN office o ON j.officeid = o.Officeid
                WHERE j.id = :id";
        return $this->fetchRow($sql, [':id' => $id]);
    }

    public function getEstimateWithItems(int $estimateId): ?array
    {
        $estimate = $this->fetchRow("SELECT * FROM civil_estimates WHERE id = :id", [':id' => $estimateId]);
        if (!$estimate) {
            return null;
        }

        $items = $this->fetchAll("SELECT * FROM civil_estimate_items WHERE estimate_id = :id ORDER BY item_order ASC", [':id' => $estimateId]);
        $estimate['items'] = $items;
        return $estimate;
    }

    public function getItemDarBreakup(string $itemCode): array
    {
        $sql = "SELECT b.*, r.current_market_rate, r.dsr_base_rate, r.category as reg_category
                FROM dar_item_breakups b
                LEFT JOIN civil_global_rates r ON b.resource_code = r.resource_code
                WHERE b.item_code = :code
                ORDER BY b.id ASC";
        return $this->fetchAll($sql, [':code' => $itemCode]);
    }

    public function saveJustification(array $data): int
    {
        $sql = "INSERT INTO civil_justifications (
                    officeid, estimate_id, justification_no, title, estimated_cost, justified_cost, 
                    justification_percentage, water_charges_percent, cp_oh_percent, gst_percent, 
                    cess_percent, item_breakdowns, status, created_by
                ) VALUES (
                    :officeid, :estimate_id, :justification_no, :title, :estimated_cost, :justified_cost, 
                    :justification_percentage, :water_charges_percent, :cp_oh_percent, :gst_percent, 
                    :cess_percent, :item_breakdowns, :status, :created_by
                )";
        
        return (int)$this->executeInsert($sql, [
            ':officeid' => $data['officeid'],
            ':estimate_id' => $data['estimate_id'] ?? null,
            ':justification_no' => $data['justification_no'],
            ':title' => $data['title'],
            ':estimated_cost' => $data['estimated_cost'] ?? 0.00,
            ':justified_cost' => $data['justified_cost'] ?? 0.00,
            ':justification_percentage' => $data['justification_percentage'] ?? 0.00,
            ':water_charges_percent' => $data['water_charges_percent'] ?? 1.00,
            ':cp_oh_percent' => $data['cp_oh_percent'] ?? 15.00,
            ':gst_percent' => $data['gst_percent'] ?? 18.00,
            ':cess_percent' => $data['cess_percent'] ?? 1.00,
            ':item_breakdowns' => json_encode($data['item_breakdowns'] ?? []),
            ':status' => $data['status'] ?? 'Approved',
            ':created_by' => $data['created_by'] ?? null,
        ]);
    }
}
