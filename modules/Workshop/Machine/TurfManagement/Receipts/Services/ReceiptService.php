<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Receipts\Services;

require_once __DIR__ . '/../Models/ReceiptModel.php';
require_once __DIR__ . '/../../SpareParts/Models/SparePartModel.php';
require_once __DIR__ . '/../../InventoryItems/Models/InventoryItemModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Receipts\Models\ReceiptModel;
use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Models\SparePartModel;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Models\InventoryItemModel;
use App\Core\Database;
use Exception;

class ReceiptService {
    protected $receiptModel;

    public function __construct() {
        $this->receiptModel = new ReceiptModel();
    }

    public function getReceiptVouchers(?int $officeId): array {
        return $this->receiptModel->getReceiptVouchers($officeId);
    }

    public function getVoucherById(int $id, ?int $officeId = null): ?array {
        return $this->receiptModel->getVoucherById($id, $officeId);
    }

    public function deleteReceiptEntry(int $id, ?int $officeId): bool {
        return $this->receiptModel->deleteReceiptVoucher($id, $officeId);
    }

    public function getSourceOptions(?int $officeId = null): array {
        return [
            'agencies'      => $this->receiptModel->getAgencies($officeId),
            'agreements'    => $this->receiptModel->getAgreements($officeId),
            'supply_orders' => $this->receiptModel->getSupplyOrders($officeId),
            'work_orders'   => $this->receiptModel->getWorkOrders($officeId)
        ];
    }

    public function getSourceContractDetails(string $sourceType, int $sourceId): ?array {
        return $this->receiptModel->getContractDetails($sourceType, $sourceId);
    }

