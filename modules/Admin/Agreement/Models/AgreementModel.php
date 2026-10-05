<?php

declare(strict_types=1);

namespace App\Modules\Admin\Agreement\Models;

use App\Core\Database;
use App\Modules\Admin\Agreement\DTO\AgreementDTO;
use PDO;

class AgreementModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT
                    a.id, a.agreement_no, a.status,
                    ag.name as agency_name,
                    ae.alias as aa_es_alias,
                    lower(a.period) as period_from,
                    upper(a.period) as period_to
                FROM agreements a
                JOIN agencies ag ON a.agency_id = ag.id
                JOIN aa_es ae ON a.aa_es_id = ae.id
                ORDER BY a.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT
                    a.*,
                    lower(a.period) as period_from,
                    upper(a.period) as period_to
                FROM agreements a WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findActiveAgreements(): array
    {
        $sql = "SELECT id, agreement_no, upper(period) as end_date 
                FROM agreements 
                WHERE status = 'Active' 
                ORDER BY agreement_no ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(AgreementDTO $dto): int
    {
        $sql = "SELECT create_agreement_with_inheritance(
                    :officeid, :agreement_no, :agency_id, :aa_es_id,
                    :service_charge_percent, :tendered_amount, daterange(:from, :to, '[]'),
                    :esic, :epf, :predecessor_id
                )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid, PDO::PARAM_INT);
        $stmt->bindValue(':agreement_no', $dto->agreement_no);
        $stmt->bindValue(':agency_id', $dto->agency_id, PDO::PARAM_INT);
        $stmt->bindValue(':aa_es_id', $dto->aa_es_id, PDO::PARAM_INT);
        $stmt->bindValue(':service_charge_percent', $dto->service_charge_percent);
        $stmt->bindValue(':tendered_amount', $dto->tendered_amount);
        $stmt->bindValue(':from', $dto->period_from);
        $stmt->bindValue(':to', $dto->period_to);
        $stmt->bindValue(':esic', $dto->bill_type_esic, PDO::PARAM_BOOL);
        $stmt->bindValue(':epf', $dto->bill_type_epf, PDO::PARAM_BOOL);
        $stmt->bindValue(':predecessor_id', $dto->predecessor_id, $dto->predecessor_id ? PDO::PARAM_INT : PDO::PARAM_NULL);

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function updateScope(int $agreementId, string $newScopeDate, array $newItems): void
    {
        $sql = "SELECT update_agreement_scope(:id, :date, :items)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':id', $agreementId, PDO::PARAM_INT);
        $stmt->bindValue(':date', $newScopeDate);
        $stmt->bindValue(':items', json_encode($newItems));

        $stmt->execute();
    }
}
