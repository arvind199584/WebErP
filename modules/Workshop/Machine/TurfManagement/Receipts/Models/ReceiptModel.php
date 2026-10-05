<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Receipts\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class ReceiptModel extends BaseModel {
    protected $table = 'turf_inventory_ledger';

    public function beginTransaction() {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function inTransaction(): bool {
        return $this->db->inTransaction();
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

    public function getReceiptVouchers(?int $officeId): array {
        // Show flat list of items instead of grouping by voucher
        $sql = "SELECT
                    til.id,
                    til.voucher_no,
                    til.transaction_date,
                    til.party,
                    til.officeid,
                    til.quantity,
                    tii.description as item_name,
                    tii.ac_unit as unit,
                    o.OfficeName as office_name
                FROM {$this->table} til
                JOIN office o ON til.officeid = o.Officeid
                JOIN turf_inventory_items tii ON til.item_id = tii.id
                WHERE til.transaction_type = 'RECEIPT'";

        $params = [];
        if ($officeId !== null) {
            $sql .= " AND til.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY til.transaction_date DESC, til.voucher_no DESC, til.id DESC";

        return $this->fetchAll($sql, $params);
    }

    public function createReceiptItem(array $data): int {
        $sql = "INSERT INTO {$this->table}
                (officeid, item_id, transaction_type, quantity, transaction_date, voucher_no, party)
                VALUES
                (:officeid, :item_id, 'RECEIPT', :quantity, :transaction_date, :voucher_no, :party)";

        return $this->executeInsert($sql, $data);
    }

    public function deleteReceiptEntry(int $id, ?int $officeId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function existsItemInVoucher(string $voucherNo, int $itemId, int $officeId): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE voucher_no = :voucher_no
                  AND item_id = :item_id
                  AND officeid = :officeid
                  AND transaction_type = 'RECEIPT'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'voucher_no' => $voucherNo,
            'item_id' => $itemId,
            'officeid' => $officeId
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }
}
