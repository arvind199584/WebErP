<?php
declare(strict_types=1);
namespace App\Modules\Admin\Agreement\Services;

use App\Core\Database;
use PDO;

class AgreementScopeService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    /**
     * Recalculates the effective scope of an agreement by merging the original AA_ES
     * with all approved Deviations and Extra Items.
     */
    public function updateAgreementScope(int $agreementId): void {
        // 1. Fetch Original AA_ES BOQ
        $sql = "SELECT ae.boq, ae.type FROM agreements a JOIN aa_es ae ON a.aa_es_id = ae.id WHERE a.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $agreementId]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        $effectiveBOQ = json_decode($original['boq'], true);
        $type = $original['type'];

        // 2. Apply Approved Deviations (Quantity Changes)
        $sqlDev = "SELECT boq FROM deviations WHERE agreement_id = :id AND status = 'Approved'";
        $stmtDev = $this->db->prepare($sqlDev);
        $stmtDev->execute(['id' => $agreementId]);
        $deviations = $stmtDev->fetchAll(PDO::FETCH_ASSOC);

        foreach ($deviations as $dev) {
            $devBOQ = json_decode($dev['boq'], true);
            $effectiveBOQ = $this->mergeDeviations($effectiveBOQ, $devBOQ, $type);
        }

        // 3. Apply Approved Extra Items (New Items)
        $sqlExtra = "SELECT boq FROM extra_items WHERE agreement_id = :id AND status = 'Approved'";
        $stmtExtra = $this->db->prepare($sqlExtra);
        $stmtExtra->execute(['id' => $agreementId]);
        $extras = $stmtExtra->fetchAll(PDO::FETCH_ASSOC);

        foreach ($extras as $extra) {
            $extraBOQ = json_decode($extra['boq'], true);
            $effectiveBOQ = $this->mergeExtraItems($effectiveBOQ, $extraBOQ, $type);
        }

        // 4. Update the Agreement with the new Effective BOQ
        // Assuming we add an 'effective_boq' column to agreements
        $sqlUpdate = "UPDATE agreements SET effective_boq = :boq WHERE id = :id";
        $stmtUpdate = $this->db->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'boq' => json_encode($effectiveBOQ),
            'id' => $agreementId
        ]);
    }

    private function mergeDeviations(array $base, array $dev, string $type): array {
        // Logic to update quantities of existing items
        return $base; // Placeholder for complex merge logic
    }

    private function mergeExtraItems(array $base, array $extra, string $type): array {
        // Logic to append new items to the BOQ
        return $base; // Placeholder for complex merge logic
    }
}
