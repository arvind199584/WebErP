<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Services;

use App\Modules\Superadmin\WagesRates\DTO\WageItemDTO;
use App\Modules\Superadmin\WagesRates\Models\WageItemModel;

// Manual includes for demonstration. Use a proper autoloader in a real app.
require_once __DIR__ . '/../Models/WageItemModel.php';
require_once __DIR__ . '/../DTO/WageItemDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class WageItemService
{
    private WageItemModel $wageItemModel;

    public function __construct()
    {
        $this->wageItemModel = new WageItemModel();
    }

    public function getAllItems(): array
    {
        $itemsData = $this->wageItemModel->findAll();
        $items = [];
        foreach ($itemsData as $data) {
            $items[] = WageItemDTO::fromArray($data);
        }
        return $items;
    }

    public function getAllItemsWithRates(string $date): array
    {
        // Returns raw array with 'current_wage' field
        return $this->wageItemModel->findAllWithRates($date);
    }

    public function getItemById(int $id): ?WageItemDTO
    {
        $data = $this->wageItemModel->findById($id);
        if (!$data) {
            return null;
        }
        return WageItemDTO::fromArray($data);
    }

    public function createItem(WageItemDTO $dto): int
    {
        return $this->wageItemModel->create($dto);
    }

    public function updateItem(WageItemDTO $dto): bool
    {
        return $this->wageItemModel->update($dto);
    }

    public function deleteItem(int $id): bool
    {
        return $this->wageItemModel->delete($id);
    }
}
