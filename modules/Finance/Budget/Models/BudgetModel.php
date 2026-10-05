<?php

declare(strict_types=1);

namespace App\Modules\Finance\Budget\Models;

use App\Core\Database;
use App\Modules\Finance\Budget\DTO\BudgetDTO;
use PDO;

class BudgetModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Fetches all budget entries.
     * RLS ensures users only see their own office's data.
     */
    public function findAll(): array
    {
        $sql = "SELECT b.*, o.OfficeName
                FROM budget b
                JOIN office o ON b.officeid = o.Officeid
                ORDER BY b.fy DESC, b.code ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM budget WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(BudgetDTO $dto): int
    {
        $sql = "INSERT INTO budget (officeid, fy, code, provision, name_of_work)
                VALUES (:officeid, :fy, :code, :provision, :name_of_work)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid, PDO::PARAM_INT);
        $stmt->bindValue(':fy', $dto->fy);
        $stmt->bindValue(':code', $dto->code);
        $stmt->bindValue(':provision', $dto->provision);
        $stmt->bindValue(':name_of_work', $dto->name_of_work);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(BudgetDTO $dto): bool
    {
        $sql = "UPDATE budget SET
                    officeid = :officeid,
                    fy = :fy,
                    code = :code,
                    provision = :provision,
                    name_of_work = :name_of_work
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid, PDO::PARAM_INT);
        $stmt->bindValue(':fy', $dto->fy);
        $stmt->bindValue(':code', $dto->code);
        $stmt->bindValue(':provision', $dto->provision);
        $stmt->bindValue(':name_of_work', $dto->name_of_work);
        $stmt->bindValue(':id', $dto->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM budget WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Helper to get the current Financial Year from the database function.
     */
    public function getCurrentFinancialYear(): string
    {
        $stmt = $this->db->query("SELECT get_financial_year(CURRENT_DATE)");
        return $stmt->fetchColumn();
    }
}
