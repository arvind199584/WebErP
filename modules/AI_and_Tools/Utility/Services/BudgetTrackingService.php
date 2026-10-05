<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\Utility\Services;

use App\Core\Database;
use PDO;

class BudgetTrackingService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getBudgetTable(int $officeId, string $fyStart, string $fyEnd): array {
        $fy = date('Y', strtotime($fyStart)) . '-' . date('y', strtotime($fyEnd));
        $sql = "SELECT id, code, name_of_work, provision FROM budget WHERE officeid = :oid AND fy = :fy";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['oid' => $officeId, 'fy' => $fy]);
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $report = [];
        foreach ($budgets as $b) {
            $bid = (int)$b['id'];
            $provisionInRupees = (float)$b['provision'] * 100000;

            $sqlAAES = "SELECT COALESCE(SUM(aa_es_amount), 0) FROM aa_es WHERE budgetid = :bid AND created_at BETWEEN :start AND :end";
            $stmtAAES = $this->db->prepare($sqlAAES);
            $stmtAAES->execute(['bid' => $bid, 'start' => $fyStart, 'end' => $fyEnd]);
            $totalAAES = (float)$stmtAAES->fetchColumn();

            $sqlExp = "SELECT COALESCE(SUM(gross_amount), 0) FROM bills WHERE budget_id = :bid AND bill_date BETWEEN :start AND :end";
            $stmtExp = $this->db->prepare($sqlExp);
            $stmtExp->execute(['bid' => $bid, 'start' => $fyStart, 'end' => $fyEnd]);
            $billExp = (float)$stmtExp->fetchColumn();

            $sqlHR = "SELECT COALESCE(SUM(gross_amount), 0) FROM hand_receipts WHERE budgetid = :bid AND budget_implication = TRUE AND created_at BETWEEN :start AND :end";
            $stmtHR = $this->db->prepare($sqlHR);
            $stmtHR->execute(['bid' => $bid, 'start' => $fyStart, 'end' => $fyEnd]);
            $hrExp = (float)$stmtHR->fetchColumn();
            $totalExpBooked = $billExp + $hrExp;

            $sqlPaidBill = "SELECT COALESCE(SUM(gross_amount), 0) FROM bills WHERE budget_id = :bid AND status = 'Paid' AND bill_date BETWEEN :start AND :end";
            $stmtPaidBill = $this->db->prepare($sqlPaidBill);
            $stmtPaidBill->execute(['bid' => $bid, 'start' => $fyStart, 'end' => $fyEnd]);
            $totalPaid = (float)$stmtPaidBill->fetchColumn() + $hrExp;

            $report[] = [
                'id' => $bid,
                'code' => $b['code'],
                'name' => $b['name_of_work'],
                'provision_lakhs' => (float)$b['provision'],
                'provision' => $provisionInRupees,
                'aa_es' => $totalAAES,
                'exp_booked' => $totalExpBooked,
                'paid' => $totalPaid,
                'balance' => $provisionInRupees - $totalExpBooked
            ];
        }
        return $report;
    }

    public function getBudgetWiseTrend(int $officeId, string $fyStart, string $fyEnd): array {
        $budgets = $this->getBudgetTable($officeId, $fyStart, $fyEnd);

        $sqlAct = "SELECT budget_id, to_char(bill_date, 'YYYY-MM') as month, SUM(gross_amount) as total FROM bills WHERE officeid = :oid AND bill_date BETWEEN :start AND :end GROUP BY budget_id, month";
        $stmtAct = $this->db->prepare($sqlAct);
        $stmtAct->execute(['oid' => $officeId, 'start' => $fyStart, 'end' => $fyEnd]);
        $actualData = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        // CORRECTED JOIN: Get budget_id from aa_es table
        $sqlAg = "
            SELECT a.id, ae.budgetid as budget_id, a.service_charge_percent, a.scope as effective_boq, lower(a.period) as start_date, upper(a.period) as end_date
            FROM agreements a
            JOIN aa_es ae ON a.aa_es_id = ae.id
            WHERE a.officeid = :oid AND a.status = 'Active'
        ";
        $stmtAg = $this->db->prepare($sqlAg);
        $stmtAg->execute(['oid' => $officeId]);
        $agreements = $stmtAg->fetchAll(PDO::FETCH_ASSOC);

        $trend = [];
        $todayMonth = date('Y-m');

        foreach ($budgets as $b) {
            $bid = $b['id'];
            $trend[$b['code']] = ['provision' => $b['provision'], 'monthly_data' => []];
            $current = new \DateTime($fyStart);
            $end = new \DateTime($fyEnd);
            $cumulative = 0;

            while ($current <= $end) {
                $m = $current->format('Y-m');
                $isFuture = ($m > $todayMonth);
                $isMarch = ($current->format('m') === '03');
                $monthlyAmount = 0;

                if (!$isFuture) {
                    foreach ($actualData as $row) { if ($row['budget_id'] == $bid && $row['month'] == $m) $monthlyAmount = (float)$row['total']; }
                } elseif (!$isMarch) {
                    foreach ($agreements as $ag) {
                        if ($ag['budget_id'] == $bid) {
                            $agStart = new \DateTime($ag['start_date']);
                            $agEnd = new \DateTime($ag['end_date']);
                            if ($current >= $agStart && $current <= $agEnd) $monthlyAmount += $this->calculateMonthlyLiability($ag, $m . '-01');
                        }
                    }
                }
                $cumulative += $monthlyAmount;
                $percentage = ($b['provision'] > 0) ? ($cumulative / $b['provision']) * 100 : 0;
                $trend[$b['code']]['monthly_data'][$m] = ['amount' => $monthlyAmount, 'percentage' => round($percentage, 2), 'type' => $isFuture ? 'projection' : 'actual'];
                $current->modify('+1 month');
            }
        }
        return $trend;
    }

    private function calculateMonthlyLiability(array $ag, string $date): float {
        $boq = json_decode($ag['effective_boq'], true);
        if (!$boq) return 0.0;
        $total = 0.0;
        $latestScope = end($boq);
        if (!isset($latestScope['Items'])) return 0.0;
        foreach ($latestScope['Items'] as $desig => $qty) {
            $sqlRate = "SELECT get_effective_rate(:date, id) FROM wage_items WHERE item_name = :name";
            $stmt = $this->db->prepare($sqlRate);
            $stmt->execute(['date' => $date, 'name' => $desig]);
            $rate = (float)$stmt->fetchColumn();
            $total += ($qty * $rate);
        }
        return $total * (1 + (float)$ag['service_charge_percent'] / 100);
    }

    public function getMonthlyTrendWithProjections(int $officeId, string $fyStart, string $fyEnd): array {
        $budgetTrend = $this->getBudgetWiseTrend($officeId, $fyStart, $fyEnd);
        $summary = [];
        foreach ($budgetTrend as $code => $data) {
            foreach ($data['monthly_data'] as $month => $mdata) {
                if (!isset($summary[$month])) $summary[$month] = ['amount' => 0, 'type' => $mdata['type']];
                $summary[$month]['amount'] += $mdata['amount'];
            }
        }
        return $summary;
    }
}