    /**
     * Process Multi-RV Batch Staging with JSONB items and perpetual ledger updates.
     * Solves the statutory 40-Liter Fuel constraint and handles Multi-RV Staging.
     */
    public function processMultiRvReceipt(array $masterData, array $stagedItems): array {
        if (empty($stagedItems)) {
            throw new Exception("No items staged for receipt. Please stage at least one item.");
        }

        $defaultOfficeId = (int)($masterData['officeid'] ?? $masterData['office_id'] ?? 0);
        if ($defaultOfficeId <= 0) {
            throw new Exception("Office ID is required.");
        }

        // Group staged items by Voucher Number
        $vouchersGrouped = [];
        foreach ($stagedItems as $index => $item) {
            $vNo = trim((string)($item['voucher_no'] ?? $masterData['voucher_no'] ?? ''));
            if ($vNo === '') {
                throw new Exception("Item #" . ($index + 1) . " is missing a Voucher Number.");
            }

            if (!isset($vouchersGrouped[$vNo])) {
                $vouchersGrouped[$vNo] = [
                    'officeid'       => (int)($item['officeid'] ?? $defaultOfficeId),
                    'voucher_no'     => $vNo,
                    'voucher_date'   => $item['voucher_date'] ?? $masterData['transaction_date'] ?? date('Y-m-d'),
                    'party'          => trim((string)($item['party'] ?? $masterData['party'] ?? '')),
                    'agency_id'      => !empty($item['agency_id']) ? (int)$item['agency_id'] : (!empty($masterData['agency_id']) ? (int)$masterData['agency_id'] : null),
                    'source_type'    => strtoupper(trim((string)($item['source_type'] ?? $masterData['source_type'] ?? 'MANUAL'))),
                    'source_id'      => !empty($item['source_id']) ? (int)$item['source_id'] : (!empty($masterData['source_id']) ? (int)$masterData['source_id'] : null),
                    'source_ref_no'  => trim((string)($item['source_ref_no'] ?? $masterData['source_ref_no'] ?? '')),
                    'store_category' => $item['store_category'] ?? $masterData['category_code'] ?? 'general',
                    'supply_status'  => $item['supply_status'] ?? $masterData['supply_status'] ?? 'COMPLETE',
                    'remarks'        => $item['remarks'] ?? $masterData['remarks'] ?? null,
                    'items'          => []
                ];
            }

            // Enforce duplicate rule: Within the SAME Voucher Number, no identical item can be added twice
            $itemIdKey = !empty($item['item_id']) ? 'item_' . $item['item_id'] : (!empty($item['spare_part_id']) ? 'sp_' . $item['spare_part_id'] : 'desc_' . strtolower(trim($item['description'] ?? '')));
            foreach ($vouchersGrouped[$vNo]['items'] as $existingItem) {
                $existingKey = !empty($existingItem['item_id']) ? 'item_' . $existingItem['item_id'] : (!empty($existingItem['spare_part_id']) ? 'sp_' . $existingItem['spare_part_id'] : 'desc_' . strtolower(trim($existingItem['description'] ?? '')));
                if ($existingKey === $itemIdKey) {
                    throw new Exception("Duplicate Entry: Item '{$item['description']}' was added more than once under Voucher '{$vNo}'. Each item can only appear once per Voucher.");
                }
            }

            $vouchersGrouped[$vNo]['items'][] = $item;
        }

        // Verify voucher numbers do not already exist in the database
        foreach ($vouchersGrouped as $vNo => $vData) {
            if ($this->receiptModel->voucherNumberExists($vNo, $vData['officeid'])) {
                throw new Exception("Voucher Number '{$vNo}' already exists in the records for this Office. Please assign unique voucher numbers.");
            }
        }

        // Validate Sanctioned Quantities for Contract-linked orders (WO, SO, Agreement)
        $contractItemsDelivered = [];
        foreach ($stagedItems as $it) {
            $sId = !empty($it['source_id']) ? (int)$it['source_id'] : (!empty($masterData['source_id']) ? (int)$masterData['source_id'] : 0);
            $sType = strtoupper(trim((string)($it['source_type'] ?? $masterData['source_type'] ?? 'MANUAL')));
            if ($sId > 0 && $sType !== 'MANUAL') {
                $cKey = $sType . '_' . $sId;
                if (!isset($contractItemsDelivered[$cKey])) {
                    $contractItemsDelivered[$cKey] = [
                        'type' => $sType,
                        'id'   => $sId,
                        'items' => []
                    ];
                }
                $descKey = strtolower(trim((string)($it['description'] ?? '')));
                $contractItemsDelivered[$cKey]['items'][$descKey] = ($contractItemsDelivered[$cKey]['items'][$descKey] ?? 0.0) + (float)($it['quantity'] ?? 0);
            }
        }

        foreach ($contractItemsDelivered as $cGroup) {
            $details = $this->receiptModel->getContractDetails($cGroup['type'], $cGroup['id']);
            if ($details && !empty($details['items'])) {
                foreach ($details['items'] as $bItem) {
                    $bDescKey = strtolower(trim($bItem['description'] ?? ''));
                    if (isset($cGroup['items'][$bDescKey])) {
                        $deliveredInThisBatch = $cGroup['items'][$bDescKey];
                        $sanctioned = (float)($bItem['sanctioned_quantity'] ?? 0);
                        $previouslyReceived = (float)($bItem['previously_received_qty'] ?? 0);
                        $totalCumulative = $previouslyReceived + $deliveredInThisBatch;

                        if ($sanctioned > 0 && $totalCumulative > $sanctioned) {
                            $exceededBy = $totalCumulative - $sanctioned;
                            throw new Exception("Statutory Limit Exceeded: For item '{$bItem['description']}', sanctioned quantity is {$sanctioned} {$bItem['unit']}, previously received is {$previouslyReceived} {$bItem['unit']}. Attempting to deliver {$deliveredInThisBatch} {$bItem['unit']} exceeds the remaining balance by {$exceededBy} {$bItem['unit']}. Delivered quantity cannot exceed sanctioned quantity.");
                        }
                    }
                }
            }
        }

        $createdVoucherIds = [];
        $totalItemsPosted = 0;

        try {
            $this->receiptModel->beginTransaction();
            $db = Database::getInstance()->getConnection();

            foreach ($vouchersGrouped as $vNo => $vData) {
                $jsonbItems = [];
                $processedItemsForLedger = [];

                foreach ($vData['items'] as $item) {
                    $officeId = $vData['officeid'];
                    $qty = (float)($item['quantity'] ?? 0);
                    if ($qty <= 0) {
                        throw new Exception("Quantity must be greater than zero for item '{$item['description']}' in Voucher '{$vNo}'.");
                    }

                    $itemId = !empty($item['item_id']) ? (int)$item['item_id'] : null;
                    $isNewItem = !empty($item['is_new_item']);
                    $isSparePart = !empty($item['is_spare_part']);
                    $sparePartId = !empty($item['spare_part_id']) ? (int)$item['spare_part_id'] : null;

                    // Handle on-the-fly new items if not already assigned an itemId
                    if (empty($itemId) && empty($sparePartId)) {
                        if ($isSparePart && !empty($item['machine_id'])) {
                            $spData = [
                                'officeid'     => $officeId,
                                'machine_id'   => (int)($item['machine_id'] ?? 0),
                                'part_no'      => trim((string)($item['part_no'] ?? '')),
                                'nomenclature' => trim((string)($item['description'] ?? '')),
                                'received_qty' => $qty,
                                'issued_qty'   => 0,
                                'unit'         => trim((string)($item['unit'] ?? 'Pcs'))
                            ];
                            $sparePartModel = new SparePartModel();
                            $sparePartId = $sparePartModel->createSparePart($spData);
                        } else {
                            $desc = trim((string)($item['description'] ?? ''));
                            if (empty($desc)) {
                                throw new Exception("Description is required for new store items.");
                            }

                            // Check if item already exists in catalog
                            $existStmt = $db->prepare("SELECT id FROM turf_inventory_items WHERE LOWER(TRIM(description)) = LOWER(TRIM(:desc)) LIMIT 1");
                            $existStmt->execute(['desc' => $desc]);
                            $existingId = $existStmt->fetchColumn();

                            if ($existingId) {
                                $itemId = (int)$existingId;
                            } else {
                                $catCode = $item['category_code'] ?? $vData['store_category'] ?? 'agronomy';
                                $catIdStmt = $db->prepare("SELECT id FROM turf_inventory_categories WHERE code = :code OR LOWER(name) LIKE :likeCode LIMIT 1");
                                $catIdStmt->execute(['code' => $catCode, 'likeCode' => "%{$catCode}%"]);
                                $catId = $catIdStmt->fetchColumn();

                                $invData = [
                                    'description' => $desc,
                                    'ac_unit'     => trim((string)($item['unit'] ?? 'Pcs')),
                                    'category_id' => $catId ? (int)$catId : null
                                ];
                                $invModel = new InventoryItemModel();
                                $itemId = $invModel->createItem($invData);
                            }
                        }
                    } elseif ($isSparePart && $sparePartId) {
                        // Existing spare part: update received_qty and current_stock in turf_machine_spare_parts
                        $upStmt = $db->prepare("UPDATE public.turf_machine_spare_parts 
                                                SET received_qty = received_qty + :qty, 
                                                    current_stock = current_stock + :qty,
                                                    updated_at = CURRENT_TIMESTAMP 
                                                WHERE id = :spid");
                        $upStmt->execute(['qty' => $qty, 'spid' => $sparePartId]);
                    }

                    // Build JSONB payload representation for the voucher header
                    $lineItemData = [
                        'item_id'       => $itemId,
                        'spare_part_id' => $sparePartId,
                        'is_spare_part' => $isSparePart,
                        'description'   => trim((string)($item['description'] ?? '')),
                        'quantity'      => $qty,
                        'unit'          => trim((string)($item['unit'] ?? 'Pcs')),
                        'rate'          => (float)($item['rate'] ?? 0),
                        'amount'        => (float)($qty * ($item['rate'] ?? 0)),
                        'remarks'       => trim((string)($item['remarks'] ?? ''))
                    ];
                    $jsonbItems[] = $lineItemData;

                    // Prepare ledger row
                    $processedItemsForLedger[] = [
                        'officeid'         => $officeId,
                        'item_id'          => $itemId ?: 0,
                        'quantity'         => $qty,
                        'transaction_date' => $vData['voucher_date'],
                        'voucher_no'       => $vNo,
                        'party'            => $vData['party'],
                        'remarks'          => $item['remarks'] ?? $vData['remarks'] ?? null
                    ];
                }

                // 1 RV = 1 Row in turf_receipt_vouchers with items JSONB
                $voucherHeaderData = [
                    'officeid'       => $vData['officeid'],
                    'voucher_no'     => $vNo,
                    'voucher_date'   => $vData['voucher_date'],
                    'party'          => $vData['party'],
                    'agency_id'      => $vData['agency_id'],
                    'source_type'    => $vData['source_type'],
                    'source_id'      => $vData['source_id'],
                    'source_ref_no'  => $vData['source_ref_no'],
                    'store_category' => $vData['store_category'],
                    'items'          => $jsonbItems,
                    'supply_status'  => $vData['supply_status'],
                    'remarks'        => $vData['remarks']
                ];

                $rvId = $this->receiptModel->createReceiptVoucher($voucherHeaderData);
                $createdVoucherIds[] = $rvId;

                // Post each line item to the perpetual inventory ledger referencing rv_id
                foreach ($processedItemsForLedger as $ledgerData) {
                    // Only insert into general inventory ledger if an item_id exists
                    if (!empty($ledgerData['item_id'])) {
                        $ledgerData['rv_id'] = $rvId;
                        $this->receiptModel->createReceiptLedgerItem($ledgerData);
                        $totalItemsPosted++;
                    }
                }
            }

            $this->receiptModel->commit();

            return [
                'status'         => 'success',
                'vouchers_count' => count($vouchersGrouped),
                'voucher_ids'    => $createdVoucherIds,
                'items_count'    => count($stagedItems),
                'message'        => 'Successfully committed ' . count($vouchersGrouped) . ' Receipt Voucher(s) containing ' . count($stagedItems) . ' staged item(s).'
            ];

        } catch (Exception $e) {
            if ($this->receiptModel->inTransaction()) {
                $this->receiptModel->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Backward-compatible wrapper for single-voucher createBulkReceipt.
     */
    public function processBulkReceipt(array $masterData, array $items): void {
        // Tag each item with master voucher info and pass to processMultiRvReceipt
        $stagedItems = [];
        foreach ($items as $it) {
            $merged = array_merge($masterData, $it);
            $stagedItems[] = $merged;
        }
        $this->processMultiRvReceipt($masterData, $stagedItems);
    }

    /**
     * Update an existing Receipt Voucher and synchronize with central inventory ledger & spare parts stock.
     */
    public function updateReceiptVoucher(int $id, array $masterData, array $items, ?int $officeId = null): array {
        if (empty($items)) {
            throw new Exception("Voucher must contain at least one line item.");
        }

        $existingVoucher = $this->receiptModel->getVoucherById($id, $officeId);
        if (!$existingVoucher) {
            throw new Exception("Receipt Voucher #{$id} not found.");
        }

        $vOfficeId = (int)$existingVoucher['officeid'];
        $vNo = trim((string)($masterData['voucher_no'] ?? $existingVoucher['voucher_no'] ?? ''));
        if ($vNo === '') {
            throw new Exception("Voucher Number is required.");
        }

        if ($this->receiptModel->voucherNumberExists($vNo, $vOfficeId, $id)) {
            throw new Exception("Voucher Number '{$vNo}' already exists for this office. Please specify a unique voucher number.");
        }

        $vDate = !empty($masterData['voucher_date']) ? $masterData['voucher_date'] : $existingVoucher['voucher_date'];
        $party = trim((string)($masterData['party'] ?? $existingVoucher['party'] ?? ''));
        $agencyId = !empty($masterData['agency_id']) ? (int)$masterData['agency_id'] : (!empty($existingVoucher['agency_id']) ? (int)$existingVoucher['agency_id'] : null);
        $sourceType = strtoupper(trim((string)($masterData['source_type'] ?? $existingVoucher['source_type'] ?? 'MANUAL')));
        $sourceId = !empty($masterData['source_id']) ? (int)$masterData['source_id'] : (!empty($existingVoucher['source_id']) ? (int)$existingVoucher['source_id'] : null);
        $sourceRefNo = trim((string)($masterData['source_ref_no'] ?? $existingVoucher['source_ref_no'] ?? ''));
        $storeCategory = $masterData['store_category'] ?? $masterData['category_code'] ?? $existingVoucher['store_category'] ?? 'general';
        $supplyStatus = $masterData['supply_status'] ?? $existingVoucher['supply_status'] ?? 'COMPLETE';
        $remarks = $masterData['remarks'] ?? $existingVoucher['remarks'] ?? null;

        $db = Database::getInstance()->getConnection();
        $this->receiptModel->beginTransaction();

        try {
            // 1. Revert previous spare parts stock for old items in this voucher
            if (!empty($existingVoucher['items']) && is_array($existingVoucher['items'])) {
                foreach ($existingVoucher['items'] as $oldItem) {
                    if (!empty($oldItem['is_spare_part']) && !empty($oldItem['spare_part_id'])) {
                        $oldQty = (float)($oldItem['quantity'] ?? 0);
                        if ($oldQty > 0) {
                            $spStmt = $db->prepare("UPDATE public.turf_machine_spare_parts 
                                                    SET received_qty = GREATEST(0, received_qty - :qty), 
                                                        current_stock = GREATEST(0, current_stock - :qty),
                                                        updated_at = CURRENT_TIMESTAMP 
                                                    WHERE id = :spid");
                            $spStmt->execute(['qty' => $oldQty, 'spid' => (int)$oldItem['spare_part_id']]);
                        }
                    }
                }
            }

            // 2. Remove all existing ledger items for this voucher
            $this->receiptModel->deleteLedgerItemsForVoucher($id, $vOfficeId, (string)$existingVoucher['voucher_no']);

            // 3. Process new line items
            $jsonbItems = [];
            $processedItemsForLedger = [];

            foreach ($items as $idx => $item) {
                $qty = (float)($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw new Exception("Item #" . ($idx + 1) . " ('" . ($item['description'] ?? 'Item') . "') must have a quantity greater than zero.");
                }

                $desc = trim((string)($item['description'] ?? ''));
                if ($desc === '') {
                    throw new Exception("Item #" . ($idx + 1) . " requires a description/name.");
                }

                $unit = trim((string)($item['unit'] ?? 'Pcs'));
                $rate = (float)($item['rate'] ?? 0);
                $amount = (float)($item['amount'] ?? ($qty * $rate));
                $itemRemarks = trim((string)($item['remarks'] ?? ''));

                $itemId = !empty($item['item_id']) ? (int)$item['item_id'] : null;
                $isSparePart = !empty($item['is_spare_part']);
                $sparePartId = !empty($item['spare_part_id']) ? (int)$item['spare_part_id'] : null;

                // If not a spare part and itemId is missing, attempt to find or create in turf_inventory_items
                if (!$isSparePart && empty($itemId)) {
                    $existStmt = $db->prepare("SELECT id FROM turf_inventory_items WHERE LOWER(TRIM(description)) = LOWER(TRIM(:desc)) LIMIT 1");
                    $existStmt->execute(['desc' => $desc]);
                    $existingId = $existStmt->fetchColumn();

                    if ($existingId) {
                        $itemId = (int)$existingId;
                    } else {
                        $catCode = $storeCategory;
                        $catIdStmt = $db->prepare("SELECT id FROM turf_inventory_categories WHERE code = :code OR LOWER(name) LIKE :likeCode LIMIT 1");
                        $catIdStmt->execute(['code' => $catCode, 'likeCode' => "%{$catCode}%"]);
                        $catId = $catIdStmt->fetchColumn();

                        $invModel = new InventoryItemModel();
                        $itemId = $invModel->createItem([
                            'description' => $desc,
                            'ac_unit'     => $unit,
                            'category_id' => $catId ? (int)$catId : null
                        ]);
                    }
                }

                // If spare part, update received_qty and current_stock in turf_machine_spare_parts
                if ($isSparePart && $sparePartId) {
                    $upStmt = $db->prepare("UPDATE public.turf_machine_spare_parts 
                                            SET received_qty = received_qty + :qty, 
                                                current_stock = current_stock + :qty,
                                                updated_at = CURRENT_TIMESTAMP 
                                            WHERE id = :spid");
                    $upStmt->execute(['qty' => $qty, 'spid' => $sparePartId]);
                }

                $lineItem = [
                    'item_id'       => $itemId,
                    'spare_part_id' => $sparePartId,
                    'is_spare_part' => $isSparePart,
                    'description'   => $desc,
                    'quantity'      => $qty,
                    'unit'          => $unit,
                    'rate'          => $rate,
                    'amount'        => $amount,
                    'remarks'       => $itemRemarks
                ];
                $jsonbItems[] = $lineItem;

                if (!empty($itemId)) {
                    $processedItemsForLedger[] = [
                        'officeid'         => $vOfficeId,
                        'item_id'          => $itemId,
                        'quantity'         => $qty,
                        'transaction_date' => $vDate,
                        'voucher_no'       => $vNo,
                        'party'            => $party,
                        'remarks'          => $itemRemarks ?: $remarks,
                        'rv_id'            => $id
                    ];
                }
            }

            // 4. Update Header in turf_receipt_vouchers
            $headerData = [
                'voucher_no'     => $vNo,
                'voucher_date'   => $vDate,
                'party'          => $party,
                'agency_id'      => $agencyId,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'source_ref_no'  => $sourceRefNo,
                'store_category' => $storeCategory,
                'items'          => $jsonbItems,
                'supply_status'  => $supplyStatus,
                'remarks'        => $remarks
            ];
            $this->receiptModel->updateReceiptVoucherHeader($id, $headerData);

            // 5. Insert new ledger rows
            foreach ($processedItemsForLedger as $lData) {
                $this->receiptModel->createReceiptLedgerItem($lData);
            }

            $this->receiptModel->commit();

            return [
                'status'     => 'success',
                'voucher_id' => $id,
                'voucher_no' => $vNo,
                'message'    => "Receipt Voucher '{$vNo}' updated successfully!"
            ];

        } catch (Exception $e) {
            if ($this->receiptModel->inTransaction()) {
                $this->receiptModel->rollBack();
            }
            throw $e;
        }
    }
}
