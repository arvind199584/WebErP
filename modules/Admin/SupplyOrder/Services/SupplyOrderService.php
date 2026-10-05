<?php
declare(strict_types=1);
namespace App\Modules\Admin\SupplyOrder\Services;

use App\Modules\Admin\SupplyOrder\DTO\SupplyOrderDTO;
use App\Modules\Admin\SupplyOrder\Models\SupplyOrderModel;

require_once __DIR__ . '/../Models/SupplyOrderModel.php';
require_once __DIR__ . '/../DTO/SupplyOrderDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class SupplyOrderService {
    private SupplyOrderModel $supplyOrderModel;
    public function __construct() { $this->supplyOrderModel = new SupplyOrderModel(); }
    public function getAllSupplyOrders(): array { return $this->supplyOrderModel->findAll(); }
    public function createSupplyOrder(SupplyOrderDTO $dto): int { return $this->supplyOrderModel->create($dto); }
}
