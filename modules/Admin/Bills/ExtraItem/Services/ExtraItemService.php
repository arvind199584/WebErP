<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\ExtraItem\Services;

use App\Core\Database;
use PDO;

class ExtraItemService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllExtraItems(): array {
        $sql = "SELECT ei.*, a.agreement_no FROM extra_items ei JOIN agreements a ON ei.agreement_id = a.id ORDER BY ei.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createExtraItem(array $data): bool {
        // 1. Fetch Original AA_ES Amount
        $sqlAE = "SELECT ae.aa_es_amount FROM agreements a JOIN aa_es ae ON a.aa_es_id = ae.id WHERE a.id = :id";
        $stmtAE = $this->db->prepare($sqlAE);
        $stmtAE->execute(['id' => $data['agreement_id']]);
        $originalAmount = (float)$stmtAE->fetchColumn();

        // 2. Calculate Percentage
        $extraAmount = (float)($data['extra_item_amount'] ?? 0);
        $percentage = ($originalAmount > 0) ? ($extraAmount / $originalAmount) * 100 : 0;

        // 3. Insert
        $sql = "INSERT INTO extra_items (officeid, agreement_id, sub_head, type, boq, estimated_cost, justified_amount, extra_item_amount, deviation_percentage)
                VALUES (:officeid, :agreement_id, :sub_head, :type, :boq, :est, :just, :extra, :perc)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'officeid' => $data['officeid'],
            'agreement_id' => $data['agreement_id'],
            'sub_head' => $data['sub_head'],
            'type' => $data['type'],
            'boq' => json_encode($data['boq']),
            'est' => $data['estimated_cost'] ?? 0,
            'just' => $data['justified_amount'] ?? 0,
            'extra' => $extraAmount,
            'perc' => $percentage
        ]);
    }
}
