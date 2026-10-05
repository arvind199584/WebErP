<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Services;

use App\Core\Database;
use PDO;

class ESICReportService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getESICReport(int $agreementId, string $fromDate, string $toDate): array {
        $sql = "SELECT * FROM get_esic_summary_report(:agreement_id, :from_date, :to_date, :drawn_on)";
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
