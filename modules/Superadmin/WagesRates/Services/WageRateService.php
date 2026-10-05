<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Services;

use App\Modules\Superadmin\WagesRates\DTO\WageOrderDTO;
use App\Modules\Superadmin\WagesRates\Models\WageRateModel;

// Manual includes for demonstration. Use a proper autoloader in a real app.
require_once __DIR__ . '/../Models/WageRateModel.php';
require_once __DIR__ . '/../DTO/WageOrderDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class WageRateService
{
    private WageRateModel $wageRateModel;

    public function __construct()
    {
        $this->wageRateModel = new WageRateModel();
    }

    public function getAllOrders(): array
    {
        return $this->wageRateModel->getAllOrders();
    }

    public function getOrderById(int $id): ?WageOrderDTO
    {
        $data = $this->wageRateModel->getOrderById($id);
        if (!$data) {
            return null;
        }
        return WageOrderDTO::fromArray($data);
    }

    public function addOrder(WageOrderDTO $dto): void
    {
        $this->wageRateModel->addOrder($dto);
    }

    public function getCurrentRates(): array
    {
        return $this->wageRateModel->getCurrentRates();
    }

    public function getAllItems(): array
    {
        return $this->wageRateModel->getAllItems();
    }
}
