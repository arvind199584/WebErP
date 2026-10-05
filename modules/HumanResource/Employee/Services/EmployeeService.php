<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Employee\Services;

use App\Modules\HumanResource\Employee\DTO\EmployeeDTO;
use App\Modules\HumanResource\Employee\Models\EmployeeModel;
use App\Core\Database;
use PDO;

require_once __DIR__ . '/../Models/EmployeeModel.php';
require_once __DIR__ . '/../DTO/EmployeeDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class EmployeeService {
    private EmployeeModel $employeeModel;
    private PDO $db;

    public function __construct() {
        $this->employeeModel = new EmployeeModel();
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllEmployees(): array { return $this->employeeModel->findAll(); }
    public function getEmployeesByAgreement(int $agreementId): array { return $this->employeeModel->findByAgreement($agreementId); }
    public function createEmployee(EmployeeDTO $dto): int { return $this->employeeModel->create($dto); }

    public function batchCreateEmployees(array $dtos): array {
        $success = 0;
        $errors = [];
        foreach ($dtos as $index => $dto) {
            try {
                $this->createEmployee($dto);
                $success++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        return ['success' => $success, 'errors' => $errors];
    }

    public function deleteEmployee(int $id): bool {
        return $this->employeeModel->delete($id);
    }

    public function getEmployeeById(int $id): ?array {
        return $this->employeeModel->findById($id);
    }

    public function updateEmployee(int $id, EmployeeDTO $dto): bool {
        return $this->employeeModel->update($id, $dto);
    }

    public function inheritEmployees(int $sourceAgreementId, int $targetAgreementId): array {
        $sourceEmployees = $this->getEmployeesByAgreement($sourceAgreementId);
        $success = 0;
        $errors = [];

        foreach ($sourceEmployees as $emp) {
            try {
                $dto = new EmployeeDTO(
                    (int)$emp['officeid'],
                    $targetAgreementId,
                    $emp['full_name'],
                    $emp['designation'],
                    $emp['joining_date'],
                    $emp['leaving_date'],
                    $emp['default_rest_day'],
                    (bool)$emp['is_reliever'],
                    $emp['account_no'],
                    $emp['ifsc'],
                    $emp['bank_name'],
                    $emp['uan_no'],
                    $emp['esic_no'],
                    (int)$emp['id'], // inherited_from_id
                    null, // id
                    $emp['deployed_office_id'] ? (int)$emp['deployed_office_id'] : null
                );
                $this->createEmployee($dto);
                $success++;
            } catch (\Exception $e) {
                $errors[] = $emp['full_name'] . ": " . $e->getMessage();
            }
        }
        return ['success' => $success, 'errors' => $errors];
    }

    public function deleteAllByAgreement(int $agreementId): bool {
        $sql = "DELETE FROM employees WHERE agreement_id = :agreementId";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['agreementId' => $agreementId]);
    }

    public function nameExists(string $name, int $agreementId): bool {
        $sql = "SELECT COUNT(*) FROM employees WHERE full_name = :name AND agreement_id = :agreementId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $name, 'agreementId' => $agreementId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
