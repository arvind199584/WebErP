<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Indents\Services;

require_once __DIR__ . '/../Models/IndentModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Indents\Models\IndentModel;
use Exception;

class IndentService {
    protected $indentModel;

    public function __construct() {
        $this->indentModel = new IndentModel();
    }

    public function getIndentVouchers(?int $officeId): array {
        return $this->indentModel->getIndentVouchers($officeId);
    }

    public function getVoucherItems(string $voucherNo, ?int $officeId): array {
        return $this->indentModel->getVoucherItems($voucherNo, $officeId);
    }

    public function deleteIndentVoucher(string $voucherNo, ?int $officeId): bool {
        return $this->indentModel->deleteIndentVoucher($voucherNo, $officeId);
    }

    public function processBulkIndent(array $masterData, array $items): void {
        if (empty($items)) {
            throw new Exception("No items provided for this voucher.");
        }
        if (empty($masterData['voucher_no']) || empty($masterData['transaction_date'])) {
            throw new Exception("Voucher Number and Date are required.");
        }

        try {
            $this->indentModel->beginTransaction();

            foreach ($items as $item) {
                // Combine master data (header) with the individual item row data
                $rowData = [
                    'officeid'         => $masterData['officeid'],
                    'transaction_date' => $masterData['transaction_date'],
                    'voucher_no'       => $masterData['voucher_no'],
                    'party'            => $masterData['party'] ?? null, // Issued To
                    'item_id'          => $item['item_id'],
                    'quantity'         => $item['quantity']
                ];

                if (empty($rowData['item_id']) || empty($rowData['quantity']) || $rowData['quantity'] <= 0) {
                    throw new Exception("Invalid item or quantity detected. Voucher save aborted.");
                }

                // In a future step, we would call a Stock Calculation service right here
                // to verify we have enough stock BEFORE calling createIndentItem.
                // For now, we allow the issue.

                $this->indentModel->createIndentItem($rowData);
            }

            $this->indentModel->commit();

        } catch (Exception $e) {
            $this->indentModel->rollBack();
            throw $e;
        }
    }
}
