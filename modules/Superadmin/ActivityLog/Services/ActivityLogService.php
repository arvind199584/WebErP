<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\ActivityLog\Services;

use App\Core\Database;
use PDO;

class ActivityLogService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getLogs(int $limit = 100): array {
        // CORRECTED: u.usrname instead of u.username
        $sql = "SELECT l.*, u.usrname as username
                FROM activity_logs l
                LEFT JOIN users u ON l.changed_by = u.id
                ORDER BY l.changed_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
