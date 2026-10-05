<?php

declare(strict_types=1);

namespace App\Modules\Admin\AA_ES\DTO;

class AA_ES_DTO
{
    public ?int $id;
    public int $officeid;
    public int $budgetid;
    public string $sub_head;
    public ?string $alias;
    public string $type;
    public array $boq;
    public float $estimated_cost;
    public float $justified_amount;
    public float $aa_es_amount;
    public string $status;
    public ?string $approval_pdf; // Binary data or path
    public ?string $approved_at;

    public function __construct(
        int $officeid,
        int $budgetid,
        string $sub_head,
        string $type,
        array $boq,
        ?string $alias = null,
        float $estimated_cost = 0.0,
        float $justified_amount = 0.0,
        float $aa_es_amount = 0.0,
        string $status = 'Draft',
        ?string $approval_pdf = null,
        ?string $approved_at = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->officeid = $officeid;
        $this->budgetid = $budgetid;
        $this->sub_head = $sub_head;
        $this->alias = $alias;
        $this->type = $type;
        $this->boq = $boq;
        $this->estimated_cost = $estimated_cost;
        $this->justified_amount = $justified_amount;
        $this->aa_es_amount = $aa_es_amount;
        $this->status = $status;
        $this->approval_pdf = $approval_pdf;
        $this->approved_at = $approved_at;
    }

    public static function fromRequest(array $data, int $currentOfficeId): self
    {
        $boq = is_string($data['boq']) ? json_decode($data['boq'], true) : $data['boq'];

        return new self(
            $currentOfficeId,
            (int)$data['budgetid'],
            $data['sub_head'],
            $data['type'],
            $boq,
            $data['alias'] ?? null,
            0.0,
            0.0,
            0.0,
            $data['status'] ?? 'Draft',
            null, // PDF handled separately
            null,
            isset($data['id']) ? (int)$data['id'] : null
        );
    }
}
