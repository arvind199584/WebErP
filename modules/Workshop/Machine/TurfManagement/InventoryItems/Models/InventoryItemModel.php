<?php
namespace App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class InventoryItemModel extends BaseModel {
    protected $table = 'turf_inventory_items';

    public function getAllItems(?int $officeId = null): array {
        $sql = "SELECT 
                    i.*, 
                    c.name AS category_name,
                    COALESCE(SUM(CASE WHEN l.transaction_type = 'RECEIPT' THEN l.quantity ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN l.transaction_type = 'ISSUE' THEN l.quantity ELSE 0 END), 0) AS current_stock
                FROM {$this->table} i 
                LEFT JOIN turf_inventory_categories c ON i.category_id = c.id 
                LEFT JOIN turf_inventory_ledger l ON i.id = l.item_id";

        if ($officeId !== null) {
            $sql .= " AND l.officeid = :officeid";
            $params = ['officeid' => $officeId];
        } else {
            $params = [];
        }

        $sql .= " GROUP BY i.id, i.description, i.ac_unit, i.created_at, i.updated_at, i.category_id, c.name
                  ORDER BY i.description";

        return $this->fetchAll($sql, $params);
    }

    public function getFuelItems(): array {
        $sql = "SELECT i.*, c.name AS category_name 
                FROM {$this->table} i 
                INNER JOIN turf_inventory_categories c ON i.category_id = c.id 
                WHERE c.code = 'fuel' 
                ORDER BY i.description";
        return $this->fetchAll($sql);
    }

    public function getAllCategories(): array {
        $sql = "SELECT * FROM turf_inventory_categories ORDER BY name";
        return $this->fetchAll($sql);
    }

    public function getItemById(int $id): ?array {
        $sql = "SELECT i.*, c.name AS category_name 
                FROM {$this->table} i 
                LEFT JOIN turf_inventory_categories c ON i.category_id = c.id 
                WHERE i.id = :id";
        return $this->fetchRow($sql, ['id' => $id]);
    }

    public function createItem(array $data): int {
        $sql = "INSERT INTO {$this->table} (description, ac_unit, category_id) VALUES (:description, :ac_unit, :category_id)";
        return $this->executeInsert($sql, $data);
    }

    public function updateItem(int $id, array $data): bool {
        $sql = "UPDATE {$this->table} SET description = :description, ac_unit = :ac_unit, category_id = :category_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function getErpChemicals(?int $officeId = null): array {
        $sql = "SELECT * FROM \"StoreChemicalsAndFertilizers\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE \"officeid\" = :officeid OR \"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY \"ItemName\"";
        return $this->fetchAll($sql, $params);
    }

    public function getErpFuels(?int $officeId = null): array {
        $sql = "SELECT * FROM \"StoreFuelAndLubricants\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE \"officeid\" = :officeid OR \"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY \"ItemName\"";
        return $this->fetchAll($sql, $params);
    }

    public function getErpHorticulture(?int $officeId = null): array {
        $sql = "SELECT * FROM \"StoreHorticultureConsumables\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE \"officeid\" = :officeid OR \"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY \"ItemName\"";
        return $this->fetchAll($sql, $params);
    }

    public function getErpSpareParts(?int $officeId = null): array {
        $sql = "SELECT * FROM \"StoreMachineSpareParts\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE \"officeid\" = :officeid OR \"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY \"ItemName\"";
        return $this->fetchAll($sql, $params);
    }

    public function getErpSanitation(?int $officeId = null): array {
        $sql = "SELECT * FROM \"StoreSanitationItems\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE \"officeid\" = :officeid OR \"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY \"ItemName\"";
        return $this->fetchAll($sql, $params);
    }

    public function getErpStationery(?int $officeId = null): array {
        $sql = "SELECT * FROM \"StoreStationeryItems\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE \"officeid\" = :officeid OR \"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY \"ItemName\"";
        return $this->fetchAll($sql, $params);
    }

    public function getErpVouchers(?int $officeId = null): array {
        $sql = "SELECT v.*, d.\"ItemCode\", d.\"ItemName\", d.\"Quantity\", d.\"UnitOfMeasure\", d.\"UnitRate\", d.\"LineTotal\"
                FROM \"StoreVouchers\" v
                LEFT JOIN \"StoreVoucherDetails\" d ON v.\"Id\" = d.\"VoucherId\"";
        $params = [];
        if ($officeId !== null) {
            $sql .= " WHERE v.\"officeid\" = :officeid OR v.\"officeid\" IS NULL";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY v.\"VoucherDate\" DESC";
        return $this->fetchAll($sql, $params);
    }

    public function deleteItem(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}

