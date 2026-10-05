<?php

declare(strict_types=1);

namespace App\Modules\Finance\Budget\DTO;

class BudgetDTO
{
    public ?int $id;
    public int $officeid;
    public string $fy;
    public string $code;
    public float $provision;
    public string $name_of_work;

    public function __construct(
        int $officeid,
        string $fy,
        string $code,
        float $provision,
        string $name_of_work,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->officeid = $officeid;
        $this->fy = $fy;
        $this->code = $code;
        $this->provision = $provision;
        $this->name_of_work = $name_of_work;
    }

    public static function fromRequest(array $data, int $currentOfficeId): self
    {
        return new self(
            // If superuser provides officeid, use it; otherwise use current user's office
            isset($data['officeid']) && !empty($data['officeid']) ? (int)$data['officeid'] : $currentOfficeId,
            $data['fy'],
            $data['code'],
            (float)$data['provision'],
            $data['name_of_work'],
            isset($data['id']) ? (int)$data['id'] : null
        );
    }
}
