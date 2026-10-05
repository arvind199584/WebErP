<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Revenue\TemporaryMember\Services;

use App\Core\Database;
use PDO;

class TMService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM tm ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tm WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): bool {
        $sql = "INSERT INTO tm (office_id, tm_no, name, dob, address, dependents, validity, payment_amount, payment_reference) 
                VALUES (:office_id, :tm_no, :name, :dob, :address, :dependents, :validity, :payment_amount, :payment_reference)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'office_id' => $data['office_id'],
            'tm_no' => $data['tm_no'],
            'name' => $data['name'],
            'dob' => $data['dob'],
            'address' => $data['address'],
            'dependents' => $data['dependents'],
            'validity' => $data['validity'],
            'payment_amount' => $data['payment_amount'],
            'payment_reference' => $data['payment_reference']
        ]);
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE tm SET tm_no = :tm_no, name = :name, dob = :dob, address = :address, 
                dependents = :dependents, validity = :validity, payment_amount = :payment_amount,
                payment_reference = :payment_reference, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        $params = $data;
        $params['id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM tm WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
