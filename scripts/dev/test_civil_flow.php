<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../modules/Admin/AA_ES/DTO/AA_ES_DTO.php';
require_once __DIR__ . '/../../modules/Admin/AA_ES/Models/AA_ES_Model.php';

use App\Core\Database;
use App\Modules\Admin\AA_ES\DTO\AA_ES_DTO;
use App\Modules\Admin\AA_ES\Models\AA_ES_Model;

echo "=== TESTING DSR SEARCH AND BOQ FLOW ===" . PHP_EOL . PHP_EOL;

$db = Database::getInstance()->getConnection();

// 1. Test Searches
$testQueries = ['cc', 'earth work', 'brick work', 'cement concrete', '4.1.3'];

foreach ($testQueries as $q) {
    // Multi-term matching for even better search experience:
    // If user searches "earth work", match items containing "earth" AND "work" OR code matching
    $terms = preg_split('/\s+/', trim($q));
    $conditions = [];
    $params = [];
    
    // Direct code or phrase match
    $conditions[] = "(item_code ILIKE :phrase OR description ILIKE :phrase)";
    $params[':phrase'] = "%{$q}%";
    
    // Also if multiple words, try AND condition across words in description
    if (count($terms) > 1) {
        $wordConditions = [];
        foreach ($terms as $idx => $term) {
            $key = ":word_{$idx}";
            $wordConditions[] = "description ILIKE {$key}";
            $params[$key] = "%{$term}%";
        }
        $conditions[] = "(" . implode(" AND ", $wordConditions) . ")";
    }

    $sql = "SELECT item_code, sub_head_no, sub_head_name, description, unit, rate 
            FROM dsr_2023 
            WHERE " . implode(" OR ", $conditions) . " 
            ORDER BY sub_head_no ASC, item_code ASC 
            LIMIT 3";
            
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Search for [{$q}]: Found " . count($results) . " items:" . PHP_EOL;
    foreach ($results as $r) {
        $desc = mb_strimwidth($r['description'], 0, 70, '...');
        echo "  - Code: {$r['item_code']} | Unit: {$r['unit']} | Rate: ₹{$r['rate']} | {$desc}" . PHP_EOL;
    }
    echo PHP_EOL;
}

// 2. Select DSR item, add a Market Rate (MR) item, and an Analyzed DAR item
$boq = [
    // 1. Standard DSR Item
    [
        'item_code' => '4.1.3',
        'description' => 'Providing and laying in position cement concrete 1:2:4',
        'qty' => 10.0,
        'unit' => 'cum',
        'rate' => 7878.50,
        'amount' => 78785.00,
        'remarks' => 'Standard DSR 2023 item'
    ],
    // 2. Market Rate Item (MR)
    [
        'item_code' => 'MR-1',
        'description' => 'Supply and installation of specialized acoustic thermal insulation panels',
        'qty' => 50.0,
        'unit' => 'sqm',
        'rate' => 1250.00,
        'amount' => 62500.00,
        'is_market_item' => 1,
        'remarks' => 'Non-Schedule / Market Rate item'
    ],
    // 3. Analyzed Item based on DSR
    [
        'item_code' => '4.1.3 (Analyzed)',
        'description' => 'Providing and laying cement concrete 1:2:4 with integral waterproofing compound',
        'qty' => 5.0,
        'unit' => 'cum',
        'rate' => 8450.00, // Calculated via custom DAR
        'amount' => 42250.00,
        'analysis_breakup' => [
            'base_item_code' => '4.1.3',
            'cost_basis_qty' => 1.0,
            'cost_basis_unit' => 'cum',
            'dar_factor' => 1.4085,
            'calculated_rate' => 8450.00
        ]
    ]
];

$expectedEstimate = 78785.00 + 62500.00 + 42250.00; // 183,535.00

