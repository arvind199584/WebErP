<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Membership\Services;

use App\Core\Database;
use PDO;

class MembershipService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAllMembers(): array {
        $sql = "SELECT * FROM members ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createMember(array $data): bool {
        $sql = "INSERT INTO members (officeid, membership_type, full_name, gender, dob, uaidi, address, phone, email, joining_date, history)
                VALUES (:officeid, :type, :name, :gender, :dob, :uaidi, :address, :phone, :email, :join, :history)";

        $history = [[
            'event' => 'Joined',
            'date' => date('Y-m-d'),
            'details' => 'New membership created'
        ]];

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'officeid' => $data['officeid'],
            'type' => $data['membership_type'],
            'name' => $data['full_name'],
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'uaidi' => $data['uaidi'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'join' => $data['joining_date'] ?? date('Y-m-d'),
            'history' => json_encode($history)
        ]);
    }

    public function addHistoryEvent(int $memberId, string $event, string $details): void {
        $sql = "UPDATE members
                SET history = history || :new_event
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'new_event' => json_encode([[
                'event' => $event,
                'date' => date('Y-m-d'),
                'details' => $details
            ]]),
            'id' => $memberId
        ]);
    }
}
