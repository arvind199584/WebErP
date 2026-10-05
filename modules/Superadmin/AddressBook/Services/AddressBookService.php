<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\AddressBook\Services;

use App\Core\Database;
use PDO;

class AddressBookService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllAddresses(): array {
        $sql = "SELECT * FROM address_book ORDER BY category, name";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAddressById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM address_book WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function addAddress(int $officeId, array $data): void {
        $sql = "INSERT INTO address_book (officeid, name, designation, department, address, email, category)
                VALUES (:oid, :name, :desig, :dept, :addr, :email, :cat)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'oid' => $officeId,
            'name' => $data['name'],
            'desig' => $data['designation'] ?? null,
            'dept' => $data['department'] ?? null,
            'addr' => $data['address'] ?? null,
            'email' => $data['email'] ?? null,
            'cat' => $data['category'] ?? 'Other'
        ]);
    }

    public function deleteAddress(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM address_book WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