echo "=== PREPARED HYBRID CIVIL BOQ (DSR + MR + ANALYZED) ===" . PHP_EOL;
echo json_encode($boq, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL . PHP_EOL;
echo "Expected Total Estimate: ₹" . number_format($expectedEstimate, 2) . PHP_EOL . PHP_EOL;

// 3. Test insertion into PostgreSQL aa_es table to verify trigger and schema
try {
    // Find an office and budget
    $sample = $db->query("SELECT officeid, budgetid FROM aa_es LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $officeId = (int)($sample['officeid'] ?? 1);
    $budgetId = (int)($sample['budgetid'] ?? 1);

    // Set Postgres session context so RLS permits full access
    $db->prepare("SELECT set_config('app.current_office_id', :officeId, false)")->execute([':officeId' => (string)$officeId]);
    $db->prepare("SELECT set_config('app.current_user_role', 'superuser', false)")->execute();
    $db->prepare("SELECT set_config('app.current_user_id', '1', false)")->execute();

    $dto = new AA_ES_DTO(
        officeid: $officeId,
        budgetid: $budgetId,
        sub_head: "Civil Works: Renovation with Hybrid DSR, MR, and Analyzed Items",
        type: "DSR",
        boq: $boq,
        alias: "DSR-TEST-" . time(),
        status: "Draft"
    );

    // Clean up previous test runs if any
    $db->exec("DELETE FROM aa_es WHERE alias LIKE 'DSR-TEST-%'");

    $model = new AA_ES_Model();
    $insertedId = $model->create($dto);
    echo "Inserted AA_ES ID: {$insertedId}" . PHP_EOL;

    $created = $model->findById($insertedId);
    if ($created) {
        echo "Fetched Record from DB via findById():" . PHP_EOL;
        echo "  - ID: " . $created['id'] . PHP_EOL;
        echo "  - Alias: " . $created['alias'] . PHP_EOL;
        echo "  - Type: " . $created['type'] . PHP_EOL;
        echo "  - Estimated Cost: ₹" . number_format((float)$created['estimated_cost'], 2) . PHP_EOL;
        echo "  - Justified Amount: ₹" . number_format((float)$created['justified_amount'], 2) . PHP_EOL;
        echo "  - AA_ES Amount: ₹" . number_format((float)$created['aa_es_amount'], 2) . PHP_EOL;

        if (abs((float)$created['aa_es_amount'] - $expectedEstimate) < 0.05) {
            echo ">>> SUCCESS: Estimate amount calculated accurately: ₹" . number_format((float)$created['aa_es_amount'], 2) . " <<<" . PHP_EOL;
        } else {
            echo "WARNING: Amount discrepancy. Expected: {$expectedEstimate}, DB had: {$created['aa_es_amount']}" . PHP_EOL;
        }

        // 4. Test Justification update (simulating user Rate Justification screen)
        echo PHP_EOL . "=== TESTING RATE JUSTIFICATION UPDATE ===" . PHP_EOL;
        $justifiedBoq = json_decode($created['boq'], true);
        // User adjusts:
        // Item 1 (4.1.3): market rate justified to 8100.00
        $justifiedBoq[0]['justified_rate'] = 8100.00;
        $justifiedBoq[0]['justified_amount'] = 81000.00;
        // Item 2 (MR-1): negotiated quotation justified to 1180.00
        $justifiedBoq[1]['justified_rate'] = 1180.00;
        $justifiedBoq[1]['justified_amount'] = 59000.00;
        // Item 3 (Analyzed): unchanged 8450.00
        $justifiedBoq[2]['justified_rate'] = 8450.00;
        $justifiedBoq[2]['justified_amount'] = 42250.00;

        $expectedJustifiedTotal = 81000.00 + 59000.00 + 42250.00; // 182,250.00

        $stmtUp = $db->prepare("UPDATE aa_es SET boq = :boq, justified_amount = :ja WHERE id = :id");
        $stmtUp->execute([
            ':boq' => json_encode($justifiedBoq),
            ':ja' => $expectedJustifiedTotal,
            ':id' => $insertedId
        ]);

        $updated = $model->findById($insertedId);
        echo "Updated Record after Justification:" . PHP_EOL;
        echo "  - Estimated Cost (Preserved): ₹" . number_format((float)$updated['estimated_cost'], 2) . PHP_EOL;
        echo "  - AA_ES Amount (Preserved):   ₹" . number_format((float)$updated['aa_es_amount'], 2) . PHP_EOL;
        echo "  - Justified Amount (New):     ₹" . number_format((float)$updated['justified_amount'], 2) . PHP_EOL;

        if (abs((float)$updated['justified_amount'] - $expectedJustifiedTotal) < 0.05) {
            echo ">>> SUCCESS: Justified amount updated and preserved independently from Estimate: ₹" . number_format((float)$updated['justified_amount'], 2) . " <<<" . PHP_EOL;
        } else {
            echo "WARNING: Justified amount discrepancy. Expected: {$expectedJustifiedTotal}, DB had: {$updated['justified_amount']}" . PHP_EOL;
        }
    } else {
        echo "Record {$insertedId} was not found!" . PHP_EOL;
    }

    // Clean up test record
    $db->prepare("DELETE FROM aa_es WHERE id = :id")->execute([':id' => $insertedId]);
    echo PHP_EOL . "Cleaned up test record {$insertedId} successfully." . PHP_EOL;

} catch (\Throwable $e) {
    echo "ERROR during DSR BoQ test: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
