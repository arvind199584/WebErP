<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Receipts\Services;

require_once __DIR__ . '/../Models/ReceiptModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Receipts\Models\ReceiptModel;
use Exception;

class ReceiptService {
    protected $receiptModel;

    public function __construct() {
        $this->receiptModel = new ReceiptModel();
    }

    public function getReceiptVouchers(?int $officeId): array {
        return $this->receiptModel->getReceiptVouchers($officeId);
    }

    public function deleteReceiptEntry(int $id, ?int $officeId): bool {
        return $this->receiptModel->deleteReceiptEntry($id, $officeId);
    }

    public function processBulkReceipt(array $masterData, array $items): void {
        if (empty($items)) {
            throw new Exception("No items provided for this voucher.");
        }
        if (empty($masterData['voucher_no']) || empty($masterData['transaction_date'])) {
            throw new Exception("Voucher Number and Date are required.");
        }

        try {
            $this->receiptModel->beginTransaction();

            foreach ($items as $item) {
                $officeId = (int)$masterData['officeid'];
                $itemId = $item['item_id'] ?? null;
                $isNewItem = !empty($item['is_new_item']);
                $isSparePart = !empty($item['is_spare_part']);

                // If user entered a BRAND NEW spare part or item on the fly during RV creation
                if ($isNewItem) {
                    if ($isSparePart) {
                        // Create new spare part entry in turf_machine_spare_parts
                        $spData = [
                            'officeid'     => $officeId,
                            'machine_id'   => (int)($item['machine_id'] ?? 0),
                            'part_no'      => trim((string)($item['part_no'] ?? '')),
                            'nomenclature' => trim((string)($item['description'] ?? '')),
                            'received_qty' => (float)$item['quantity'],
                            'issued_qty'   => 0,
                            'unit'         => trim((string)($item['unit'] ?? 'Pcs'))
                        ];

                        if (empty($spData['machine_id']) || empty($spData['nomenclature'])) {
                            throw new Exception("Machine and Spare Part Name are required for new spare parts.");
                        }

                        $sparePartModel = new \App\Modules\Workshop\Machine\TurfManagement\SpareParts\Models\SparePartModel();
                        $sparePartModel->createSparePart($spData);
                        continue; // Stock quantity initialized directly on new spare part table
                    } else {
                        // Resolve category_id from category_code
                        $catCode = $item['category_code'] ?? $masterData['category_code'] ?? 'agronomy';
                        $db = \App\Core\Database::getInstance()->getConnection();
                        $catId = $db->query("SELECT id FROM turf_inventory_categories WHERE code = '{$catCode}' OR LOWER(name) LIKE '%{$catCode}%' LIMIT 1")->fetchColumn();

                        // Create new general inventory item in turf_inventory_items
                        $itemData = [
                            'description' => trim((string)($item['description'] ?? '')),
                            'ac_unit'     => trim((string)($item['unit'] ?? 'Pcs')),
                            'category_id' => $catId ? (int)$catId : null
                        ];

                        if (empty($itemData['description'])) {
                            throw new Exception("Item Description is required for new store items.");
                        }

                        $inventoryModel = new \App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Models\InventoryItemModel();
                        $itemId = $inventoryModel->createItem($itemData);
                    }
                } elseif ($isSparePart && !empty($item['spare_part_id'])) {
                    // Update existing spare part received_qty and current_stock in turf_machine_spare_parts
                    $spId = (int)$item['spare_part_id'];
                    $qty = (float)$item['quantity'];

                    $db = \App\Core\Database::getInstance()->getConnection();
                    $db->exec("UPDATE public.turf_machine_spare_parts 
                               SET received_qty = received_qty + {$qty}, 
                                   current_stock = current_stock + {$qty},
                                   updated_at = CURRENT_TIMESTAMP 
                               WHERE id = {$spId}");
                    continue;
                }

                // Standard General Store Item Ledger Entry
                if ($this->receiptModel->existsItemInVoucher($masterData['voucher_no'], (int)$itemId, $officeId)) {
                    throw new Exception("Duplicate Entry: Item '{$item['description']}' already exists in Voucher '{$masterData['voucher_no']}'. You cannot add the same item twice to the same voucher.");
                }

                $rowData = [
                    'officeid'         => $officeId,
                    'transaction_date' => $masterData['transaction_date'],
                    'voucher_no'       => $masterData['voucher_no'],
                    'party'            => $masterData['party'] ?? null,
                    'item_id'          => $itemId,
                    'quantity'         => (float)$item['quantity']
                ];

                if (empty($rowData['item_id']) || empty($rowData['quantity']) || $rowData['quantity'] <= 0) {
                    throw new Exception("Invalid item or quantity detected. Voucher save aborted.");
                }

                $this->receiptModel->createReceiptItem($rowData);
            }

            $this->receiptModel->commit();

        } catch (Exception $e) {
            // If ANY error occurs, undo all the inserts we just tried to do
            if ($this->receiptModel->inTransaction()) {
                $this->receiptModel->rollBack();
            }
            throw $e; // Re-throw the error so the controller can show it to the user
        }
    }
}
