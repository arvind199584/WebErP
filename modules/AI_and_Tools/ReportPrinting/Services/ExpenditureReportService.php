<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Services;

use App\Core\Database;
use PDO;

class ExpenditureReportService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getExpenditureByAgreement(int $agreementId): array {
        $sql = "
            SELECT
                b.office_bill_no,
                b.bill_date,
                b.bill_type,
                b.gross_amount,
                b.net_amount,
                b.created_at
            FROM bills b
            WHERE b.agreement_id = :aid AND b.status IN ('Processed', 'Paid')
            ORDER BY b.created_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['aid' => $agreementId]);
        $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Cumulative Total
        $cumulative = 0;
        foreach ($bills as &$bill) {
            $cumulative += (float)$bill['gross_amount'];
            $bill['cumulative_gross'] = $cumulative;
        }

        return $bills;
    }
}
