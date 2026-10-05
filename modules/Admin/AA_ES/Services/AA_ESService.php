<?php
declare(strict_types=1);
namespace App\Modules\Admin\AA_ES\Services;

use App\Core\Database;
use PDO;

class AA_ESService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    /**
     * Fetches the Bill of Quantities (BOQ) for a given AA&ES record.
     */
    public function getAaEsBoq(int $aa_es_id): array {
        $stmt = $this->db->prepare("SELECT boq FROM aa_es WHERE id = :id");
        $stmt->execute(['id' => $aa_es_id]);
        $boqJson = $stmt->fetchColumn();

        if (!$boqJson) {
            return [];
        }

        return json_decode($boqJson, true);
    }
}
