<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Membership\Services;

use App\Core\Database;
use PDO;

class MembershipSchemaService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function initSchema(): void {
        $check = $this->db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'members'")->fetch();
        if ($check) return;

        $sqlFile = __DIR__ . '/../sql/schema.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            try {
                $this->db->exec($sql);
            } catch (\PDOException $e) {
                error_log("Membership Schema Init Error: " . $e->getMessage());
            }
        }
    }
}
