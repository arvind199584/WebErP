<?php
namespace App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Services;

require_once __DIR__ . '/../Models/InventoryItemModel.php';
require_once __DIR__ . '/../DTO/InventoryItemDTO.php';

use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\Models\InventoryItemModel;
use App\Modules\Workshop\Machine\TurfManagement\InventoryItems\DTO\InventoryItemDTO;
use Exception;

class InventoryItemService {
    private $inventoryItemModel;

    public function __construct() {
        $this->inventoryItemModel = new InventoryItemModel();
    }

    /**
     * @return InventoryItemDTO[]
     */
    public function getAllItems(?int $officeId = null): array {
        $itemsData = $this->inventoryItemModel->getAllItems($officeId);
        $dtos = [];
        foreach ($itemsData as $data) {
            $dtos[] = new InventoryItemDTO(
                $data['description'],
                $data['ac_unit'],
                (int)$data['id'],
                $data['category_id'] !== null ? (int)$data['category_id'] : null,
                $data['category_name'] ?? null,
                (float)($data['current_stock'] ?? 0)
            );
        }
        return $dtos;
    }

    /**
     * @return InventoryItemDTO[]
     */
    public function getFuelItems(): array {
        $itemsData = $this->inventoryItemModel->getFuelItems();
        $dtos = [];
        foreach ($itemsData as $data) {
            $dtos[] = new InventoryItemDTO(
                $data['description'],
                $data['ac_unit'],
                (int)$data['id'],
                $data['category_id'] !== null ? (int)$data['category_id'] : null,
                $data['category_name'] ?? null
            );
        }
        return $dtos;
    }

    public function getAllCategories(): array {
        return $this->inventoryItemModel->getAllCategories();
    }

    public function getItemById(int $id): ?InventoryItemDTO {
        $data = $this->inventoryItemModel->getItemById($id);
        if (!$data) {
            return null;
        }
        return new InventoryItemDTO(
            $data['description'],
            $data['ac_unit'],
            (int)$data['id'],
            $data['category_id'] !== null ? (int)$data['category_id'] : null,
            $data['category_name'] ?? null
        );
    }

    public function createItem(InventoryItemDTO $dto): int {
        if (empty(trim($dto->description)) || empty(trim($dto->ac_unit))) {
            throw new Exception("Description and Unit are required.");
        }

        $data = [
            'description' => $dto->description,
            'ac_unit' => $dto->ac_unit,
            'category_id' => $dto->category_id
        ];
        return $this->inventoryItemModel->createItem($data);
    }

    public function updateItem(InventoryItemDTO $dto): bool {
        if ($dto->id === null) {
            throw new Exception("ID is required for updating.");
        }
        if (empty(trim($dto->description)) || empty(trim($dto->ac_unit))) {
            throw new Exception("Description and Unit are required.");
        }

        $data = [
            'description' => $dto->description,
            'ac_unit' => $dto->ac_unit,
            'category_id' => $dto->category_id
        ];
        return $this->inventoryItemModel->updateItem($dto->id, $data);
    }

    public function deleteItem(int $id): bool {
        return $this->inventoryItemModel->deleteItem($id);
    }
}
