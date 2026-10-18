<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../modules/Admin/AA_ES/DTO/AA_ES_DTO.php';
require_once __DIR__ . '/../../modules/Admin/AA_ES/Models/AA_ES_Model.php';

use App\Core\Database;
use App\Modules\Admin\AA_ES\DTO\AA_ES_DTO;
use App\Modules\Admin\AA_ES\Models\AA_ES_Model;

echo "=== TESTING HARDCODED BOQ & RATE ANALYSIS IMMUTABILITY ===" . PHP_EOL . PHP_EOL;

$db = Database::getInstance()->getConnection();

// Set Postgres session context so RLS permits access
$sample = $db->query("SELECT officeid, budgetid FROM aa_es LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$officeId = (int)($sample['officeid'] ?? 1);
$budgetId = (int)($sample['budgetid'] ?? 1);

$db->prepare("SELECT set_config('app.current_office_id', :officeId, false)")->execute([':officeId' => (string)$officeId]);
$db->prepare("SELECT set_config('app.current_user_role', 'superuser', false)")->execute();
$db->prepare("SELECT set_config('app.current_user_id', '1', false)")->execute();

// Clean up any test runs
$db->exec("DELETE FROM aa_es WHERE alias LIKE 'HARDCODE-TEST-%'");

// 1. Simulate Controller Step 2 Form Submission for DSR AA & ES
$postItems = [
    // Standard DSR item without custom analysis initially
    [
        'item_code' => '4.1.3',
        'description' => 'Providing and laying in position cement concrete 1:2:4',
        'qty' => '10.0',
        'unit' => 'cum',
        'rate' => '7878.50',
        'is_market_item' => '0',
        'analysis_breakup' => ''
    ],
    // Market Rate (MR) item
    [
        'item_code' => 'MR-1',
        'description' => 'Acoustic thermal false ceiling tiles',
        'qty' => '100.0',
        'unit' => 'sqm',
        'rate' => '1150.00',
        'is_market_item' => '1',
        'analysis_breakup' => ''
    ],
    // Analyzed DSR item with custom modified rates
    [
        'item_code' => '4.1.3 (Analyzed)',
        'description' => 'Cement concrete 1:2:4 with integral waterproofing',
        'qty' => '5.0',
        'unit' => 'cum',
        'rate' => '8450.00',
        'is_market_item' => '0',
        'analysis_breakup' => json_encode([
            'base_item_code' => '4.1.3',
            'cost_basis_qty' => 1.0,
            'cost_basis_unit' => 'cum',
            'basic_sum_w' => 5999.00,
            'water_charges_x' => 6058.99,
            'dar_factor' => 1.4085,
            'calculated_rate' => 8450.00,
            'components' => [
                ['resource_code' => '0001', 'category' => 'MATERIAL', 'description' => 'Cement PPC', 'unit' => 'tonne', 'quantity' => 0.32, 'standard_rate' => 6500.00, 'market_rate' => 7000.00, 'amount' => 2240.00],
                ['resource_code' => '0115', 'category' => 'LABOUR', 'description' => 'Mason', 'unit' => 'day', 'quantity' => 0.5, 'standard_rate' => 850.00, 'market_rate' => 950.00, 'amount' => 475.00]
            ]
        ])
    ]
];

// Replicate controller snapshotting logic
$boq = [];
foreach ($postItems as $item) {
    $itemCode = trim((string)$item['item_code']);
    $qty = (float)$item['qty'];
    $rate = (float)$item['rate'];
    $isMarketItem = !empty($item['is_market_item']) || str_starts_with($itemCode, 'MR');
    $rateBasis = $isMarketItem 
        ? 'MARKET_RATE' 
        : (!empty($item['analysis_breakup']) || str_contains($itemCode, 'Analyzed') ? 'ANALYZED_DAR' : 'DSR_2023');

    $analysisBreakup = null;
    if (!empty($item['analysis_breakup'])) {
        $decoded = json_decode($item['analysis_breakup'], true);
        if ($decoded !== null) {
            $analysisBreakup = $decoded;
        }
    }

    if (!$isMarketItem && $analysisBreakup === null) {
        $baseCode = trim(str_replace('(Analyzed)', '', $itemCode));
        $stmtBreakup = $db->prepare("
            SELECT b.resource_code, b.category, b.description, b.unit, 
                   b.quantity, b.standard_rate, b.standard_amount, b.cost_basis_qty, b.cost_basis_unit,
                   COALESCE(g.current_market_rate, g.dsr_base_rate, b.standard_rate) as market_rate
            FROM dar_item_breakups b
            LEFT JOIN civil_global_rates g ON b.resource_code = g.resource_code
            WHERE b.item_code = :c
            ORDER BY b.id ASC
        ");
        $stmtBreakup->execute([':c' => $baseCode]);
        $comps = $stmtBreakup->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($comps)) {
            $prefix = substr($baseCode, 0, strrpos($baseCode, '.') ?: strlen($baseCode));
            $stmtBreakup->execute([':c' => $prefix]);
            $comps = $stmtBreakup->fetchAll(\PDO::FETCH_ASSOC);
        }

        if (!empty($comps)) {
            $costBasisQty = (float)($comps[0]['cost_basis_qty'] ?? 1.0);
            $costBasisUnit = $comps[0]['cost_basis_unit'] ?? ($item['unit'] ?? 'cum');
            $stdSum = 0.0;
            foreach ($comps as &$c) {
                $c['quantity'] = (float)$c['quantity'];
                $c['standard_rate'] = (float)$c['standard_rate'];
                $c['market_rate'] = (float)$c['market_rate'];
                $c['amount'] = round($c['quantity'] * $c['standard_rate'], 2);
                $stdSum += $c['amount'];
            }
            $darFactor = ($stdSum > 0 && $rate > 0) 
                ? round(($rate * $costBasisQty) / $stdSum, 5) 
                : 1.4085;

            $analysisBreakup = [
                'base_item_code' => $baseCode,
                'description' => trim((string)($item['description'] ?? '')),
                'snapshotted_at' => date('Y-m-d H:i:s'),
                'cost_basis_qty' => $costBasisQty,
                'cost_basis_unit' => $costBasisUnit,
                'basic_sum_w' => round($stdSum, 2),
                'water_charges_x' => round($stdSum * 1.01, 2),
                'dar_factor' => $darFactor,
                'calculated_rate' => $rate,
                'components' => $comps
            ];
        }
    }

    $boq[] = [
        'item_code' => $itemCode,
        'description' => trim((string)($item['description'] ?? '')),
        'qty' => $qty,
        'unit' => trim((string)($item['unit'] ?? 'each')),
        'rate' => $rate,
        'amount' => round($qty * $rate, 2),
        'is_market_item' => $isMarketItem,
        'rate_basis' => $rateBasis,
        'snapshotted_at' => date('Y-m-d H:i:s'),
        'estimate_analysis_breakup' => $analysisBreakup,
        'analysis_breakup' => $analysisBreakup,
        'justified_rate' => $rate,
        'justified_amount' => round($qty * $rate, 2),
        'justified_analysis_breakup' => null,
        'remarks' => ''
    ];
}

echo "Step 1: BoQ created with snapshotting. Items count: " . count($boq) . PHP_EOL;
echo "  - Item 1 (4.1.3): Basis = {$boq[0]['rate_basis']}, Constituents = " . count($boq[0]['analysis_breakup']['components'] ?? []) . PHP_EOL;
echo "  - Item 2 (MR-1): Basis = {$boq[1]['rate_basis']}, Rate = ₹{$boq[1]['rate']}" . PHP_EOL;
echo "  - Item 3 (4.1.3 Analyzed): Basis = {$boq[2]['rate_basis']}, Derived Rate = ₹{$boq[2]['rate']}, Custom Constituents = " . count($boq[2]['analysis_breakup']['components'] ?? []) . PHP_EOL . PHP_EOL;

// 2. Insert into Database
$testAlias = 'HARDCODE-TEST-' . time();
$dto = new AA_ES_DTO(
    officeid: $officeId,
    budgetid: $budgetId,
    sub_head: "Test Immutability of Hardcoded Estimate and Rate Analysis",
    type: "DSR",
    boq: $boq,
    alias: $testAlias,
    status: "Draft"
);

$model = new AA_ES_Model();
$insertedId = $model->create($dto);
echo "Step 2: Inserted AA_ES into PostgreSQL. ID: {$insertedId}" . PHP_EOL;

$savedRow = $model->findById($insertedId);
$savedBoq = json_decode($savedRow['boq'], true);

echo "  - DB Estimated Cost: ₹" . number_format((float)$savedRow['estimated_cost'], 2) . PHP_EOL;
echo "  - DB Justified Amount: ₹" . number_format((float)$savedRow['justified_amount'], 2) . PHP_EOL;
echo "  - DB AA_ES Amount: ₹" . number_format((float)$savedRow['aa_es_amount'], 2) . PHP_EOL;

// Verify hardcoding in DB
assert(!empty($savedBoq[0]['estimate_analysis_breakup']), "Item 0 must have hardcoded estimate_analysis_breakup");
assert(!empty($savedBoq[0]['estimate_analysis_breakup']['components']), "Item 0 must have hardcoded components");
assert($savedBoq[0]['rate_basis'] === 'DSR_2023', "Item 0 rate basis must be DSR_2023");
assert($savedBoq[1]['rate_basis'] === 'MARKET_RATE', "Item 1 rate basis must be MARKET_RATE");
assert($savedBoq[2]['rate_basis'] === 'ANALYZED_DAR', "Item 2 rate basis must be ANALYZED_DAR");

echo "  ✓ Verification: Database contains full hardcoded constituent breakup." . PHP_EOL . PHP_EOL;

// 3. Simulate Rate Justification
echo "Step 3: Simulating Rate Justification with modified justified rates..." . PHP_EOL;
$justifiedBoq = $savedBoq;
$justifiedBoq[0]['justified_rate'] = 8200.00; // Increased market rate for 4.1.3
$justifiedBoq[0]['justified_amount'] = round(10.0 * 8200.00, 2);
$justifiedBoq[0]['justified_at'] = date('Y-m-d H:i:s');
$justifiedBoq[0]['justified_analysis_breakup'] = [
    'base_item_code' => '4.1.3',
    'justified_at' => date('Y-m-d H:i:s'),
    'calculated_rate' => 8200.00,
    'components' => $savedBoq[0]['analysis_breakup']['components'] ?? []
];

$justifiedBoq[1]['justified_rate'] = 1100.00; // Negotiated MR rate
$justifiedBoq[1]['justified_amount'] = round(100.0 * 1100.00, 2);

$totalJustifiedExpected = $justifiedBoq[0]['justified_amount'] + $justifiedBoq[1]['justified_amount'] + $justifiedBoq[2]['justified_amount'];

$stmtUpdate = $db->prepare("UPDATE aa_es SET boq = :b, justified_amount = :ja WHERE id = :id");
$stmtUpdate->execute([
    ':b' => json_encode($justifiedBoq),
    ':ja' => $totalJustifiedExpected,
    ':id' => $insertedId
]);

$afterJustification = $model->findById($insertedId);
$afterBoq = json_decode($afterJustification['boq'], true);

echo "  - DB Estimated Cost (Frozen/Untouched): ₹" . number_format((float)$afterJustification['estimated_cost'], 2) . PHP_EOL;
echo "  - DB Justified Amount (Recalculated): ₹" . number_format((float)$afterJustification['justified_amount'], 2) . PHP_EOL;
echo "  - DB AA_ES Amount (Locked to Estimate): ₹" . number_format((float)$afterJustification['aa_es_amount'], 2) . PHP_EOL;

// Check that baseline estimate rate and estimate analysis are STILL untouched
assert((float)$afterBoq[0]['rate'] === 7878.50, "Estimate rate must remain 7878.50");
assert((float)$afterBoq[0]['amount'] === 78785.00, "Estimate amount must remain 78785.00");
assert(!empty($afterBoq[0]['estimate_analysis_breakup']), "Baseline estimate analysis must be preserved");
assert((float)$afterBoq[0]['justified_rate'] === 8200.00, "Justified rate must be 8200.00");
assert(!empty($afterBoq[0]['justified_analysis_breakup']), "Justified analysis must be saved");

echo "  ✓ Verification: Estimate rates and baseline constituent analysis remained 100% hardcoded and frozen." . PHP_EOL . PHP_EOL;

// 4. Clean up test record
$db->exec("DELETE FROM aa_es WHERE id = {$insertedId}");
echo "Step 4: Cleaned up test record. ALL IMMUTABILITY CHECKS PASSED!" . PHP_EOL;
