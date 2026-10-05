<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Indents\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class IndentModel extends BaseModel {
    protected $table = 'turf_inventory_ledger';

    public function beginTransaction() {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
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

    public function getIndentVouchers(?int $officeId): array {
        // Group by voucher_no, date, and party to show a list of distinct indent vouchers
        $sql = "SELECT
                    til.voucher_no,
                    til.transaction_date,
                    til.party,
                    til.officeid,
                    o.OfficeName as office_name,
                    COUNT(til.id) as item_count,
                    SUM(til.quantity) as total_quantity
                FROM {$this->table} til
                JOIN office o ON til.officeid = o.Officeid
                WHERE til.transaction_type = 'ISSUE'";

        $params = [];
        if ($officeId !== null) {
            $sql .= " AND til.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " GROUP BY til.voucher_no, til.transaction_date, til.party, til.officeid, o.OfficeName
                  ORDER BY til.transaction_date DESC, til.voucher_no DESC";

        return $this->fetchAll($sql, $params);
    }

    public function getVoucherItems(string $voucherNo, ?int $officeId): array {
        $sql = "SELECT
                    til.*,
                    tii.description as item_description,
                    tii.ac_unit
                FROM {$this->table} til
                JOIN turf_inventory_items tii ON til.item_id = tii.id
                WHERE til.transaction_type = 'ISSUE'
                  AND til.voucher_no = :voucher_no";

        $params = ['voucher_no' => $voucherNo];

        if ($officeId !== null) {
            $sql .= " AND til.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        return $this->fetchAll($sql, $params);
    }

    public function createIndentItem(array $data): int {
        $sql = "INSERT INTO {$this->table}
                (officeid, item_id, transaction_type, quantity, transaction_date, voucher_no, party)
                VALUES
                (:officeid, :item_id, 'ISSUE', :quantity, :transaction_date, :voucher_no, :party)";

        return $this->executeInsert($sql, $data);
    }

    public function deleteIndentVoucher(string $voucherNo, ?int $officeId): bool {
        $sql = "DELETE FROM {$this->table} WHERE transaction_type = 'ISSUE' AND voucher_no = :voucher_no";
        $params = ['voucher_no' => $voucherNo];

        if ($officeId !== null) {
            $sql .= " AND officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
