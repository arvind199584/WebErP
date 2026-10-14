<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Receipts\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class ReceiptModel extends BaseModel {
    protected $table = 'turf_inventory_ledger';
    protected $voucherTable = 'turf_receipt_vouchers';

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

    /**
     * Get list of Receipt Vouchers from turf_receipt_vouchers table.
     */
    public function getReceiptVouchers(?int $officeId): array {
        $sql = "SELECT
                    trv.id,
                    trv.officeid,
                    trv.voucher_no,
                    trv.voucher_date,
                    trv.party,
                    trv.agency_id,
                    trv.source_type,
                    trv.source_id,
                    trv.source_ref_no,
                    trv.store_category,
                    trv.items,
                    trv.supply_status,
                    trv.remarks,
                    trv.created_at,
                    o.OfficeName as office_name,
                    ag.name as agency_name,
                    jsonb_array_length(trv.items) as item_count
                FROM {$this->voucherTable} trv
                JOIN office o ON trv.officeid = o.Officeid
                LEFT JOIN agencies ag ON trv.agency_id = ag.id
                WHERE 1=1";

        $params = [];
        if ($officeId !== null) {
            $sql .= " AND trv.officeid = :officeid";
            $params['officeid'] = $officeId;
        }

        $sql .= " ORDER BY trv.voucher_date DESC, trv.id DESC";

        $results = $this->fetchAll($sql, $params);

        // Decode JSON items for easy consumption in views
        foreach ($results as &$r) {
            if (is_string($r['items'])) {
                $r['items'] = json_decode($r['items'], true) ?: [];
            }
        }
        return $results;
    }

    /**
     * Get a single voucher by ID.
     */
    public function getVoucherById(int $id, ?int $officeId = null): ?array {
        $sql = "SELECT trv.*, o.OfficeName as office_name, ag.name as agency_name
                FROM {$this->voucherTable} trv
                JOIN office o ON trv.officeid = o.Officeid
                LEFT JOIN agencies ag ON trv.agency_id = ag.id
                WHERE trv.id = :id";
        $params = ['id' => $id];
        if ($officeId !== null) {
            $sql .= " AND trv.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (is_string($row['items'])) {
                $row['items'] = json_decode($row['items'], true) ?: [];
            }
            return $row;
        }
        return null;
    }

    /**
     * Check if a voucher number already exists in office.
     */
    public function voucherNumberExists(string $voucherNo, int $officeId, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->voucherTable} WHERE voucher_no = :vno AND officeid = :officeid";
        $params = ['vno' => $voucherNo, 'officeid' => $officeId];
        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Create a Receipt Voucher header record with JSONB items.
     */
    public function createReceiptVoucher(array $data): int {
        $sql = "INSERT INTO {$this->voucherTable}
                (officeid, voucher_no, voucher_date, party, agency_id, source_type, source_id, source_ref_no, store_category, items, supply_status, remarks)
                VALUES
                (:officeid, :voucher_no, :voucher_date, :party, :agency_id, :source_type, :source_id, :source_ref_no, :store_category, :items::jsonb, :supply_status, :remarks)";

        $itemsJson = is_array($data['items']) ? json_encode($data['items']) : $data['items'];

        $params = [
            'officeid'       => $data['officeid'],
            'voucher_no'     => $data['voucher_no'],
            'voucher_date'   => $data['voucher_date'],
            'party'          => $data['party'] ?? null,
            'agency_id'      => !empty($data['agency_id']) ? (int)$data['agency_id'] : null,
            'source_type'    => $data['source_type'] ?? 'MANUAL',
            'source_id'      => !empty($data['source_id']) ? (int)$data['source_id'] : null,
            'source_ref_no'  => $data['source_ref_no'] ?? null,
            'store_category' => $data['store_category'] ?? 'general',
            'items'          => $itemsJson,
            'supply_status'  => $data['supply_status'] ?? 'COMPLETE',
            'remarks'        => $data['remarks'] ?? null
        ];

        return $this->executeInsert($sql, $params);
    }

    /**
     * Update an existing Receipt Voucher header record with JSONB items.
     */
    public function updateReceiptVoucherHeader(int $id, array $data): bool {
        $sql = "UPDATE {$this->voucherTable}
                SET voucher_no     = :voucher_no,
                    voucher_date   = :voucher_date,
                    party          = :party,
                    agency_id      = :agency_id,
                    source_type    = :source_type,
                    source_id      = :source_id,
                    source_ref_no  = :source_ref_no,
                    store_category = :store_category,
                    items          = :items::jsonb,
                    supply_status  = :supply_status,
                    remarks        = :remarks,
                    updated_at     = CURRENT_TIMESTAMP
                WHERE id = :id";

        $itemsJson = is_array($data['items']) ? json_encode($data['items']) : $data['items'];

        $params = [
            'id'             => $id,
            'voucher_no'     => $data['voucher_no'],
            'voucher_date'   => $data['voucher_date'],
            'party'          => $data['party'] ?? null,
            'agency_id'      => !empty($data['agency_id']) ? (int)$data['agency_id'] : null,
            'source_type'    => $data['source_type'] ?? 'MANUAL',
            'source_id'      => !empty($data['source_id']) ? (int)$data['source_id'] : null,
            'source_ref_no'  => $data['source_ref_no'] ?? null,
            'store_category' => $data['store_category'] ?? 'general',
            'items'          => $itemsJson,
            'supply_status'  => $data['supply_status'] ?? 'COMPLETE',
            'remarks'        => $data['remarks'] ?? null
        ];

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Create a line entry in the central inventory ledger linked to rv_id.
     */
    public function createReceiptLedgerItem(array $data): int {
        $sql = "INSERT INTO {$this->table}
                (officeid, item_id, transaction_type, quantity, transaction_date, voucher_no, party, remarks, rv_id)
                VALUES
                (:officeid, :item_id, 'RECEIPT', :quantity, :transaction_date, :voucher_no, :party, :remarks, :rv_id)";

        return $this->executeInsert($sql, [
            'officeid'         => $data['officeid'],
            'item_id'          => $data['item_id'],
            'quantity'         => $data['quantity'],
            'transaction_date' => $data['transaction_date'],
            'voucher_no'       => $data['voucher_no'],
            'party'            => $data['party'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'rv_id'            => $data['rv_id'] ?? null
        ]);
    }

    /**
     * Delete ledger items referencing a voucher.
     */
    public function deleteLedgerItemsForVoucher(int $rvId, int $officeId, string $voucherNo): void {
        $sqlLedger = "DELETE FROM {$this->table} WHERE (rv_id = :rv_id) OR (officeid = :officeid AND voucher_no = :vno AND transaction_type = 'RECEIPT')";
        $stmt = $this->db->prepare($sqlLedger);
        $stmt->execute([
            'rv_id'    => $rvId,
            'officeid' => $officeId,
            'vno'      => $voucherNo
        ]);
    }

    /**
     * Delete a receipt voucher and all its associated inventory ledger items.
     */
    public function deleteReceiptVoucher(int $id, ?int $officeId): bool {
        // Find voucher first
        $v = $this->getVoucherById($id, $officeId);
        if (!$v) return false;

        $this->beginTransaction();
        try {
            // Revert spare parts stock if any items were spare parts
            if (!empty($v['items']) && is_array($v['items'])) {
                foreach ($v['items'] as $item) {
                    if (!empty($item['is_spare_part']) && !empty($item['spare_part_id'])) {
                        $qty = (float)($item['quantity'] ?? 0);
                        if ($qty > 0) {
                            $spStmt = $this->db->prepare("UPDATE public.turf_machine_spare_parts 
                                                          SET received_qty = GREATEST(0, received_qty - :qty), 
                                                              current_stock = GREATEST(0, current_stock - :qty),
                                                              updated_at = CURRENT_TIMESTAMP 
                                                          WHERE id = :spid");
                            $spStmt->execute(['qty' => $qty, 'spid' => (int)$item['spare_part_id']]);
                        }
                    }
                }
            }

            // Delete ledger items referencing this voucher (or matching office and voucher_no)
            $this->deleteLedgerItemsForVoucher($id, (int)$v['officeid'], (string)$v['voucher_no']);

            // Delete voucher header
            $sqlVoucher = "DELETE FROM {$this->voucherTable} WHERE id = :id";
            $stmtV = $this->db->prepare($sqlVoucher);
            $stmtV->execute(['id' => $id]);

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Check if item already exists in a voucher in the ledger.
     */
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

    /**
     * Fetch registered agencies.
     */
    public function getAgencies(?int $officeId = null): array {
        $sql = "SELECT id, name, gst_no, contact_person, address FROM agencies ORDER BY name ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Fetch agreements for office with linked agency and AA/ES BOQ.
     */
    public function getAgreements(?int $officeId = null): array {
        $sql = "SELECT 
                    a.id, 
                    a.agreement_no, 
                    a.officeid, 
                    a.agency_id, 
                    ag.name as agency_name,
                    ae.id as aa_es_id,
                    ae.alias as aa_es_alias,
                    ae.boq
                FROM agreements a
                LEFT JOIN agencies ag ON a.agency_id = ag.id
                LEFT JOIN aa_es ae ON a.aa_es_id = ae.id
                WHERE 1=1";
        $params = [];
        if ($officeId !== null) {
            $sql .= " AND a.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY a.agreement_no ASC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Fetch supply orders for office with linked agency and AA/ES BOQ.
     */
    public function getSupplyOrders(?int $officeId = null): array {
        $sql = "SELECT 
                    so.id, 
                    so.supply_order_no, 
                    so.officeid, 
                    so.agency_id, 
                    ag.name as agency_name,
                    ae.id as aa_es_id,
                    ae.alias as aa_es_alias,
                    ae.boq
                FROM supply_orders so
                LEFT JOIN agencies ag ON so.agency_id = ag.id
                LEFT JOIN aa_es ae ON so.aa_es_id = ae.id
                WHERE 1=1";
        $params = [];
        if ($officeId !== null) {
            $sql .= " AND so.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY so.supply_order_no ASC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Fetch work orders for office with linked agency and AA/ES BOQ.
     */
    public function getWorkOrders(?int $officeId = null): array {
        $sql = "SELECT 
                    wo.id, 
                    wo.work_order_no, 
                    wo.officeid, 
                    wo.agency_id, 
                    ag.name as agency_name,
                    ae.id as aa_es_id,
                    ae.alias as aa_es_alias,
                    ae.boq
                FROM work_orders wo
                LEFT JOIN agencies ag ON wo.agency_id = ag.id
                LEFT JOIN aa_es ae ON wo.aa_es_id = ae.id
                WHERE 1=1";
        $params = [];
        if ($officeId !== null) {
            $sql .= " AND wo.officeid = :officeid";
            $params['officeid'] = $officeId;
        }
        $sql .= " ORDER BY wo.work_order_no ASC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Retrieve full details and extract line items for an Agreement, SO, or WO.
     */
    public function getContractDetails(string $sourceType, int $sourceId): ?array {
        $sourceType = strtoupper(trim($sourceType));
        $record = null;
        $refNo = '';

        if ($sourceType === 'AGREEMENT') {
            $sql = "SELECT a.id, a.agreement_no as ref_no, a.officeid, a.agency_id, ag.name as agency_name,
                           ae.id as aa_es_id, ae.alias as aa_es_alias, ae.boq
                    FROM agreements a
                    LEFT JOIN agencies ag ON a.agency_id = ag.id
                    LEFT JOIN aa_es ae ON a.aa_es_id = ae.id
                    WHERE a.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $sourceId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($sourceType === 'SUPPLY_ORDER') {
            $sql = "SELECT so.id, so.supply_order_no as ref_no, so.officeid, so.agency_id, ag.name as agency_name,
                           ae.id as aa_es_id, ae.alias as aa_es_alias, ae.boq
                    FROM supply_orders so
                    LEFT JOIN agencies ag ON so.agency_id = ag.id
                    LEFT JOIN aa_es ae ON so.aa_es_id = ae.id
                    WHERE so.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $sourceId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($sourceType === 'WORK_ORDER') {
            $sql = "SELECT wo.id, wo.work_order_no as ref_no, wo.officeid, wo.agency_id, ag.name as agency_name,
                           ae.id as aa_es_id, ae.alias as aa_es_alias, ae.boq
                    FROM work_orders wo
                    LEFT JOIN agencies ag ON wo.agency_id = ag.id
                    LEFT JOIN aa_es ae ON wo.aa_es_id = ae.id
                    WHERE wo.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $sourceId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$record) return null;

        // Fetch cumulative previously received quantities from past vouchers for this contract
        $prevSql = "SELECT items FROM {$this->voucherTable} WHERE UPPER(source_type) = :stype AND source_id = :sid";
        $prevStmt = $this->db->prepare($prevSql);
        $prevStmt->execute(['stype' => $sourceType, 'sid' => $sourceId]);
        $prevVouchers = $prevStmt->fetchAll(PDO::FETCH_ASSOC);

        $receivedTotals = [];
        foreach ($prevVouchers as $pv) {
            $pvItems = is_string($pv['items']) ? json_decode($pv['items'], true) : $pv['items'];
            if (is_array($pvItems)) {
                foreach ($pvItems as $pItem) {
                    $descKey = strtolower(trim($pItem['description'] ?? ''));
                    $qty = (float)($pItem['quantity'] ?? 0);
                    if ($descKey !== '') {
                        $receivedTotals[$descKey] = ($receivedTotals[$descKey] ?? 0.0) + $qty;
                    }
                }
            }
        }

        // Parse BOQ items into tabular schedule
        $items = [];
        $boq = $record['boq'];
        if (is_string($boq)) {
            $boq = json_decode($boq, true);
        }

        if (is_array($boq)) {
            $boqItems = $boq['Items'] ?? $boq['items'] ?? [];
            if (is_array($boqItems)) {
                foreach ($boqItems as $idx => $bItem) {
                    $desc = trim($bItem['description'] ?? $bItem['item_name'] ?? 'Item ' . ($idx + 1));
                    $descKey = strtolower($desc);
                    $sanctionedQty = (float)($bItem['qty'] ?? $bItem['quantity'] ?? 0);
                    $prevReceived = (float)($receivedTotals[$descKey] ?? 0.0);
                    $balanceQty = max(0.0, $sanctionedQty - $prevReceived);

                    $items[] = [
                        'boq_index'               => $idx,
                        'description'             => $desc,
                        'sanctioned_quantity'     => $sanctionedQty,
                        'previously_received_qty' => $prevReceived,
                        'balance_quantity'        => $balanceQty,
                        'quantity'                => $sanctionedQty, // for backward compatibility
                        'rate'                    => (float)($bItem['rate'] ?? 0),
                        'unit'                    => $bItem['unit'] ?? 'Unit'
                    ];
                }
            }
        }

        return [
            'source_type'   => $sourceType,
            'source_id'     => $sourceId,
            'source_ref_no' => $record['ref_no'] ?? '',
            'officeid'      => $record['officeid'] ?? null,
            'agency_id'     => $record['agency_id'] ?? null,
            'agency_name'   => $record['agency_name'] ?? '',
            'aa_es_alias'   => $record['aa_es_alias'] ?? '',
            'items'         => $items
        ];
    }
}
