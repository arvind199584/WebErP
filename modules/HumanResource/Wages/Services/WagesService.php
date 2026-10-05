<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Wages\Services;

use App\Core\Database;
use PDO;

class WagesService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getEmployeeDashboard(int $agreementId): array {
        $sql = "SELECT * FROM get_employee_wage_dashboard(:aid)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['aid' => $agreementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateWagesForPeriod(int $employeeId, int $officeId, string $from, string $to): void {
        $sql = "SELECT generate_wages_for_period(:eid, :oid, :from, :to)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['eid' => $employeeId, 'oid' => $officeId, 'from' => $from, 'to' => $to]);
    }

    public function generateWagesForAllEmployees(int $agreementId, int $officeId): int {
        $dashboard = $this->getEmployeeDashboard($agreementId);
        $count = 0;
        foreach ($dashboard as $row) {
            if ($row['pending_from'] && strtotime($row['pending_from']) <= strtotime($row['pending_to'])) {
                $this->generateWagesForPeriod((int)$row['id'], $officeId, $row['pending_from'], $row['pending_to']);
                $count++;
            }
        }
        return $count;
    }

    public function recalculateWagesForAgreement(int $agreementId, int $officeId, string $from, string $to): int {
        $this->db->beginTransaction();
        try {
            $sqlDelete = "DELETE FROM wages_ledger WHERE cr_amount > 0 AND transaction_date BETWEEN :from AND :to AND employee_id IN (SELECT id FROM employees WHERE agreement_id = :aid)";
            $stmtDel = $this->db->prepare($sqlDelete);
            $stmtDel->execute(['from' => $from, 'to' => $to, 'aid' => $agreementId]);
            $sqlEmp = "SELECT id FROM employees WHERE agreement_id = :aid";
            $stmtEmp = $this->db->prepare($sqlEmp);
            $stmtEmp->execute(['aid' => $agreementId]);
            $employees = $stmtEmp->fetchAll(PDO::FETCH_COLUMN);
            foreach ($employees as $empId) { $this->generateWagesForPeriod((int)$empId, $officeId, $from, $to); }
            $this->db->commit();
            return count($employees);
        } catch (\Exception $e) { $this->db->rollBack(); throw $e; }
    }

    public function addDebitEntry(int $employeeId, int $officeId, string $date, float $amount, string $remark): void {
        $sql = "INSERT INTO wages_ledger (officeid, employee_id, transaction_date, dr_amount, remark) VALUES (:oid, :eid, :date, :amt, :rem)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['oid' => $officeId, 'eid' => $employeeId, 'date' => $date, 'amt' => $amount, 'rem' => $remark]);
    }

    /**
     * Adds a batch debit entry based on a range of wages.
     */
    public function addBatchDebitEntry(int $agreementId, int $officeId, string $wagesFrom, string $wagesTo, string $paymentDate, string $remark): int {
        $sqlEmp = "SELECT id FROM employees WHERE agreement_id = :aid";
        $stmtEmp = $this->db->prepare($sqlEmp);
        $stmtEmp->execute(['aid' => $agreementId]);
        $employees = $stmtEmp->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        $this->db->beginTransaction();
        try {
            foreach ($employees as $empId) {
                // Calculate SUM of CR entries in the specified range
                $sqlSum = "SELECT COALESCE(SUM(cr_amount), 0)
                           FROM wages_ledger
                           WHERE employee_id = :eid
                           AND transaction_date BETWEEN :from AND :to";
                $stmtSum = $this->db->prepare($sqlSum);
                $stmtSum->execute(['eid' => $empId, 'from' => $wagesFrom, 'to' => $wagesTo]);
                $totalDue = (float)$stmtSum->fetchColumn();

                if ($totalDue > 0) {
                    $this->addDebitEntry((int)$empId, $officeId, $paymentDate, $totalDue, $remark);
                    $count++;
                }
            }
            $this->db->commit();
            return $count;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getWageSummaryReport(int $agreementId, string $from, string $to, string $drawnOn): array {
        $sql = "SELECT * FROM get_wage_summary_report(:aid, :from, :to, :drawn)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['aid' => $agreementId, 'from' => $from, 'to' => $to, 'drawn' => $drawnOn]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFullWageLedger(int $employeeId): array {
        $sql = "SELECT * FROM wages_ledger WHERE employee_id = :eid ORDER BY transaction_date ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['eid' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
