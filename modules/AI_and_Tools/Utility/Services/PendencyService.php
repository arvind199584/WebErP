<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\Utility\Services;

use App\Core\Database;
use PDO;

class PendencyService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getPendencies(int $officeId, string $role): array {
        $pendencies = [];
        $today = new \DateTime();
        $currentDay = (int)$today->format('d');

        $prevMonth = (clone $today)->modify('first day of last month');
        $monthStr = $prevMonth->format('Y-m');

        // 1. Fetch Active Manpower Agreements with Agency Name
        $sql = "SELECT
                    a.id,
                    a.agreement_no,
                    ae.alias,
                    ag.name as agency_name,
                    lower(a.period) as period_from,
                    (upper(a.period) - INTERVAL '1 day')::date as period_to
                FROM agreements a
                JOIN aa_es ae ON a.aa_es_id = ae.id
                JOIN agencies ag ON a.agency_id = ag.id
                WHERE a.status = 'Active' AND ae.type = 'Manpower'";

        if ($role !== 'superuser') {
            $sql .= " AND a.officeid = :oid";
        }

        $stmt = $this->db->prepare($sql);
        if ($role !== 'superuser') $stmt->bindValue(':oid', $officeId);
        $stmt->execute();
        $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($agreements as $ag) {
            $aid = (int)$ag['id'];
            $alias = $ag['alias'] ?: 'No Alias';
            $agNo = $ag['agreement_no'];
            $agency = $ag['agency_name'];
            $info = "Agmt: $agNo, Agency: $agency, Alias: $alias";

            // --- EXPIRY ALERTS ---
            $expiryDate = new \DateTime($ag['period_to']);
            $interval = $today->diff($expiryDate);
            $monthsToExpiry = ($interval->y * 12) + $interval->m;

            if ($expiryDate < $today || ($interval->y == 0 && $interval->m == 0)) {
                $pendencies[] = ['category' => 'Expiry', 'msg' => $info, 'severity' => 'high', 'responsible' => 'Manager'];
            } elseif ($monthsToExpiry <= 3) {
                $pendencies[] = ['category' => 'Expiry', 'msg' => $info . " (Expires in $monthsToExpiry months)", 'severity' => 'medium', 'responsible' => 'Manager'];
            }

            // --- HIERARCHICAL TIMELINE ALERTS ---

            // 1. Attendance (by 5th)
            $sqlAtt = "SELECT COUNT(*) FROM attendance_records ar
                       JOIN employees e ON ar.employee_id = e.id
                       WHERE e.agreement_id = :aid AND to_char(ar.attendance_date, 'YYYY-MM') = :month";
            $stmtAtt = $this->db->prepare($sqlAtt);
            $stmtAtt->execute(['aid' => $aid, 'month' => $monthStr]);

            if ($stmtAtt->fetchColumn() == 0) {
                if ($currentDay >= 5) {
                    $pendencies[] = ['category' => 'Attendance', 'msg' => $info, 'severity' => 'high', 'responsible' => 'Manager'];
                }
                continue;
            }

            // 2. Bill Submission (by 10th) - Note: Wages check is implicit here
            $sqlBill = "SELECT id, status FROM bills WHERE agreement_id = :aid AND period_from >= :start AND period_to <= :end FETCH FIRST 1 ROWS ONLY";
            $stmtBill = $this->db->prepare($sqlBill);
            $stmtBill->execute(['aid' => $aid, 'start' => $prevMonth->format('Y-m-01'), 'end' => $prevMonth->format('Y-m-t')]);
            $bill = $stmtBill->fetch(PDO::FETCH_ASSOC);

            if (!$bill) {
                if ($currentDay >= 10) {
                    $pendencies[] = ['category' => 'Bill Submission', 'msg' => $info, 'severity' => 'medium', 'responsible' => 'Agency'];
                }
                continue;
            }

            // 3. Bill Processing (by 15th)
            if ($bill['status'] === 'Draft' && $currentDay >= 15) {
                $pendencies[] = ['category' => 'Bill Processing', 'msg' => $info, 'severity' => 'medium', 'responsible' => 'Manager'];
            }
        }

        return $pendencies;
    }
}
