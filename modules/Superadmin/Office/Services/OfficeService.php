<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Office\Services;

use App\Modules\Superadmin\Office\DTO\OfficeDTO;
use App\Modules\Superadmin\Office\Models\OfficeModel;
use Exception;

require_once __DIR__ . '/../Models/OfficeModel.php';
require_once __DIR__ . '/../DTO/OfficeDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class OfficeService
{
    private OfficeModel $officeModel;

    public function __construct()
    {
        $this->officeModel = new OfficeModel();
    }

    public function getAllOffices(?string $searchTerm = null): array
    {
        $officesData = $this->officeModel->findAll($searchTerm);
        $officeDTOs = [];
        foreach ($officesData as $office) {
            $officeDTOs[] = new OfficeDTO(
                $office['officename'],
                $office['address'],
                $office['phone'],
                $office['email'],
                $office['contactperson'],
                $office['officeid'],
                $office['officecode'],
                (bool)($office['has_finance'] ?? false),
                (bool)($office['has_store'] ?? false),
                (bool)($office['has_workshop'] ?? false),
                (bool)($office['has_hr'] ?? true)
            );
        }
        return $officeDTOs;
    }

    public function getOfficeById(int $officeId): ?OfficeDTO
    {
        $office = $this->officeModel->findById($officeId);
        if (!$office) {
            return null;
        }

        return new OfficeDTO(
            $office['officename'],
            $office['address'],
            $office['phone'],
            $office['email'],
            $office['contactperson'],
            $office['officeid'],
            $office['officecode'],
            (bool)($office['has_finance'] ?? false),
            (bool)($office['has_store'] ?? false),
            (bool)($office['has_workshop'] ?? false),
            (bool)($office['has_hr'] ?? true)
        );
    }

    public function createOffice(OfficeDTO $officeDTO): OfficeDTO
    {
        $newId = $this->officeModel->create($officeDTO);

        if (!$newId) {
            throw new Exception("Failed to create office record.");
        }

        $createdOffice = $this->getOfficeById($newId);

        if (!$createdOffice) {
            throw new Exception("Office created but could not be retrieved.");
        }

        return $createdOffice;
    }

    public function updateOffice(OfficeDTO $officeDTO): bool
    {
        return $this->officeModel->update($officeDTO);
    }

    public function deleteOffice(int $officeId): bool
    {
        return $this->officeModel->delete($officeId);
    }
}
