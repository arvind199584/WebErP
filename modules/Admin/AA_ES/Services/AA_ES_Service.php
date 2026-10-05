<?php

declare(strict_types=1);

namespace App\Modules\Admin\AA_ES\Services;

use App\Modules\Admin\AA_ES\DTO\AA_ES_DTO;
use App\Modules\Admin\AA_ES\Models\AA_ES_Model;
use App\Modules\Superadmin\Office\Services\OfficeService;

require_once __DIR__ . '/../Models/AA_ES_Model.php';
require_once __DIR__ . '/../DTO/AA_ES_DTO.php';
require_once __DIR__ . '/../../../Superadmin/Office/Services/OfficeService.php';
require_once __DIR__ . '/../../../../core/Database.php';

class AA_ES_Service
{
    private AA_ES_Model $aaEsModel;
    private string $uploadDir;

    public function __construct()
    {
        $this->aaEsModel = new AA_ES_Model();
        $this->uploadDir = __DIR__ . '/../../../storage/uploads/aa_es_approvals/';
    }

    public function getAllAA_ES(): array
    {
        return $this->aaEsModel->findAll();
    }

    public function getAA_ESById(int $id): ?AA_ES_DTO
    {
        $data = $this->aaEsModel->findById($id);
        if (!$data) {
            return null;
        }
        $boq = json_decode($data['boq'], true);

        return new AA_ES_DTO(
            (int)$data['officeid'],
            (int)$data['budgetid'],
            $data['sub_head'],
            $data['type'],
            $boq,
            $data['alias'],
            (float)$data['estimated_cost'],
            (float)$data['justified_amount'],
            (float)$data['aa_es_amount'],
            $data['status'],
            $data['approval_pdf'], // Now stores the path
            $data['approved_at'],
            (int)$data['id']
        );
    }

    public function createAA_ES(AA_ES_DTO $dto): int
    {
        if (empty($dto->alias)) {
            $dto->alias = $this->generateAlias($dto->sub_head, $dto->officeid);
        }
        return $this->aaEsModel->create($dto);
    }

    public function updateAA_ES(AA_ES_DTO $dto): bool
    {
        if (empty($dto->alias)) {
            $dto->alias = $this->generateAlias($dto->sub_head, $dto->officeid);
        }
        return $this->aaEsModel->update($dto);
    }

    public function deleteAA_ES(int $id): bool
    {
        // Delete associated PDF if exists
        $aa_es = $this->getAA_ESById($id);
        if ($aa_es && $aa_es->approval_pdf) {
            $filePath = $this->uploadDir . basename($aa_es->approval_pdf);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        return $this->aaEsModel->delete($id);
    }

    public function approveAA_ES(int $id, array $file): bool
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("File upload error.");
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Security Check: Only allow PDFs
        if ($extension !== 'pdf') {
            throw new \Exception("Security Error: Only PDF files are allowed for approvals.");
        }

        $filename = 'approval_' . $id . '_' . time() . '.' . $extension;
        $targetPath = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to save uploaded file.");
        }

        // Store relative path
        $relativePath = '/storage/uploads/aa_es_approvals/' . $filename;

        return $this->aaEsModel->approve($id, $relativePath);
    }

    public function revertAA_ES(int $id): bool
    {
        // Delete the PDF file
        $aa_es = $this->getAA_ESById($id);
        if ($aa_es && $aa_es->approval_pdf) {
            $filePath = $this->uploadDir . basename($aa_es->approval_pdf);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        return $this->aaEsModel->revertToDraft($id);
    }

    public function generateAlias(string $text, int $officeId = 0): string
    {
        $officeName = "";
        $officeCode = "";

        if ($officeId > 0) {
            $officeService = new OfficeService();
            $office = $officeService->getOfficeById($officeId);
            if ($office) {
                $officeName = $office->OfficeName;
                $officeCode = $office->OfficeCode;
            }
        }

        $scriptPath = __DIR__ . '/../scripts/generate_alias.py';
        $escapedText = escapeshellarg($text);
        $escapedOfficeName = escapeshellarg($officeName);
        $escapedOfficeCode = escapeshellarg($officeCode);

        $command = "python \"$scriptPath\" $escapedText $escapedOfficeName $escapedOfficeCode";
        $output = shell_exec($command);

        if ($output === null) {
            return substr($text, 0, 20) . '...';
        }

        return trim($output);
    }
}
