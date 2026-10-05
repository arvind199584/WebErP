<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIML\Services;

use App\Core\Database;
use PDO;

class AIMLSchemaService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function initSchema(): void {
        // Check if table already exists
        $check = $this->db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'pending_training'")->fetch();
        if ($check) return;

        $sqlFile = __DIR__ . '/../sql/aiml_tables.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            try {
                $this->db->exec($sql);
            } catch (\PDOException $e) {
                error_log("AIML Schema Init Error: " . $e->getMessage());
            }
        }
    }
}
