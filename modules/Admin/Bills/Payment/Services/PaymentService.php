<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\Payment\Services;

use App\Core\Database;
use App\Modules\Payment\DTO\PaymentDTO;
use App\Modules\Admin\Agreement\Services\AgreementService;
use PDO;
use Exception;

require_once __DIR__ . '/../../../Agreement/Services/AgreementService.php';

class PaymentService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllBills(?string $searchTerm = null): array {
        $sql = "SELECT b.*, ae.alias as agreement_alias FROM bills b LEFT JOIN agreements a ON b.agreement_id = a.id LEFT JOIN aa_es ae ON a.aa_es_id = ae.id";
        $params = [];
        if ($searchTerm) {
            $sql .= " WHERE UPPER(b.office_bill_no) LIKE UPPER(:term) OR UPPER(b.agency_bill_no) LIKE UPPER(:term) OR UPPER(ae.alias) LIKE UPPER(:term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }
        $sql .= " ORDER BY b.bill_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBillById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM bills WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPreviousExpenditure(int $agreementId, string $beforeDate): array {
        $sql = "SELECT COALESCE(SUM(gross_amount), 0) as prev_gross, COALESCE(SUM(total_deduction), 0) as prev_deductions, COALESCE(SUM(net_amount), 0) as prev_net FROM bills WHERE agreement_id = :aid AND status = 'Paid' AND bill_date < :dt";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['aid' => $agreementId, 'dt' => $beforeDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createBill(PaymentDTO $dto): int {
        $sql = "INSERT INTO bills (officeid, agency_bill_no, bill_date, office_bill_no, bill_type, agreement_id, work_order_id, supply_order_id, budget_id, bill_items, withheld, gross_amount, total_deduction, net_amount, deductions, period_from, period_to) VALUES (:officeid, :agency_bill_no, :bill_date, :office_bill_no, :bill_type, :agreement_id, :work_order_id, :supply_order_id, :budget_id, :bill_items, :withheld, :gross_amount, :total_deduction, :net_amount, :deductions, :period_from, :period_to)";
        $calcs = $this->calculateBillAmounts($dto->bill_items ?? [], 'Manpower', (int)$dto->agreement_id);
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'officeid' => $dto->officeid, 'agency_bill_no' => $dto->agency_bill_no, 'bill_date' => $dto->bill_date, 'office_bill_no' => $dto->office_bill_no, 'bill_type' => $dto->bill_type, 'agreement_id' => $dto->agreement_id, 'work_order_id' => $dto->work_order_id, 'supply_order_id' => $dto->supply_order_id, 'budget_id' => $dto->budget_id, 'bill_items' => $dto->bill_items ? json_encode($dto->bill_items) : null, 'withheld' => $dto->withheld ? json_encode($dto->withheld) : null, 'gross_amount' => $calcs['gross_amount'], 'total_deduction' => $calcs['total_deduction'], 'net_amount' => $calcs['net_amount'], 'deductions' => json_encode($calcs['deductions']), 'period_from' => $dto->period_from ?? null, 'period_to' => $dto->period_to ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateBillStatus(int $id, string $status, ?string $date): void {
        $column = '';
        if ($status === 'Processed') $column = 'status_processed_at';
        elseif ($status === 'Paid') $column = 'status_paid_at';
        if ($column) {
            $sql = "UPDATE bills SET status = :status::bill_status, $column = :date WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id, 'status' => $status, 'date' => $date]);
        } else {
            $sql = "UPDATE bills SET status = :status::bill_status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id, 'status' => $status]);
        }
    }

    public function deleteBill(int $id): void {
        $bill = $this->getBillById($id);
        if ($bill && $bill['status'] !== 'Draft') throw new Exception("Cannot delete a non-Draft bill.");
        $this->db->prepare("DELETE FROM bills WHERE id = :id")->execute(['id' => $id]);
    }

    public function generateManpowerBillItems(int $agreementId, string $fromDate, string $toDate): array {
        // 1. CHECK IF ATTENDANCE EXISTS FOR THIS PERIOD
        $sqlCheck = "
            SELECT COUNT(*)
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            WHERE e.agreement_id = :aid AND ar.attendance_date BETWEEN :from AND :to
        ";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['aid' => $agreementId, 'from' => $fromDate, 'to' => $toDate]);
        if ($stmtCheck->fetchColumn() == 0) {
            throw new Exception("Cannot generate bill. Attendance for the period " . date('d.m.Y', strtotime($fromDate)) . " to " . date('d.m.Y', strtotime($toDate)) . " has not been marked yet.");
        }

        // 2. PROCEED WITH GENERATION
        $sql = "SELECT wi.item_name as description, wi.unit, get_effective_rate(:from_date, wi.id) as rate, ar.status FROM employees e JOIN wage_items wi ON e.designation = wi.item_name LEFT JOIN attendance_records ar ON e.id = ar.employee_id AND ar.attendance_date BETWEEN :from_date AND :to_date WHERE e.agreement_id = :agreement_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['from_date' => $fromDate, 'to_date' => $toDate, 'agreement_id' => $agreementId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($rows as $row) {
            $desc = $row['description'];
            if (!isset($grouped[$desc])) { $grouped[$desc] = ['description' => $desc, 'unit' => $row['unit'], 'rate' => $row['rate'], 'P' => 0, 'A' => 0, 'L' => 0, 'R' => 0]; }
            if ($row['status']) { $grouped[$desc][$row['status']]++; }
        }
        $billItems = [];
        foreach ($grouped as $item) {
            $qty = ($item['unit'] === 'Per Person Per Day') ? $item['P'] : ($item['P'] / 26.0);
            $absentQty = ($item['unit'] === 'Per Person Per Day') ? $item['A'] : ($item['A'] / 26.0);
            $billItems[] = ['description' => $item['description'], 'qty' => round($qty, 2), 'unit' => $item['unit'], 'rate' => $item['rate'], 'absent_qty' => round($absentQty, 2), 'breakdown' => ['P' => $item['P'], 'A' => $item['A'], 'L' => $item['L'], 'R' => $item['R']]];
        }
        return $billItems;
    }

    public function getDailyAttendanceSummary(int $agreementId, string $fromDate, string $toDate): array {
        $sql = "SELECT ar.attendance_date, e.designation, COUNT(*) as present_count FROM attendance_records ar JOIN employees e ON ar.employee_id = e.id WHERE e.agreement_id = :agreement_id AND ar.attendance_date BETWEEN :from AND :to AND ar.status = 'P' GROUP BY ar.attendance_date, e.designation ORDER BY ar.attendance_date ASC, e.designation ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['agreement_id' => $agreementId, 'from' => $fromDate, 'to' => $toDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $formatted = [];
        foreach ($results as $row) {
            $date = $row['attendance_date'];
            if (!isset($formatted[$date])) { $formatted[$date] = []; }
            $formatted[$date][] = $row['designation'] . "=" . $row['present_count'];
        }
        $final = [];
        foreach ($formatted as $date => $details) { $final[] = ['attendance_date' => date('d M, Y', strtotime($date)), 'designation' => implode(', ', $details)]; }
        return $final;
    }

    public function calculateBillAmounts(array $billItems, string $agreementType, ?int $agreementId = null): array {
        $totalBilled = 0.0; $totalAbsenteePenalty = 0.0;
        foreach ($billItems as $item) { $rate = (float)$item['rate']; $totalBilled += (float)$item['qty'] * $rate; $totalAbsenteePenalty += (float)($item['absent_qty'] ?? 0) * $rate; }
        $afterPenalty = round($totalBilled - $totalAbsenteePenalty);
        $serviceCharge = 0.0; $serviceChargePercent = 0.0;
        if ($agreementId) {
            $agreementService = new AgreementService();
            $agreement = $agreementService->getAgreementById($agreementId);
            if ($agreement) { $serviceChargePercent = (float)$agreement->service_charge_percent; $serviceCharge = round($afterPenalty * ($serviceChargePercent / 100)); }
        }
        $grossAmount = $afterPenalty + $serviceCharge;
        $deductions = ['I_Tax @ 2%' => round($grossAmount * 0.02), 'CGST @ 1%' => round($grossAmount * 0.01), 'SGST @ 1%' => round($grossAmount * 0.01), 'Security Deposit @ 5%' => round($grossAmount * 0.05)];
        $totalDeduction = array_sum($deductions);
        $netAmount = $grossAmount - $totalDeduction;
        return ['total_billed' => round($totalBilled, 2), 'absentee_penalty' => round($totalAbsenteePenalty, 2), 'after_penalty' => $afterPenalty, 'service_charge' => $serviceCharge, 'service_charge_percent' => $serviceChargePercent, 'gross_amount' => $grossAmount, 'total_deduction' => $totalDeduction, 'net_amount' => $netAmount, 'deductions' => $deductions];
    }
}
