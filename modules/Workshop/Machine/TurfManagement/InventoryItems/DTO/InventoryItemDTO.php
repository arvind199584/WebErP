<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\InventoryItems\DTO;

class InventoryItemDTO
{
    public ?int $id;
    public string $description;
    public string $ac_unit;
    public ?int $category_id;
    public ?string $category_name;
    public float $current_stock;

    public function __construct(string $description, string $ac_unit, ?int $id = null, ?int $category_id = null, ?string $category_name = null, float $current_stock = 0.0)
    {
        $this->description = $description;
        $this->ac_unit = $ac_unit;
        $this->id = $id;
        $this->category_id = $category_id;
        $this->category_name = $category_name;
        $this->current_stock = $current_stock;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'ac_unit' => $this->ac_unit,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'current_stock' => $this->current_stock,
        ];
    }
}
