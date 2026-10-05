<?php
declare(strict_types=1);
namespace App\Modules\Admin\WorkOrder\Services;

use App\Modules\Admin\WorkOrder\DTO\WorkOrderDTO;
use App\Modules\Admin\WorkOrder\Models\WorkOrderModel;

require_once __DIR__ . '/../Models/WorkOrderModel.php';
require_once __DIR__ . '/../DTO/WorkOrderDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class WorkOrderService {
    private WorkOrderModel $workOrderModel;
    public function __construct() { $this->workOrderModel = new WorkOrderModel(); }
    public function getAllWorkOrders(): array { return $this->workOrderModel->findAll(); }
    public function createWorkOrder(WorkOrderDTO $dto): int { return $this->workOrderModel->create($dto); }
}
