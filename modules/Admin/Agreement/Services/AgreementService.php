<?php

declare(strict_types=1);

namespace App\Modules\Admin\Agreement\Services;

use App\Modules\Admin\Agreement\DTO\AgreementDTO;
use App\Modules\Admin\Agreement\Models\AgreementModel;

require_once __DIR__ . '/../Models/AgreementModel.php';
require_once __DIR__ . '/../DTO/AgreementDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class AgreementService
{
    private AgreementModel $agreementModel;

    public function __construct()
    {
        $this->agreementModel = new AgreementModel();
    }

    public function getAllAgreements(): array
    {
        return $this->agreementModel->findAll();
    }

    public function findActiveAgreements(): array
    {
        return $this->agreementModel->findActiveAgreements();
    }

    public function getAgreementById(int $id): ?AgreementDTO
    {
        $data = $this->agreementModel->findById($id);
        if (!$data) {
            return null;
        }

        return new AgreementDTO(
            (int)$data['officeid'],
            $data['agreement_no'],
            (int)$data['agency_id'],
            (int)$data['aa_es_id'],
            (float)$data['service_charge_percent'],
            (float)$data['tendered_amount'],
            $data['period_from'],
            $data['period_to'],
            $data['status'],
            (bool)$data['bill_type_esic'],
            (bool)$data['bill_type_epf'],
            $data['scope'] ? json_decode($data['scope'], true) : null,
            (int)$data['id'],
            $data['predecessor_id'] ? (int)$data['predecessor_id'] : null
        );
    }

    public function createAgreement(AgreementDTO $dto): int
    {
        return $this->agreementModel->create($dto);
    }

    public function updateScope(int $agreementId, string $newScopeDate, array $newItems): void
    {
        $this->agreementModel->updateScope($agreementId, $newScopeDate, $newItems);
    }
}
