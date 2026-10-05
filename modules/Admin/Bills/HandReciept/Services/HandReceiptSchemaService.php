<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\HandReciept\Services;

use App\Core\Database;
use PDO;

class HandReceiptSchemaService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function initSchema(): void {
        // Check if table already exists to avoid unnecessary execution
        $check = $this->db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'hand_receipts'")->fetch();
        if ($check) return;

        $sqlFile = __DIR__ . '/../sql/hand_receipts.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            try {
                $this->db->exec($sql);
            } catch (\PDOException $e) {
                // Log error but don't crash if it's just a duplicate type/table
                error_log("HandReceipt Schema Init: " . $e->getMessage());
            }
        }
    }
}
