<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$startTime = microtime(true);
echo "=== Starting Civil DSR & DAR Seeding (Pure PHP) ===\n";

try {
    $db = Database::getInstance()->getConnection();
    $reader = new Xlsx();
    $reader->setReadDataOnly(true);

    // -------------------------------------------------------------
    // 1. Seed civil_global_rates from CPWD_DAR_2023_Basic_Rates.xlsx
    // -------------------------------------------------------------
    $basicRatesFile = __DIR__ . '/../DSR/dsr_subheads_json/CPWD_DAR_2023_Basic_Rates.xlsx';
    if (file_exists($basicRatesFile)) {
        echo "\n[1/3] Seeding civil_global_rates from Excel...\n";
        $spreadsheet = $reader->load($basicRatesFile);
        $sheet = $spreadsheet->getSheetByName('All_Basic_Rates') ?? $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $stmtRate = $db->prepare("
            INSERT INTO civil_global_rates (resource_code, category, description, unit, dsr_base_rate, current_market_rate, notes, pdf_page)
            VALUES (:code, :cat, :desc, :unit, :base_rate, :market_rate, :notes, :page)
            ON CONFLICT (resource_code) DO UPDATE SET
                category = EXCLUDED.category,
                description = EXCLUDED.description,
                unit = EXCLUDED.unit,
                dsr_base_rate = EXCLUDED.dsr_base_rate,
                current_market_rate = CASE WHEN civil_global_rates.current_market_rate = 0 THEN EXCLUDED.current_market_rate ELSE civil_global_rates.current_market_rate END,
                notes = EXCLUDED.notes,
                pdf_page = EXCLUDED.pdf_page
        ");

        $db->beginTransaction();
        $rateCount = 0;
        for ($r = 2; $r <= $highestRow; $r++) {
            $code = trim((string)$sheet->getCell("A{$r}")->getValue());
            if ($code === '') continue;

            $cat = trim((string)$sheet->getCell("B{$r}")->getValue()) ?: 'Materials';
            $desc = trim((string)$sheet->getCell("C{$r}")->getValue()) ?: $code;
            $unit = trim((string)$sheet->getCell("D{$r}")->getValue()) ?: 'each';
            $baseRate = (float)($sheet->getCell("E{$r}")->getValue() ?: 0.0);
            $notes = trim((string)$sheet->getCell("F{$r}")->getValue()) ?: null;
            $pageVal = $sheet->getCell("G{$r}")->getValue();
            $page = is_numeric($pageVal) ? (int)$pageVal : null;

            $stmtRate->execute([
                ':code' => $code,
                ':cat' => $cat,
                ':desc' => $desc,
                ':unit' => $unit,
                ':base_rate' => $baseRate,
                ':market_rate' => $baseRate,
                ':notes' => $notes,
                ':page' => $page
            ]);
            $rateCount++;
        }
        $db->commit();
        echo "  -> Seeded {$rateCount} basic resource rates into civil_global_rates.\n";
    }

    // -------------------------------------------------------------
    // 2. Seed dsr_2023 from CPWD_DSR_2023_Schedule_of_Rates.xlsx
    // -------------------------------------------------------------
    $dsrFile = __DIR__ . '/../DSR/CPWD_DSR_2023_Schedule_of_Rates.xlsx';
    if (file_exists($dsrFile)) {
        echo "\n[2/3] Seeding dsr_2023 from Schedule of Rates Excel...\n";
        $spreadsheetDsr = $reader->load($dsrFile);
        $dsrSheet = $spreadsheetDsr->getSheetByName('All_DSR_Items') ?? $spreadsheetDsr->getSheet(0);
        $highestRowDsr = $dsrSheet->getHighestRow();

        $stmtDsr = $db->prepare("
            INSERT INTO dsr_2023 (item_code, sub_head_no, sub_head_name, description, unit, rate, volume, pdf_page)
            VALUES (:item_code, :sub_head_no, :sub_head_name, :description, :unit, :rate, :volume, :pdf_page)
            ON CONFLICT (item_code) DO UPDATE SET
                sub_head_no = EXCLUDED.sub_head_no,
                sub_head_name = EXCLUDED.sub_head_name,
                description = EXCLUDED.description,
                unit = EXCLUDED.unit,
                rate = EXCLUDED.rate,
                volume = EXCLUDED.volume,
                pdf_page = EXCLUDED.pdf_page
        ");

        $db->beginTransaction();
        $dsrCount = 0;
        for ($r = 2; $r <= $highestRowDsr; $r++) {
            $itemCode = trim((string)$dsrSheet->getCell("C{$r}")->getValue());
            if ($itemCode === '') continue;

            $subHeadNo = (int)($dsrSheet->getCell("A{$r}")->getValue() ?: 1);
            $subHeadName = trim((string)$dsrSheet->getCell("B{$r}")->getValue()) ?: 'General';
            $desc = trim((string)$dsrSheet->getCell("D{$r}")->getValue()) ?: $itemCode;
            $unit = trim((string)$dsrSheet->getCell("E{$r}")->getValue()) ?: 'each';
            $rate = (float)($dsrSheet->getCell("F{$r}")->getValue() ?: 0.0);
            $volume = trim((string)$dsrSheet->getCell("G{$r}")->getValue()) ?: 'Vol 1';
            $pageVal = $dsrSheet->getCell("H{$r}")->getValue();
            $page = is_numeric($pageVal) ? (int)$pageVal : null;

            $stmtDsr->execute([
                ':item_code' => $itemCode,
                ':sub_head_no' => $subHeadNo,
                ':sub_head_name' => $subHeadName,
                ':description' => $desc,
                ':unit' => $unit,
                ':rate' => $rate,
                ':volume' => $volume,
                ':pdf_page' => $page
            ]);
            $dsrCount++;
        }
        $db->commit();
        echo "  -> Seeded {$dsrCount} schedule items into dsr_2023.\n";

        // Part C Cement Consumption
        $cementSheet = $spreadsheetDsr->getSheetByName('PartC_Cement_Consumption');
        if ($cementSheet) {
            echo "  -> Processing Part C Cement Consumption...\n";
            $stmtCement = $db->prepare("UPDATE dsr_2023 SET cement_consumption = :val WHERE item_code = :code");
            $db->beginTransaction();
            $cRows = $cementSheet->getHighestRow();
            $cementCount = 0;
            for ($r = 2; $r <= $cRows; $r++) {
                $cCode = trim((string)$cementSheet->getCell("A{$r}")->getValue());
                $cVal = $cementSheet->getCell("D{$r}")->getValue();
                if ($cCode !== '' && is_numeric($cVal)) {
                    $stmtCement->execute([':val' => (float)$cVal, ':code' => $cCode]);
                    $cementCount++;
                }
            }
            $db->commit();
            echo "  -> Updated {$cementCount} items with cement consumption constants.\n";
        }

        // Part D Bitumen Consumption
        $bitumenSheet = $spreadsheetDsr->getSheetByName('PartD_Bitumen_Consumption');
        if ($bitumenSheet) {
            echo "  -> Processing Part D Bitumen Consumption...\n";
            $stmtBitumen = $db->prepare("UPDATE dsr_2023 SET bitumen_consumption = :val WHERE item_code = :code");
            $db->beginTransaction();
            $bRows = $bitumenSheet->getHighestRow();
            $bitumenCount = 0;
            for ($r = 2; $r <= $bRows; $r++) {
                $bCode = trim((string)$bitumenSheet->getCell("A{$r}")->getValue());
                $bVal = $bitumenSheet->getCell("D{$r}")->getValue();
                if ($bCode !== '' && is_numeric($bVal)) {
                    $stmtBitumen->execute([':val' => (float)$bVal, ':code' => $bCode]);
                    $bitumenCount++;
                }
            }
            $db->commit();
            echo "  -> Updated {$bitumenCount} items with bitumen consumption constants.\n";
        }
    }

    // -------------------------------------------------------------
    // 3. Seed dar_item_breakups from 26 JSON files
    // -------------------------------------------------------------
    echo "\n[3/3] Seeding dar_item_breakups from 26 DAR Subhead JSON files...\n";
    $jsonFiles = glob(__DIR__ . '/../DSR/dsr_subheads_json/sh_*.json');
    sort($jsonFiles);

    $db->exec("TRUNCATE TABLE dar_item_breakups RESTART IDENTITY");

    $stmtBreakup = $db->prepare("
        INSERT INTO dar_item_breakups (item_code, resource_code, category, description, unit, quantity, standard_rate, standard_amount, cost_basis_qty, cost_basis_unit)
        VALUES (:item_code, :resource_code, :category, :description, :unit, :quantity, :standard_rate, :standard_amount, :basis_qty, :basis_unit)
    ");

    $db->beginTransaction();
    $totalBreakups = 0;
    $itemsWithBreakup = 0;

    foreach ($jsonFiles as $jf) {
        $raw = file_get_contents($jf);
        $data = json_decode($raw, true);
        if (!$data || empty($data['items'])) continue;

        foreach ($data['items'] as $item) {
            $itemCode = trim((string)($item['item_code'] ?? ''));
            $breakups = $item['breakup'] ?? [];
            if ($itemCode === '' || empty($breakups)) continue;

            $itemsWithBreakup++;
            $basisQty = (float)($item['cost_basis']['quantity'] ?? 1.0);
            $basisUnit = (string)($item['cost_basis']['unit'] ?? 'cum');

            foreach ($breakups as $b) {
                $resCode = trim((string)($b['resource_code'] ?? ''));
                if ($resCode === '') continue;

                $cat = trim((string)($b['category'] ?? 'MATERIAL'));
                $desc = trim((string)($b['description'] ?? ''));
                $unit = trim((string)($b['unit'] ?? 'each'));
                $qty = (float)($b['quantity'] ?? 0.0);
                $rate = (float)($b['rate'] ?? 0.0);
                $amount = (float)($b['amount'] ?? ($qty * $rate));

                $stmtBreakup->execute([
                    ':item_code' => $itemCode,
                    ':resource_code' => $resCode,
                    ':category' => $cat,
                    ':description' => $desc,
                    ':unit' => $unit,
                    ':quantity' => $qty,
                    ':standard_rate' => $rate,
                    ':standard_amount' => $amount,
                    ':basis_qty' => $basisQty,
                    ':basis_unit' => $basisUnit
                ]);
                $totalBreakups++;
            }
        }
    }
    $db->commit();
    echo "  -> Seeded {$totalBreakups} breakup rows across {$itemsWithBreakup} analyzed items.\n";

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "\n=== All Civil DSR & DAR Data Successfully Seeded in {$elapsed}s ===\n";

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error during seeding: " . $e->getMessage() . "\n";
    exit(1);
}
