<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Services;

require_once __DIR__ . '/../Models/DashboardModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Models\DashboardModel;

class DashboardService {
    protected $dashboardModel;

    public function __construct() {
        $this->dashboardModel = new DashboardModel();
    }

    public function getFleetHealthSummary(?int $officeId): array {
        return $this->dashboardModel->getFleetHealthSummary($officeId);
    }

    public function getStockBalancesByCategory(?int $officeId, string $category, ?string $asOfDate = null): array {
        $category = strtolower(trim($category));
        $date = !empty($asOfDate) ? $asOfDate : date('Y-m-d');

        if ($category === 'fuel') {
            return $this->dashboardModel->getFuelBalances($officeId, $date);
        } elseif ($category === 'chemical' || $category === 'fertilizer') {
            return $this->dashboardModel->getChemicalBalances($officeId, $date);
        } elseif ($category === 'spares' || $category === 'spare_parts') {
            return $this->dashboardModel->getSparePartsStock($officeId);
        } else {
            return $this->dashboardModel->getFuelBalances($officeId, $date);
        }
    }

    public function getDashboardInitData(?int $officeId, ?string $asOfDate = null): array {
        $date = !empty($asOfDate) ? $asOfDate : date('Y-m-d');

        $fleet = $this->getFleetHealthSummary($officeId);
        $fuelBalances = $this->dashboardModel->getFuelBalances($officeId, $date);

        // Extract key headline fuel balances (Petrol, Diesel, 2T Oil)
        $headlineFuels = [
            'petrol' => ['name' => 'Petrol', 'balance' => 0.0, 'unit' => 'Litre', 'inward' => 0.0, 'outward' => 0.0],
            'diesel' => ['name' => 'Diesel', 'balance' => 0.0, 'unit' => 'Litre', 'inward' => 0.0, 'outward' => 0.0],
            'two_t'  => ['name' => '2T Oil', 'balance' => 0.0, 'unit' => 'Litre', 'inward' => 0.0, 'outward' => 0.0]
        ];

        foreach ($fuelBalances as $fb) {
            $desc = strtolower(trim($fb['description']));
            $bal = (float)$fb['balance_qty'];
            $inw = (float)$fb['total_inward'];
            $out = (float)$fb['total_outward'];
            $u = $fb['ac_unit'] ?? 'Litre';

            if ($fb['id'] == 1 || str_contains($desc, 'petrol')) {
                $headlineFuels['petrol'] = ['name' => 'Petrol', 'balance' => $bal, 'unit' => $u, 'inward' => $inw, 'outward' => $out];
            } elseif ($fb['id'] == 2 || str_contains($desc, 'diesel')) {
                $headlineFuels['diesel'] = ['name' => 'Diesel', 'balance' => $bal, 'unit' => $u, 'inward' => $inw, 'outward' => $out];
            } elseif ($fb['id'] == 86 || str_contains($desc, '2t')) {
                $headlineFuels['two_t'] = ['name' => '2T Oil', 'balance' => $bal, 'unit' => $u, 'inward' => $inw, 'outward' => $out];
            }
        }

        return [
            'as_of_date'     => $date,
            'fleet'          => $fleet,
            'headline_fuels' => $headlineFuels,
            'fuel_balances'  => $fuelBalances
        ];
    }
}
