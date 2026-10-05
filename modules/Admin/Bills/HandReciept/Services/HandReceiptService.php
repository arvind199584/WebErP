<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\HandReciept\Services;

use App\Core\Database;
use PDO;

class HandReceiptService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllReceipts(): array {
        $sql = "SELECT hr.*, b.code as budget_code
                FROM hand_receipts hr
                LEFT JOIN budget b ON hr.budgetid = b.id
                ORDER BY hr.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createReceipt(array $data): bool {
        $sql = "INSERT INTO hand_receipts (officeid, budgetid, category, description, gross_amount, tds_amount, net_amount, budget_implication)
                VALUES (:officeid, :budgetid, :category, :description, :gross, :tds, :net, :implication)";
        $stmt = $this->db->prepare($sql);

        $hasImplication = (bool)($data['budget_implication'] ?? false);

        return $stmt->execute([
            'officeid' => $data['officeid'],
            // STRICT ENFORCEMENT: If no implication, budgetid MUST be null
            'budgetid' => ($hasImplication && !empty($data['budgetid'])) ? $data['budgetid'] : null,
            'category' => $data['category'],
            'description' => $data['description'],
            'gross' => $data['gross_amount'],
            'tds' => $data['tds_amount'],
            'net' => $data['net_amount'],
            'implication' => $hasImplication ? 'true' : 'false'
        ]);
    }

    public function getBudgetsByOffice(int $officeId): array {
        $sql = "SELECT id, code, name_of_work FROM budget WHERE officeid = :oid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['oid' => $officeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllOffices(): array {
        return $this->db->query("SELECT Officeid, OfficeName FROM office ORDER BY OfficeName")->fetchAll(PDO::FETCH_ASSOC);
    }
}
