<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\DTO;

class WageItemDTO
{
    public ?int $id;
    public string $authority;
    public string $item_name;
    public string $json_key;
    public string $unit;
    public float $fixed_allowance;

    public function __construct(
        string $authority,
        string $item_name,
        string $json_key,
        string $unit,
        float $fixed_allowance = 0.0,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->authority = $authority;
        $this->item_name = $item_name;
        $this->json_key = $json_key;
        $this->unit = $unit;
        $this->fixed_allowance = $fixed_allowance;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['authority'],
            $data['item_name'],
            $data['json_key'],
            $data['unit'],
            (float)($data['fixed_allowance'] ?? 0.0),
            isset($data['id']) ? (int)$data['id'] : null
        );
    }
}
