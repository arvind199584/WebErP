<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Services;

use App\Core\Database;
use PDO;

class AAESReportService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllAAES(): array {
        $sql = "SELECT ae.id, ae.sub_head, b.name_of_work, b.code as budget_code FROM ae_es ae JOIN budget b ON ae.budgetid = b.id ORDER BY b.code, ae.sub_head";
        // Corrected table name in query
        $sql = "SELECT ae.id, ae.sub_head, b.name_of_work, b.code as budget_code FROM aa_es ae JOIN budget b ON ae.budgetid = b.id ORDER BY b.code, ae.sub_head";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAAESDetails(int $id): ?array {
        $sql = "SELECT ae.*, b.name_of_work, b.code as budget_code, b.provision, o.officecode FROM aa_es ae JOIN budget b ON ae.budgetid = b.id JOIN office o ON ae.officeid = o.Officeid WHERE ae.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($details) {
            $details['boq'] = json_decode($details['boq'], true);
            $details['calculations'] = $this->performCalculations($details);
            $details['aa_es_amount_words'] = $this->numberToWords((float)$details['aa_es_amount']);
            $details['wage_orders'] = $this->getRelevantWageOrders($details['boq']);
        }
        return $details ?: null;
    }

    private function getRelevantWageOrders(array $boq): array {
        $designations = [];
        if (isset($boq['Items'])) { foreach ($boq['Items'] as $item) { $designations[] = $item['description']; } }
        if (empty($designations)) return [];
        $placeholders = implode(',', array_fill(0, count($designations), '?'));
        $sql = "SELECT DISTINCT authority FROM wage_items WHERE item_name IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($designations);
        $authorities = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $orders = [];
        foreach ($authorities as $auth) {
            $sqlOrder = "SELECT authority, letter_no, letter_date FROM wage_orders WHERE authority = :auth ORDER BY valid_from DESC LIMIT 1";
            $stmtOrder = $this->db->prepare($sqlOrder);
            $stmtOrder->execute(['auth' => $auth]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);
            if ($order) $orders[] = $order;
        }
        return $orders;
    }

    private function performCalculations(array $details): array {
        $boq = $details['boq'];
        $type = $details['type'];
        $office = $details['officecode'] ?? 'Office';
        $calcs = ['items' => [], 'summary' => []];

        if ($type === 'Manpower') {
            $period = (int)($boq['Period'] ?? 0);
            $is7Day = !empty($boq['is_7_day']);
            $totalEst = 0;
            $esicTotal = 0; $epfTotal = 0; $bonusTotal = 0;

            foreach ($boq['Items'] as $item) {
                $qty = (float)$item['qty'];
                $rate = (float)$item['rate'];
                $effectiveQty = $is7Day ? ($qty * 7.0 / 6.0) : $qty;
                $totalQty = $effectiveQty * $period;
                $amount = $totalQty * $rate;
                $totalEst += $amount;

                $formula = $is7Day ? "({$qty} Nos x {$period} Months x 7/6 = " . round($totalQty, 2) . ")" : "({$qty} Nos x {$period} Months = " . round($totalQty, 2) . ")";
                $description = "Deployment of {$qty} Nos " . htmlspecialchars($item['description']) . " at {$office}, DDA " . $formula;

                $calcs['items'][] = ['description' => $description, 'qty' => round($totalQty, 2), 'rate' => $rate, 'amount' => $amount];

                if ($boq['ESIC'] ?? false) { $esicTotal += ($effectiveQty * $period * 0.0325 * min($rate, 21000)); }
                if ($boq['EPF'] ?? false) { $epfTotal += ($effectiveQty * $period * 0.13 * min($rate, 15000)); }
                if ($boq['Bonus'] ?? false) { $bonusTotal += ($effectiveQty * $period * 0.0833 * min($rate, 21000)); }
            }

            // APPLY SELECTIVE ROUNDING
            $roundedEst = round($totalEst);
            $roundedJustified = round($roundedEst * 1.15);
            $gstAmount = $roundedJustified * 0.18; // No rounding
            $grandTotal = round($roundedJustified + $gstAmount + $esicTotal + $epfTotal + $bonusTotal);

            $calcs['summary'] = [
                'Total Estimated Cost' => $roundedEst,
                'Add 15% CP & OH' => $roundedJustified - $roundedEst,
                'Total (Justified Amount)' => $roundedJustified,
                'Add 18% GST' => $gstAmount,
                'ESIC Component' => $esicTotal,
                'EPF Component' => $epfTotal,
                'Bonus Component' => $bonusTotal,
                'Grand Total (AA & ES)' => $grandTotal
            ];
        }
        return $calcs;
    }

    private function numberToWords(float $amount): string {
        $amount = round($amount);
        $no = floor($amount);
        $digits_1 = strlen((string)$no);
        $i = 0; $str = array();
        $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
        $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
        while ($i < $digits_1) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
            } else $str[] = null;
        }
        $str = array_reverse($str);
        return implode('', $str);
    }
}
