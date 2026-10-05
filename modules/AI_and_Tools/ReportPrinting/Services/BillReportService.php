<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Services;

use App\Core\Database;
use PDO;

class BillReportService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getBillList(?string $from, ?string $to): array {
        // CORRECTED: Join agencies via agreements
        $sql = "
            SELECT b.id, b.bill_date, b.office_bill_no, b.net_amount, b.status, ag.name as agency_name
            FROM bills b
            LEFT JOIN agreements a ON b.agreement_id = a.id
            LEFT JOIN agencies ag ON a.agency_id = ag.id
            WHERE 1=1
        ";
        $params = [];
        if ($from) { $sql .= " AND b.bill_date >= :f"; $params['f'] = $from; }
        if ($to) { $sql .= " AND b.bill_date <= :t"; $params['t'] = $to; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBillPrintData(int $billId): ?array {
        $sql = "
            SELECT
                b.*,
                a.agreement_no, a.tendered_amount, a.service_charge_percent,
                ag.name as agency_name, ag.pan_no, ag.gst_no, ag.bank_name, ag.account_no, ag.ifsc,
                bg.code as budget_code, bg.name_of_work, bg.provision,
                ae.sub_head,
                o.officecode as office_code
            FROM bills b
            LEFT JOIN agreements a ON b.agreement_id = a.id
            LEFT JOIN agencies ag ON a.agency_id = ag.id
            LEFT JOIN aa_es ae ON a.aa_es_id = ae.id
            LEFT JOIN budget bg ON ae.budgetid = bg.id
            LEFT JOIN office o ON a.officeid = o.Officeid
            WHERE b.id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $billId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return null;

        $data['bill_items'] = json_decode($data['bill_items'], true);
        $data['deductions'] = json_decode($data['deductions'], true);

        // Calculations
        $totalBasic = 0;
        foreach ($data['bill_items'] as $item) {
            $totalBasic += $item['qty'] * $item['rate'];
        }

        $shortageData = $this->calculateShortage($data);
        $shortageAmount = $shortageData['amount'];

        $totalA = $totalBasic - $shortageAmount;
        $profitPercent = (float)$data['service_charge_percent'];
        $profitAmount = $totalA * ($profitPercent / 100);
        $gross = $totalA + $profitAmount;

        $it = $gross * 0.02;
        $cgst = $gross * 0.01;
        $sgst = $gross * 0.01;
        $sd = $gross * 0.05;
        $totalRecoveries = $it + $cgst + $sgst + $sd;
        $net = $gross - $totalRecoveries;

        return [
            'data' => $data,
            'calcs' => [
                'total_basic' => $totalBasic,
                'shortage_amount' => $shortageAmount,
                'shortage_details' => $shortageData['details'],
                'total_a' => $totalA,
                'profit_amount' => $profitAmount,
                'gross_amount' => $gross,
                'total_recoveries' => $totalRecoveries,
                'net_amount' => $net,
                'recoveries' => [
                    'Income Tax @ 2%' => $it,
                    'CGST @ 1%' => $cgst,
                    'SGST @ 1%' => $sgst,
                    'Security Deposit @ 5%' => $sd
                ]
            ],
            'netWords' => $this->numberToWords($net)
        ];
    }

    private function calculateShortage(array $data): array {
        if (empty($data['period_from']) || empty($data['period_to'])) {
            return ['amount' => 0, 'details' => []];
        }

        $sql = "
            SELECT e.designation, COUNT(*) as absent_days
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            WHERE e.agreement_id = :agreement_id AND ar.status = 'A' AND ar.attendance_date BETWEEN :from AND :to
            GROUP BY e.designation
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['agreement_id' => $data['agreement_id'], 'from' => $data['period_from'], 'to' => $data['period_to']]);
        $shortages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalDeduction = 0;
        $details = [];

        foreach ($shortages as $row) {
            $rate = 0;
            foreach ($data['bill_items'] as $item) {
                if ($item['description'] === $row['designation']) {
                    $rate = $item['rate'];
                    break;
                }
            }
            $dailyRate = $rate / 30;
            $amount = $dailyRate * $row['absent_days'];
            $totalDeduction += $amount;

            $details[] = [
                'designation' => $row['designation'],
                'absent_days' => $row['absent_days'],
                'daily_rate' => $dailyRate,
                'amount' => $amount
            ];
        }

        return ['amount' => $totalDeduction, 'details' => $details];
    }

    private function numberToWords(float $amount): string {
        $amount = round($amount);
        $no = floor($amount);
        $digits_1 = strlen((string)$no);
        $i = 0;
        $str = array();
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
