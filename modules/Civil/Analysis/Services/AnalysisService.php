<?php
declare(strict_types=1);

namespace App\Modules\Civil\Analysis\Services;

require_once __DIR__ . '/../Models/AnalysisModel.php';

use App\Modules\Civil\Analysis\Models\AnalysisModel;
use Exception;

class AnalysisService
{
    private AnalysisModel $model;

    public function __construct()
    {
        $this->model = new AnalysisModel();
    }

    public function getAllJustifications(?int $officeId = null): array
    {
        return $this->model->getAllJustifications($officeId);
    }

    public function getJustificationDetails(int $id): array
    {
        $just = $this->model->getJustificationById($id);
        if (!$just) {
            throw new Exception("Justification statement #{$id} not found.");
        }
        $just['breakdowns'] = json_decode($just['item_breakdowns'] ?? '[]', true);
        return $just;
    }

    /**
     * Performs CPWD DAR Rate Analysis for all items in an estimate.
     */
    public function computeJustification(
        int $estimateId,
        float $waterPct = 1.0,
        float $cpOhPct = 15.0,
        float $gstPct = 18.0,
        float $cessPct = 1.0
    ): array {
        $estimate = $this->model->getEstimateWithItems($estimateId);
        if (!$estimate) {
            throw new Exception("Estimate #{$estimateId} not found.");
        }

        $items = $estimate['items'] ?? [];
        $analyzedItems = [];
        $totalEstimated = 0.0;
        $totalJustified = 0.0;

        foreach ($items as $item) {
            $itemCode = $item['item_code'];
            $qty = (float)$item['quantity'];
            $dsrRate = (float)$item['rate'];
            $estAmount = $qty * $dsrRate;
            $totalEstimated += $estAmount;

            // Fetch DAR constants for this item
            $breakups = $this->model->getItemDarBreakup($itemCode);

            if (!empty($breakups)) {
                $basisQty = (float)($breakups[0]['cost_basis_qty'] ?? 1.0);
                if ($basisQty <= 0) $basisQty = 1.0;

                $materialsCost = 0.0;
                $labourCost = 0.0;
                $machineryCost = 0.0;
                $resourcesUsed = [];

                foreach ($breakups as $b) {
                    $constantQty = (float)$b['quantity'];
                    // Use updated current market rate from global rates registry
                    $marketRate = (float)($b['current_market_rate'] ?? $b['standard_rate'] ?? 0.0);
                    $subTotal = $constantQty * $marketRate;

                    $cat = strtoupper((string)$b['category']);
                    if (str_contains($cat, 'LABOUR')) {
                        $labourCost += $subTotal;
                    } elseif (str_contains($cat, 'MACHINERY') || str_contains($cat, 'PLANT')) {
                        $machineryCost += $subTotal;
                    } else {
                        $materialsCost += $subTotal;
                    }

                    $resourcesUsed[] = [
                        'resource_code' => $b['resource_code'],
                        'description' => $b['description'],
                        'category' => $b['category'],
                        'unit' => $b['unit'],
                        'constant_qty' => $constantQty,
                        'dsr_rate' => (float)$b['standard_rate'],
                        'market_rate' => $marketRate,
                        'amount' => $subTotal
                    ];
                }

                // 1. Base Cost (Materials + Labour + Machinery)
                $baseCost = $materialsCost + $labourCost + $machineryCost;

                // 2. Water Charges (1% of Base Cost)
                $waterCharges = ($baseCost * $waterPct) / 100.0;

                // 3. Contractor Profit & Overheads (15% on Base + Water Charges)
                $cpOh = (($baseCost + $waterCharges) * $cpOhPct) / 100.0;

                // 4. Subtotal before GST & Cess
                $subtotalBeforeTax = $baseCost + $waterCharges + $cpOh;

                // 5. GST (18%)
                $gst = ($subtotalBeforeTax * $gstPct) / 100.0;

                // 6. Labour Cess (1%)
                $cess = ($subtotalBeforeTax * $cessPct) / 100.0;

                // Total Rate for Cost Basis Qty
                $totalForBasis = $subtotalBeforeTax + $gst + $cess;

                // Unit Justified Rate
                $justifiedRate = $totalForBasis / $basisQty;
                $hasDarAnalysis = true;
            } else {
                // Item has no granular DAR breakup, use DSR rate as baseline
                $justifiedRate = $dsrRate;
                $resourcesUsed = [];
                $hasDarAnalysis = false;
            }

            $justifiedAmount = $qty * $justifiedRate;
            $diffAmount = $justifiedAmount - $estAmount;
            $diffPercent = ($estAmount > 0) ? (($diffAmount / $estAmount) * 100.0) : 0.0;

            $totalJustified += $justifiedAmount;

            $analyzedItems[] = [
                'item_code' => $itemCode,
                'description' => $item['description'],
                'unit' => $item['unit'],
                'quantity' => $qty,
                'dsr_rate' => $dsrRate,
                'estimated_amount' => $estAmount,
                'justified_rate' => round($justifiedRate, 2),
                'justified_amount' => round($justifiedAmount, 2),
                'difference_amount' => round($diffAmount, 2),
                'difference_percent' => round($diffPercent, 2),
                'has_dar_analysis' => $hasDarAnalysis,
                'resources' => $resourcesUsed
            ];
        }

        $overallDiff = $totalJustified - $totalEstimated;
        $justificationPct = ($totalEstimated > 0) ? (($overallDiff / $totalEstimated) * 100.0) : 0.0;

        return [
            'estimate' => $estimate,
            'parameters' => [
                'water_charges_percent' => $waterPct,
                'cp_oh_percent' => $cpOhPct,
                'gst_percent' => $gstPct,
                'cess_percent' => $cessPct,
            ],
            'items' => $analyzedItems,
            'summary' => [
                'total_estimated_cost' => round($totalEstimated, 2),
                'total_justified_cost' => round($totalJustified, 2),
                'difference_amount' => round($overallDiff, 2),
                'justification_percentage' => round($justificationPct, 2),
                'is_higher' => ($justificationPct >= 0)
            ]
        ];
    }

    public function saveJustificationStatement(array $data, int $userId): int
    {
        $data['created_by'] = $userId;
        return $this->model->saveJustification($data);
    }
}
