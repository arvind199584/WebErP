<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Absentee\Services;

use App\Core\Database;
use App\Modules\HumanResource\Employee\Services\EmployeeService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use PDO;
use Exception;

require_once __DIR__ . '/../../Employee/Services/EmployeeService.php';
require_once __DIR__ . '/../../../Admin/Agreement/Services/AgreementService.php';

class AttendanceService {
    private PDO $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUnmarkedEmployees(int $agreementId, string $month): array {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));

        $sql = "SELECT e.id, e.full_name, e.designation, e.is_reliever, e.default_rest_day, e.joining_date
                FROM employees e
                WHERE e.agreement_id = :agreementId
                  AND e.joining_date <= :endDate
                  AND (e.leaving_date IS NULL OR e.leaving_date >= :startDate)
                  AND NOT EXISTS (
                      SELECT 1 FROM attendance_records ar
                      WHERE ar.employee_id = e.id
                        AND DATE_TRUNC('month', ar.attendance_date) = :startDate
                  )
                ORDER BY e.is_reliever ASC, e.designation ASC, e.full_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':agreementId', $agreementId);
        $stmt->bindValue(':startDate', $startDate);
        $stmt->bindValue(':endDate', $endDate);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function expandRangeString(string $str): array {
        $result = [];
        $parts = explode(',', $str);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            if (strpos($part, '-') !== false) {
                list($start, $end) = explode('-', $part);
                for ($i = (int)$start; $i <= (int)$end; $i++) { $result[] = $i; }
            } else { $result[] = (int)$part; }
        }
        return array_unique($result);
    }

    public function saveEmployeeAttendance(int $employeeId, int $officeId, string $month, string $pDays, string $aDays, string $lDays, string $rDays): void {
        $sqlAgr = "SELECT lower(a.period) FROM agreements a JOIN employees e ON a.id = e.agreement_id WHERE e.id = :id";
        $stmtAgr = $this->db->prepare($sqlAgr);
        $stmtAgr->execute(['id' => $employeeId]);
        $agreementStart = $stmtAgr->fetchColumn();

        $monthStart = date('Y-m-01', strtotime($month));
        $monthEnd = date('Y-m-t', strtotime($month));

        $pArr = $this->expandRangeString($pDays);
        $aArr = $this->expandRangeString($aDays);
        $lArr = $this->expandRangeString($lDays);
        $rArr = $this->expandRangeString($rDays);

        if ($agreementStart) {
            $current = new \DateTime($monthStart);
            $end = new \DateTime($monthEnd);
            $agrStartDt = new \DateTime($agreementStart);
            while ($current <= $end) {
                if ($current < $agrStartDt) {
                    $dayNum = (int)$current->format('d');
                    if (!in_array($dayNum, $lArr)) $lArr[] = $dayNum;
                }
                $current->modify('+1 day');
            }
        }

        $toPgArray = fn($arr) => '{' . implode(',', $arr) . '}';
        $sql = "SELECT fill_employee_attendance(:eid::INT, :oid::INT, :start::DATE, :end::DATE, :p::INT[], :a::INT[], :l::INT[], :r::INT[])";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['eid' => $employeeId, 'oid' => $officeId, 'start' => $monthStart, 'end' => $monthEnd, 'p' => $toPgArray($pArr), 'a' => $toPgArray($aArr), 'l' => $toPgArray($lArr), 'r' => $toPgArray($rArr)]);
    }

    public function getAttendanceGridForMonth(int $agreementId, string $month): array {
        $employeeService = new EmployeeService();
        $employees = $employeeService->getEmployeesByAgreement($agreementId);
        if (empty($employees)) return [];

        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
        $daysInMonth = (int)date('t', strtotime($month));

        $employeeIds = array_column($employees, 'id');
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));

        // 1. CHECK IF ANY RECORDS EXIST FOR THIS MONTH
        $sqlCheck = "SELECT COUNT(*) FROM attendance_records WHERE employee_id IN ($placeholders) AND attendance_date BETWEEN ? AND ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([...$employeeIds, $startDate, $endDate]);
        if ($stmtCheck->fetchColumn() == 0) {
            throw new Exception("Attendance for " . date('F Y', strtotime($month)) . " has not been marked yet. Please use the 'Mark Attendance' tool first.");
        }

        // 2. FETCH RECORDS
        $sql = "SELECT employee_id, attendance_date, status FROM attendance_records WHERE employee_id IN ($placeholders) AND attendance_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$employeeIds, $startDate, $endDate]);
        $rawRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $records = [];
        foreach ($rawRecords as $rec) { $records[$rec['employee_id']][$rec['attendance_date']] = $rec['status']; }

        $grid = [];
        foreach ($employees as $emp) {
            if ($emp['joining_date'] > $endDate) continue;
            $empId = $emp['id'];
            $row = ['employee_id' => $empId, 'full_name' => $emp['full_name'], 'designation' => $emp['designation'], 'joining_date' => $emp['joining_date'], 'days' => [], 'summary' => ['P' => 0, 'A' => 0, 'R' => 0, 'L' => 0]];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = date('Y-m-d', strtotime("$month-$d"));
                if ($dateStr < $emp['joining_date']) { $status = '-'; }
                else { $status = $records[$empId][$dateStr] ?? 'A'; $row['summary'][$status]++; }
                $row['days'][$d] = $status;
            }
            $grid[] = $row;
        }
        return $grid;
    }

    public function saveAttendanceSheet(array $attendanceData, int $officeId): void {
        $sql = "INSERT INTO attendance_records (officeid, employee_id, attendance_date, status) VALUES (:officeid, :employee_id, :attendance_date, :status::attendance_status) ON CONFLICT (employee_id, attendance_date) DO UPDATE SET status = EXCLUDED.status, updated_at = NOW()";
        $stmt = $this->db->prepare($sql);
        $this->db->beginTransaction();
        try {
            foreach ($attendanceData as $employee_id => $days) {
                $sqlEmp = "SELECT joining_date, leaving_date FROM employees WHERE id = :id";
                $stmtEmp = $this->db->prepare($sqlEmp);
                $stmtEmp->execute(['id' => $employee_id]);
                $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
                
                $joiningDate = $emp['joining_date'];
                $leavingDate = $emp['leaving_date'];

                foreach ($days as $date => $status) {
                    if ($date >= $joiningDate && ($leavingDate === null || $date <= $leavingDate)) {
                        $stmt->execute([
                            'officeid' => $officeId,
                            'employee_id' => $employee_id,
                            'attendance_date' => $date,
                            'status' => $status
                        ]);
                    }
                }
            }
            $this->db->commit();
        } catch (\Exception $e) { $this->db->rollBack(); throw $e; }
    }
}
