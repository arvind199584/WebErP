<?php

declare(strict_types=1);

namespace App\Modules\Admin\AA_ES\Models;

use App\Core\Database;
use App\Modules\Admin\AA_ES\DTO\AA_ES_DTO;
use PDO;

class AA_ES_Model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT a.*, b.code as budget_code, b.fy
                FROM aa_es a
                JOIN budget b ON a.budgetid = b.id
                ORDER BY a.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM aa_es WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(AA_ES_DTO $dto): int
    {
        $sql = "INSERT INTO aa_es (officeid, budgetid, sub_head, alias, type, boq, status)
                VALUES (:officeid, :budgetid, :sub_head, :alias, :type, :boq, :status)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':officeid', $dto->officeid, PDO::PARAM_INT);
        $stmt->bindValue(':budgetid', $dto->budgetid, PDO::PARAM_INT);
        $stmt->bindValue(':sub_head', $dto->sub_head);
        $stmt->bindValue(':alias', $dto->alias);
        $stmt->bindValue(':type', $dto->type);
        $stmt->bindValue(':boq', json_encode($dto->boq));
        $stmt->bindValue(':status', $dto->status);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(AA_ES_DTO $dto): bool
    {
        $sql = "UPDATE aa_es SET
                    budgetid = :budgetid,
                    sub_head = :sub_head,
                    alias = :alias,
                    type = :type,
                    boq = :boq,
                    status = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':budgetid', $dto->budgetid, PDO::PARAM_INT);
        $stmt->bindValue(':sub_head', $dto->sub_head);
        $stmt->bindValue(':alias', $dto->alias);
        $stmt->bindValue(':type', $dto->type);
        $stmt->bindValue(':boq', json_encode($dto->boq));
        $stmt->bindValue(':status', $dto->status);
        $stmt->bindValue(':id', $dto->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM aa_es WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function approve(int $id, string $pdfPath): bool
    {
        $sql = "UPDATE aa_es SET
                    status = 'Approved',
                    approval_pdf = :pdf,
                    approved_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pdf', $pdfPath); // Now storing string path
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function revertToDraft(int $id): bool
    {
        $sql = "UPDATE aa_es SET
                    status = 'Draft',
                    approval_pdf = NULL,
                    approved_at = NULL
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
