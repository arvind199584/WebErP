<?php
declare(strict_types=1);
namespace App\Modules\Admin\SupplyOrder\Models;

use App\Core\Database;
use App\Modules\Admin\SupplyOrder\DTO\SupplyOrderDTO;
use PDO;

class SupplyOrderModel {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function findAll(): array {
        $sql = "SELECT so.*, ae.alias as aa_es_alias, ag.name as agency_name
                FROM supply_orders so
                JOIN aa_es ae ON so.aa_es_id = ae.id
                JOIN agencies ag ON so.agency_id = ag.id
                ORDER BY so.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(SupplyOrderDTO $dto): int {
        $sql = "INSERT INTO supply_orders (officeid, aa_es_id, agency_id, supply_order_no, estimated_cost, tendered_amount, service_charge_percent)
                VALUES (:officeid, :aa_es_id, :agency_id, :supply_order_no, :estimated_cost, :tendered_amount, :service_charge_percent)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':officeid', $dto->officeid);
        $stmt->bindValue(':aa_es_id', $dto->aa_es_id);
        $stmt->bindValue(':agency_id', $dto->agency_id);
        $stmt->bindValue(':supply_order_no', $dto->supply_order_no);
        $stmt->bindValue(':estimated_cost', $dto->estimated_cost);
        $stmt->bindValue(':tendered_amount', $dto->tendered_amount);
        $stmt->bindValue(':service_charge_percent', $dto->service_charge_percent);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }
}
