<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Services;

use App\Core\Database;
use PDO;

class ReportService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getBillStatusReport(?string $fromDate, ?string $toDate): array {
        $sql = "SELECT b.id, b.bill_date, b.office_bill_no, b.agency_bill_no, b.bill_type, b.gross_amount, b.net_amount, b.status, a.agreement_no, ag.name as agency_name FROM bills b LEFT JOIN agreements a ON b.agreement_id = a.id LEFT JOIN agencies ag ON a.agency_id = ag.id WHERE 1=1";
        $params = [];
        if ($fromDate) { $sql .= " AND b.bill_date >= :from_date"; $params['from_date'] = $fromDate; }
        if ($toDate) { $sql .= " AND b.bill_date <= :to_date"; $params['to_date'] = $toDate; }
        $sql .= " ORDER BY b.bill_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAgreementReport(string $status): array {
        $sql = "SELECT a.agreement_no, ag.name as agency_name, a.tendered_amount, a.period, a.status FROM agreements a JOIN agencies ag ON a.agency_id = ag.id WHERE a.status = :status ORDER BY a.agreement_no";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttendanceReport(int $agreementId, string $month): array {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $headerSql = "SELECT b.name_of_work, ae.sub_head, a.agreement_no, ag.name as agency_name FROM agreements a JOIN aa_es ae ON a.aa_es_id = ae.id JOIN budget b ON ae.budgetid = b.id JOIN agencies ag ON a.agency_id = ag.id WHERE a.id = :agreement_id";
        $stmt = $this->db->prepare($headerSql);
        $stmt->execute(['agreement_id' => $agreementId]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql = "SELECT e.id, e.full_name as name, e.designation, e.is_reliever, ar.attendance_date, ar.status, get_effective_rate(:start_date, wi.id) as current_rate FROM employees e JOIN wage_items wi ON e.designation = wi.item_name JOIN attendance_records ar ON e.id = ar.employee_id WHERE e.agreement_id = :agreement_id AND ar.attendance_date BETWEEN :start_date AND :end_date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['agreement_id' => $agreementId, 'start_date' => $startDate, 'end_date' => $endDate]);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $report = [];
        foreach ($raw as $row) {
            $empId = $row['id'];
            if (!isset($report[$empId])) { $report[$empId] = ['name' => $row['name'], 'designation' => $row['designation'], 'is_reliever' => $row['is_reliever'], 'wage_rate' => $row['current_rate'], 'days' => [], 'has_attendance' => false]; }
            if ($row['attendance_date']) { $day = (int)date('d', strtotime($row['attendance_date'])); $report[$empId]['days'][$day] = $row['status']; if (in_array($row['status'], ['P', 'A', 'R'])) { $report[$empId]['has_attendance'] = true; } }
        }
        $filteredReport = array_filter($report, function($emp) { return $emp['has_attendance']; });
        usort($filteredReport, function($a, $b) { if ($a['is_reliever'] != $b['is_reliever']) { return $a['is_reliever'] ? 1 : -1; } if ($a['wage_rate'] != $b['wage_rate']) { return ($b['wage_rate'] <=> $a['wage_rate']); } return strcmp($b['designation'], $a['designation']); });
        return ['header' => $header, 'data' => $filteredReport];
    }

    /**
     * NEW: Wages Verification Report
     */
    public function getWagesVerificationReport(int $agreementId, string $fromDate, string $toDate): array {
        // We use the toDate as the 'drawn_on' date as well for this summary
        $sql = "SELECT * FROM get_wage_summary_report(:agreement_id, :from_date, :to_date, :drawn_on)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'agreement_id' => $agreementId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'drawn_on' => $toDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
