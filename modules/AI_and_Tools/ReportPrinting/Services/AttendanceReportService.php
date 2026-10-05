<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Services;

use App\Core\Database;
use PDO;

class AttendanceReportService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAttendanceReport(int $agreementId, string $month): array {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $daysInMonth = (int)date('t', strtotime($startDate));

        // 1. Fetch Header Details
        $headerSql = "
            SELECT b.name_of_work, ae.sub_head, a.agreement_no, ag.name as agency_name,
                   a.signatory, o.officecode as \"Shortname\"
            FROM agreements a
            JOIN aa_es ae ON a.aa_es_id = ae.id
            JOIN budget b ON ae.budgetid = b.id
            JOIN agencies ag ON a.agency_id = ag.id
            JOIN office o ON b.officeid = o.officeid
            WHERE a.id = :agreement_id
        ";
        $stmt = $this->db->prepare($headerSql);
        $stmt->execute(['agreement_id' => $agreementId]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch ALL Employees for this agreement
        $empSql = "
            SELECT e.id, e.full_name as name, e.designation, e.is_reliever, e.joining_date, e.inherited_from_id,
                   get_effective_rate(:start_date, wi.id) as current_rate
            FROM employees e
            JOIN wage_items wi ON e.designation = wi.item_name
            WHERE e.agreement_id = :agreement_id AND e.joining_date <= :end_date
        ";
        $stmtEmp = $this->db->prepare($empSql);
        $stmtEmp->execute(['agreement_id' => $agreementId, 'start_date' => $startDate, 'end_date' => $endDate]);
        $employees = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Attendance Records for current employees and their predecessors
        $empIds = array_column($employees, 'id');
        $inheritedIds = array_filter(array_column($employees, 'inherited_from_id'));
        $allRelevantEmpIds = array_unique(array_merge($empIds, $inheritedIds));
        
        $attendance = [];
        if (!empty($allRelevantEmpIds)) {
            $placeholders = implode(',', array_fill(0, count($allRelevantEmpIds), '?'));
            $attSql = "SELECT employee_id, attendance_date, status FROM attendance_records 
                       WHERE employee_id IN ($placeholders) AND attendance_date BETWEEN ? AND ?";
            $stmtAtt = $this->db->prepare($attSql);
            $params = array_merge($allRelevantEmpIds, [$startDate, $endDate]);
            $stmtAtt->execute($params);
            $attRaw = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attRaw as $row) {
                $attendance[$row['employee_id']][(int)date('d', strtotime($row['attendance_date']))] = $row['status'];
            }
        }

        // 4. Build the Report Grid
        $reportData = [];
        foreach ($employees as $emp) {
            $empId = $emp['id'];
            $parentEmpId = $emp['inherited_from_id'];
            
            $row = [
                'id' => $empId,
                'name' => $emp['name'],
                'designation' => $emp['designation'],
                'is_reliever' => $emp['is_reliever'],
                'wage_rate' => $emp['current_rate'],
                'days' => [],
                'summary' => ['P' => 0, 'A' => 0, 'R' => 0, 'L' => 0]
            ];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $currentDate = date('Y-m-d', strtotime("$month-$d"));

                // Logic: 
                // 1. Try current employee record
                // 2. If no status and has parent, try parent record
                // 3. Else fallback to 'A' or 'L'
                $status = $attendance[$empId][$d] ?? null;
                
                if ($status === null && $parentEmpId !== null) {
                    $status = $attendance[$parentEmpId][$d] ?? null;
                }

                if ($status === null) {
                    if ($currentDate < $emp['joining_date'] && ($parentEmpId === null)) {
                        $status = 'L'; // Blank in print
                    } else {
                        $status = 'A';
                    }
                }

                if (isset($row['summary'][$status])) {
                    $row['summary'][$status]++;
                }
                $row['days'][$d] = $status;
                }
                if ($row['summary']['P'] > 0) {
                $reportData[] = $row;
                }
                }

                usort($reportData, function($a, $b) {
                return strcmp($a['name'], $b['name']);
                });

                return [
                'header' => $header,
                'data' => $reportData,
                'daysInMonth' => $daysInMonth
                ];    }
}
