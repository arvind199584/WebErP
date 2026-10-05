<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Employee\Models;

use App\Core\Database;
use App\Modules\HumanResource\Employee\DTO\EmployeeDTO;
use PDO;

class EmployeeModel {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function findAll(): array {
        $sql = "SELECT e.*, a.agreement_no, o.OfficeName as deployed_office_name
                FROM employees e
                JOIN agreements a ON e.agreement_id = a.id
                LEFT JOIN office o ON e.deployed_office_id = o.Officeid
                ORDER BY e.full_name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByAgreement(int $agreementId): array {
        $sql = "SELECT e.*, o.OfficeName as deployed_office_name
                FROM employees e
                LEFT JOIN office o ON e.deployed_office_id = o.Officeid
                WHERE e.agreement_id = :agreementId
                ORDER BY e.is_reliever ASC, e.designation ASC, e.full_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':agreementId', $agreementId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(EmployeeDTO $dto): int {
        $sql = "INSERT INTO employees (
                    officeid, agreement_id, full_name, designation,
                    account_no, ifsc, bank_name, uan_no, esic_no,
                    joining_date, leaving_date, default_rest_day, is_reliever,
                    inherited_from_id, deployed_office_id
                ) VALUES (
                    :officeid, :agreement_id, :full_name, :designation,
                    :account_no, :ifsc, :bank_name, :uan_no, :esic_no,
                    :joining_date, :leaving_date, :default_rest_day, :is_reliever,
                    :inherited_from_id, :deployed_office_id
                )";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid);
        $stmt->bindValue(':agreement_id', $dto->agreement_id);
        $stmt->bindValue(':full_name', $dto->full_name);
        $stmt->bindValue(':designation', $dto->designation);
        $stmt->bindValue(':account_no', $dto->account_no);
        $stmt->bindValue(':ifsc', $dto->ifsc);
        $stmt->bindValue(':bank_name', $dto->bank_name);
        $stmt->bindValue(':uan_no', $dto->uan_no);
        $stmt->bindValue(':esic_no', $dto->esic_no);
        $stmt->bindValue(':joining_date', $dto->joining_date);
        $stmt->bindValue(':leaving_date', $dto->leaving_date);
        $stmt->bindValue(':default_rest_day', $dto->default_rest_day);
        $stmt->bindValue(':is_reliever', $dto->is_reliever, PDO::PARAM_BOOL);
        $stmt->bindValue(':inherited_from_id', $dto->inherited_from_id, PDO::PARAM_INT);
        $stmt->bindValue(':deployed_office_id', $dto->deployed_office_id, $dto->deployed_office_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM employees WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM employees WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function update(int $id, EmployeeDTO $dto): bool {
        $sql = "UPDATE employees SET
                    full_name = :full_name,
                    designation = :designation,
                    account_no = :account_no,
                    ifsc = :ifsc,
                    bank_name = :bank_name,
                    uan_no = :uan_no,
                    esic_no = :esic_no,
                    joining_date = :joining_date,
                    leaving_date = :leaving_date,
                    default_rest_day = :default_rest_day,
                    is_reliever = :is_reliever,
                    deployed_office_id = :deployed_office_id
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':full_name', $dto->full_name);
        $stmt->bindValue(':designation', $dto->designation);
        $stmt->bindValue(':account_no', $dto->account_no);
        $stmt->bindValue(':ifsc', $dto->ifsc);
        $stmt->bindValue(':bank_name', $dto->bank_name);
        $stmt->bindValue(':uan_no', $dto->uan_no);
        $stmt->bindValue(':esic_no', $dto->esic_no);
        $stmt->bindValue(':joining_date', $dto->joining_date);
        $stmt->bindValue(':leaving_date', $dto->leaving_date);
        $stmt->bindValue(':default_rest_day', $dto->default_rest_day);
        $stmt->bindValue(':is_reliever', $dto->is_reliever, PDO::PARAM_BOOL);
        $stmt->bindValue(':deployed_office_id', $dto->deployed_office_id, $dto->deployed_office_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
