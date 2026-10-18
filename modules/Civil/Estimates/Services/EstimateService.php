<?php
declare(strict_types=1);

namespace App\Modules\Civil\Estimates\Services;

require_once __DIR__ . '/../Models/EstimateModel.php';

use App\Modules\Civil\Estimates\Models\EstimateModel;
use Exception;

class EstimateService
{
    private EstimateModel $model;

    public function __construct()
    {
        $this->model = new EstimateModel();
    }

    public function getAllEstimates(?int $officeId = null): array
    {
        return $this->model->getAllEstimates($officeId);
    }

    public function getEstimateDetails(int $id): array
    {
        $estimate = $this->model->getEstimateById($id);
        if (!$estimate) {
            throw new Exception("Estimate with ID #{$id} not found.");
        }
        $items = $this->model->getEstimateItems($id);
        $totals = $this->model->recalculateTotals($id);

        return [
            'estimate' => array_merge($estimate, $totals),
            'items' => $items
        ];
    }

    public function createEstimate(array $data, int $userId): int
    {
        if (empty($data['work_name'])) {
            throw new Exception("Work name is required.");
        }
        if (empty($data['estimate_no'])) {
            throw new Exception("Estimate number is required.");
        }

        $data['created_by'] = $userId;
        $data['estimated_amount'] = 0.00;
        $data['total_amount'] = 0.00;
        $data['status'] = 'Draft';

        return $this->model->createEstimate($data);
    }

    public function addItemToEstimate(int $estimateId, array $itemData): array
    {
        if (empty($itemData['item_code'])) {
            throw new Exception("Item code is required.");
        }
        $qty = (float)($itemData['quantity'] ?? 0.0);
        if ($qty <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }

        // If rate or description not provided, fetch from DSR master
        if (empty($itemData['description']) || !isset($itemData['rate'])) {
            $dsr = $this->model->getDsrItemByCode($itemData['item_code']);
            if (!$dsr) {
                throw new Exception("DSR Item '{$itemData['item_code']}' not found in DSR 2023 master.");
            }
            $itemData['sub_head_no'] = $dsr['sub_head_no'];
            $itemData['description'] = $dsr['description'];
            $itemData['unit'] = $dsr['unit'];
            $itemData['rate'] = (float)$dsr['rate'];
        }

        $this->model->addEstimateItem($estimateId, $itemData);
        return $this->getEstimateDetails($estimateId);
    }

    public function removeItem(int $itemId, int $estimateId): array
    {
        $this->model->deleteEstimateItem($itemId, $estimateId);
        return $this->getEstimateDetails($estimateId);
    }

    public function searchDsr(string $query, ?int $subHead = null): array
    {
        return $this->model->searchDsrItems($query, $subHead, 25);
    }
}
