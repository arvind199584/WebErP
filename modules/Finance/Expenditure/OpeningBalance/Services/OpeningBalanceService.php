<?php
declare(strict_types=1);
namespace App\Modules\Finance\Expenditure\OpeningBalance\Services;

require_once __DIR__ . '/../../../../../core/Database.php';

use App\Core\Database;
use PDO;

class OpeningBalanceService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function getAgreementsForOB(): array {
        $sql = "
            SELECT a.id, a.agreement_no, ob.id as ob_id, ob.updated_at
            FROM agreements a
            LEFT JOIN opening_balances ob ON a.id = ob.agreement_id
            WHERE a.status IN ('Active', 'Expired')
            ORDER BY a.agreement_no;
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOBByAgreementId(int $agreementId): ?array {
        $sql = "SELECT * FROM opening_balances WHERE agreement_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $agreementId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // CORRECTED: Explicitly return null if fetch returns false
        return $result === false ? null : $result;
    }

    public function updateOB(array $data): void {
        $sql = "
            INSERT INTO opening_balances (
                agreement_id, officeid, items_paid, gst_paid, esic_paid, epf_paid,
                items_upto, gst_upto, esic_upto, epf_upto, itemwise_expenditure, updated_at
            ) VALUES (
                :agreement_id, :officeid, :items_paid, :gst_paid, :esic_paid, :epf_paid,
                :items_upto, :gst_upto, :esic_upto, :epf_upto, :itemwise_expenditure, CURRENT_TIMESTAMP
            )
            ON CONFLICT (agreement_id) DO UPDATE SET
                items_paid = EXCLUDED.items_paid,
                gst_paid = EXCLUDED.gst_paid,
                esic_paid = EXCLUDED.esic_paid,
                epf_paid = EXCLUDED.epf_paid,
                items_upto = EXCLUDED.items_upto,
                gst_upto = EXCLUDED.gst_upto,
                esic_upto = EXCLUDED.esic_upto,
                epf_upto = EXCLUDED.epf_upto,
                itemwise_expenditure = EXCLUDED.itemwise_expenditure,
                updated_at = CURRENT_TIMESTAMP
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'agreement_id' => $data['agreement_id'],
            'officeid' => $data['officeid'],
            'items_paid' => $data['items_paid'],
            'gst_paid' => $data['gst_paid'],
            'esic_paid' => $data['esic_paid'],
            'epf_paid' => $data['epf_paid'],
            'items_upto' => $data['items_upto'] ?: null,
            'gst_upto' => $data['gst_upto'] ?: null,
            'esic_upto' => $data['esic_upto'] ?: null,
            'epf_upto' => $data['epf_upto'] ?: null,
            'itemwise_expenditure' => isset($data['itemwise_expenditure']) ? json_encode($data['itemwise_expenditure']) : null
        ]);
    }

    public function getBoqForAgreement(int $agreementId): array {
        $sql = "
            SELECT ae.boq
            FROM agreements a
            JOIN aa_es ae ON a.aa_es_id = ae.id
            WHERE a.id = :agreement_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['agreement_id' => $agreementId]);
        $boqJson = $stmt->fetchColumn();
        return $boqJson ? json_decode($boqJson, true) : [];
    }
}
