<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Agency\Services;

use App\Modules\Superadmin\Agency\DTO\AgencyDTO;
use App\Modules\Superadmin\Agency\Models\AgencyModel;

// Manual includes for demonstration. Use a proper autoloader in a real app.
require_once __DIR__ . '/../Models/AgencyModel.php';
require_once __DIR__ . '/../DTO/AgencyDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class AgencyService
{
    private AgencyModel $agencyModel;

    public function __construct()
    {
        $this->agencyModel = new AgencyModel();
    }

    public function getAllAgencies(): array
    {
        return $this->agencyModel->findAll();
    }

    public function getAgencyById(int $id): ?AgencyDTO
    {
        $data = $this->agencyModel->findById($id);
        if (!$data) {
            return null;
        }
        // Map array to DTO manually since DTO constructor expects specific types
        return new AgencyDTO(
            $data['name'],
            $data['account_no'],
            $data['ifsc'],
            $data['bank_name'],
            $data['gst_no'],
            $data['pan_no'],
            $data['address'],
            $data['contact_person'],
            $data['email'],
            (int)$data['id']
        );
    }

    public function createAgency(AgencyDTO $dto): int
    {
        return $this->agencyModel->create($dto);
    }

    public function updateAgency(AgencyDTO $dto): bool
    {
        return $this->agencyModel->update($dto);
    }

    public function deleteAgency(int $id): bool
    {
        return $this->agencyModel->delete($id);
    }
}
