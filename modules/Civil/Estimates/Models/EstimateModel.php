<?php
declare(strict_types=1);

namespace App\Modules\Civil\Estimates\Models;

require_once __DIR__ . '/../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class EstimateModel extends BaseModel
{
    public function getAllEstimates(?int $officeId = null): array
    {
        $sql = "SELECT e.*, o.OfficeName,
                       (SELECT COUNT(*) FROM civil_estimate_items WHERE estimate_id = e.id) as item_count
                FROM civil_estimates e
                LEFT JOIN office o ON e.officeid = o.Officeid";
        $params = [];

        if ($officeId !== null) {
            $sql .= " WHERE e.officeid = :officeid";
            $params[':officeid'] = $officeId;
        }

        $sql .= " ORDER BY e.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getEstimateById(int $id): ?array
    {
        $sql = "SELECT e.*, o.OfficeName
                FROM civil_estimates e
                LEFT JOIN office o ON e.officeid = o.Officeid
                WHERE e.id = :id";
        return $this->fetchRow($sql, [':id' => $id]);
    }

    public function createEstimate(array $data): int
    {
        $sql = "INSERT INTO civil_estimates (officeid, estimate_no, work_name, aa_es_id, description, estimated_amount, contingency_percent, total_amount, status, created_by)
                VALUES (:officeid, :estimate_no, :work_name, :aa_es_id, :description, :estimated_amount, :contingency_percent, :total_amount, :status, :created_by)";
        return (int)$this->executeInsert($sql, [
            ':officeid' => $data['officeid'],
            ':estimate_no' => $data['estimate_no'],
            ':work_name' => $data['work_name'],
            ':aa_es_id' => $data['aa_es_id'] ?? null,
            ':description' => $data['description'] ?? null,
            ':estimated_amount' => $data['estimated_amount'] ?? 0.00,
            ':contingency_percent' => $data['contingency_percent'] ?? 3.00,
            ':total_amount' => $data['total_amount'] ?? 0.00,
            ':status' => $data['status'] ?? 'Draft',
            ':created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function getEstimateItems(int $estimateId): array
    {
        $sql = "SELECT * FROM civil_estimate_items WHERE estimate_id = :id ORDER BY item_order ASC, id ASC";
        return $this->fetchAll($sql, [':id' => $estimateId]);
    }

    public function addEstimateItem(int $estimateId, array $item): int
    {
        $maxOrder = $this->fetchRow("SELECT COALESCE(MAX(item_order), 0) + 1 as next_order FROM civil_estimate_items WHERE estimate_id = :id", [':id' => $estimateId]);
        $order = (int)($maxOrder['next_order'] ?? 1);

        $sql = "INSERT INTO civil_estimate_items (estimate_id, item_order, sub_head_no, item_code, description, quantity, unit, rate, amount, remarks)
                VALUES (:estimate_id, :item_order, :sub_head_no, :item_code, :description, :quantity, :unit, :rate, :amount, :remarks)";
        
        $amount = (float)$item['quantity'] * (float)$item['rate'];

        return (int)$this->executeInsert($sql, [
            ':estimate_id' => $estimateId,
            ':item_order' => $order,
            ':sub_head_no' => $item['sub_head_no'] ?? 1,
            ':item_code' => $item['item_code'],
            ':description' => $item['description'],
            ':quantity' => $item['quantity'],
            ':unit' => $item['unit'],
            ':rate' => $item['rate'],
            ':amount' => $amount,
            ':remarks' => $item['remarks'] ?? null
        ]);
    }

    public function deleteEstimateItem(int $itemId, int $estimateId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM civil_estimate_items WHERE id = :id AND estimate_id = :est_id");
        return $stmt->execute([':id' => $itemId, ':est_id' => $estimateId]);
    }

    public function recalculateTotals(int $estimateId): array
    {
        $sumRow = $this->fetchRow("SELECT COALESCE(SUM(amount), 0.00) as subtotal FROM civil_estimate_items WHERE estimate_id = :id", [':id' => $estimateId]);
        $subtotal = (float)($sumRow['subtotal'] ?? 0.0);

        $est = $this->getEstimateById($estimateId);
        $contingencyPct = (float)($est['contingency_percent'] ?? 3.0);
        $contingencyAmt = ($subtotal * $contingencyPct) / 100.0;
        $totalAmount = $subtotal + $contingencyAmt;

        $stmt = $this->db->prepare("
            UPDATE civil_estimates 
            SET estimated_amount = :subtotal, total_amount = :total, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            ':subtotal' => $subtotal,
            ':total' => $totalAmount,
            ':id' => $estimateId
        ]);

        return [
            'subtotal' => $subtotal,
            'contingency_percent' => $contingencyPct,
            'contingency_amount' => $contingencyAmt,
            'total_amount' => $totalAmount
        ];
    }

    public function searchDsrItems(string $query, ?int $subHead = null, int $limit = 20): array
    {
        $sql = "SELECT item_code, sub_head_no, sub_head_name, description, unit, rate, volume, pdf_page
                FROM dsr_2023
                WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (item_code ILIKE :q OR description ILIKE :q)";
            $params[':q'] = '%' . trim($query) . '%';
        }

        if ($subHead !== null && $subHead > 0) {
            $sql .= " AND sub_head_no = :sh";
            $params[':sh'] = $subHead;
        }

        $sql .= " ORDER BY sub_head_no ASC, item_code ASC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDsrItemByCode(string $code): ?array
    {
        return $this->fetchRow("SELECT * FROM dsr_2023 WHERE item_code = :code", [':code' => $code]);
    }
}
