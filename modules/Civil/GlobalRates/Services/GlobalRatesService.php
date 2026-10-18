<?php
declare(strict_types=1);

namespace App\Modules\Civil\GlobalRates\Services;

require_once __DIR__ . '/../Models/GlobalRatesModel.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

use App\Modules\Civil\GlobalRates\Models\GlobalRatesModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Exception;

class GlobalRatesService
{
    private GlobalRatesModel $model;

    public function __construct()
    {
        $this->model = new GlobalRatesModel();
    }

    public function getRatesList(?string $query = null, ?string $category = null, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $items = $this->model->searchRates($query, $category, $perPage, $offset);
        $total = $this->model->countRates($query, $category);
        $totalPages = (int)ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'categories' => $this->model->getCategories(),
            'stats' => $this->model->getSummaryStats()
        ];
    }

    public function updateRate(string $code, float $marketRate): bool
    {
        if ($marketRate < 0) {
            throw new Exception("Market rate cannot be negative.");
        }
        return $this->model->updateMarketRate($code, $marketRate);
    }

    public function importRatesFromExcel(string $filePath): int
    {
        if (!file_exists($filePath)) {
            throw new Exception("Uploaded file not found.");
        }

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        // Identify column mappings by inspecting header row
        $codeCol = 'A';
        $rateCol = 'E'; // Standard basic rate or market rate column

        // Inspect header row
        for ($col = 'A'; $col <= 'G'; $col++) {
            $header = strtolower(trim((string)$sheet->getCell("{$col}1")->getValue()));
            if (str_contains($header, 'code') || str_contains($header, 'item')) {
                $codeCol = $col;
            } elseif (str_contains($header, 'market') || str_contains($header, 'rate')) {
                $rateCol = $col;
            }
        }

        $updatedCount = 0;
        for ($r = 2; $r <= $highestRow; $r++) {
            $code = trim((string)$sheet->getCell("{$codeCol}{$r}")->getValue());
            $rateVal = $sheet->getCell("{$rateCol}{$r}")->getValue();

            if ($code !== '' && is_numeric($rateVal)) {
                $rate = (float)$rateVal;
                if ($this->model->updateMarketRate($code, $rate)) {
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }
}
