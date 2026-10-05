<?php
declare(strict_types=1);
namespace App\Modules\Admin\WorkOrder\Models;

use App\Core\Database;
use App\Modules\Admin\WorkOrder\DTO\WorkOrderDTO;
use PDO;

class WorkOrderModel {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function findAll(): array {
        $sql = "SELECT wo.*, ae.alias as aa_es_alias, ag.name as agency_name
                FROM work_orders wo
                JOIN aa_es ae ON wo.aa_es_id = ae.id
                JOIN agencies ag ON wo.agency_id = ag.id
                ORDER BY wo.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(WorkOrderDTO $dto): int {
        $sql = "INSERT INTO work_orders (officeid, aa_es_id, agency_id, work_order_no, estimated_cost, tendered_amount, service_charge_percent)
                VALUES (:officeid, :aa_es_id, :agency_id, :work_order_no, :estimated_cost, :tendered_amount, :service_charge_percent)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':officeid', $dto->officeid);
        $stmt->bindValue(':aa_es_id', $dto->aa_es_id);
        $stmt->bindValue(':agency_id', $dto->agency_id);
        $stmt->bindValue(':work_order_no', $dto->work_order_no);
        $stmt->bindValue(':estimated_cost', $dto->estimated_cost);
        $stmt->bindValue(':tendered_amount', $dto->tendered_amount);
        $stmt->bindValue(':service_charge_percent', $dto->service_charge_percent);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }
}
