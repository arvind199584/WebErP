<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\EPF\Services;

use App\Core\Database;
use PDO;

class EpfService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getContributionStatusForMonth(int $agreementId, string $month): array {
        $startDate = date('Y-m-01', strtotime($month));
        $sql = "
            SELECT
                e.id, e.full_name, e.designation,
                (SELECT COUNT(*) > 0 FROM epf_ledger el
                 WHERE el.employee_id = e.id
                   AND date_trunc('month', el.transaction_date) = :startDate) as has_contribution
            FROM employees e
            JOIN agreements ag ON e.agreement_id = ag.id
            WHERE e.agreement_id = :agreementId AND ag.bill_type_epf = TRUE
            ORDER BY e.full_name;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['agreementId' => $agreementId, 'startDate' => $startDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateContribution(int $employeeId, int $officeId, string $month): void {
        $sql = "SELECT generate_epf_contribution(:employee_id, :office_id, :month)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId, 'office_id' => $officeId, 'month' => $month]);
    }
}
