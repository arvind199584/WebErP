<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\Deviation\Services;

use App\Core\Database;
use PDO;

class DeviationService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllDeviations(): array {
        $sql = "SELECT d.*, a.agreement_no FROM deviations d JOIN agreements a ON d.agreement_id = a.id ORDER BY d.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOriginalBOQ(int $agreementId): array {
        $sql = "SELECT ae.boq, ae.type FROM agreements a JOIN aa_es ae ON a.aa_es_id = ae.id WHERE a.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $agreementId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            return ['type' => $res['type'], 'boq' => json_decode($res['boq'], true)];
        }
        return [];
    }

    public function createDeviation(array $data): bool {
        $sqlAE = "SELECT ae.aa_es_amount FROM agreements a JOIN aa_es ae ON a.aa_es_id = ae.id WHERE a.id = :id";
        $stmtAE = $this->db->prepare($sqlAE);
        $stmtAE->execute(['id' => $data['agreement_id']]);
        $originalAmount = (float)$stmtAE->fetchColumn();

        $devAmount = (float)($data['deviation_amount'] ?? 0);
        $percentage = ($originalAmount > 0) ? ($devAmount / $originalAmount) * 100 : 0;

        $sql = "INSERT INTO deviations (officeid, agreement_id, sub_head, type, boq, estimated_cost, justified_amount, deviation_amount, deviation_percentage)
                VALUES (:officeid, :agreement_id, :sub_head, :type, :boq, :est, :just, :dev, :perc)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'officeid' => $data['officeid'],
            'agreement_id' => $data['agreement_id'],
            'sub_head' => $data['sub_head'],
            'type' => $data['type'],
            'boq' => json_encode($data['boq']),
            'est' => $data['estimated_cost'] ?? 0,
            'just' => $data['justified_amount'] ?? 0,
            'dev' => $devAmount,
            'perc' => $percentage
        ]);
    }
}
