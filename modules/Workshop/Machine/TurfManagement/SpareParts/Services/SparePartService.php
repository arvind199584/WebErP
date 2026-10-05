<?php
namespace App\Modules\Workshop\Machine\TurfManagement\SpareParts\Services;

require_once __DIR__ . '/../Models/SparePartModel.php';

use App\Modules\Workshop\Machine\TurfManagement\SpareParts\Models\SparePartModel;

class SparePartService {
    protected $sparePartModel;

    public function __construct() {
        $this->sparePartModel = new SparePartModel();
    }

    public function getSparePartsByMachine(int $machineId, ?int $officeId): array {
        return $this->sparePartModel->getSparePartsByMachine($machineId, $officeId);
    }

    public function getAllSpareParts(?int $officeId, ?int $machineId = null): array {
        return $this->sparePartModel->getAllSpareParts($officeId, $machineId);
    }

    public function createSparePart(array $data, int $officeId): int {
        $data['officeid'] = $officeId;
        return $this->sparePartModel->createSparePart($data);
    }
}
