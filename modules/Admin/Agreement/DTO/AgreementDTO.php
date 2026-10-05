<?php

declare(strict_types=1);

namespace App\Modules\Admin\Agreement\DTO;

class AgreementDTO
{
    public ?int $id;
    public int $officeid;
    public string $agreement_no;
    public int $agency_id;
    public int $aa_es_id;
    public float $service_charge_percent;
    public float $tendered_amount;
    public string $period_from;
    public string $period_to;
    public string $status;
    public bool $bill_type_esic;
    public bool $bill_type_epf;
    public ?array $scope;
    public ?int $predecessor_id;

    public function __construct(
        int $officeid,
        string $agreement_no,
        int $agency_id,
        int $aa_es_id,
        float $service_charge_percent,
        float $tendered_amount,
        string $period_from,
        string $period_to,
        string $status = 'Active',
        bool $bill_type_esic = false,
        bool $bill_type_epf = false,
        ?array $scope = null,
        ?int $id = null,
        ?int $predecessor_id = null
    ) {
        $this->id = $id;
        $this->officeid = $officeid;
        $this->agreement_no = $agreement_no;
        $this->agency_id = $agency_id;
        $this->aa_es_id = $aa_es_id;
        $this->service_charge_percent = $service_charge_percent;
        $this->tendered_amount = $tendered_amount;
        $this->period_from = $period_from;
        $this->period_to = $period_to;
        $this->status = $status;
        $this->bill_type_esic = $bill_type_esic;
        $this->bill_type_epf = $bill_type_epf;
        $this->scope = $scope;
        $this->predecessor_id = $predecessor_id;
    }

    public static function fromRequest(array $data, int $currentOfficeId): self
    {
        $scope = !empty($data['scope']) ? json_decode($data['scope'], true) : null;

        return new self(
            $currentOfficeId,
            $data['agreement_no'],
            (int)$data['agency_id'],
            (int)$data['aa_es_id'],
            (float)$data['service_charge_percent'],
            (float)$data['tendered_amount'],
            $data['period_from'],
            $data['period_to'],
            $data['status'] ?? 'Active',
            isset($data['bill_type_esic']),
            isset($data['bill_type_epf']),
            $scope,
            isset($data['id']) ? (int)$data['id'] : null,
            !empty($data['predecessor_id']) ? (int)$data['predecessor_id'] : null
        );
    }
}
