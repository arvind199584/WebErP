<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Models;

use App\Core\Database;
use App\Modules\Superadmin\WagesRates\DTO\WageItemDTO;
use PDO;

class WageItemModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM wage_items ORDER BY authority, item_name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all items with their effective rate for a specific date.
     * Uses the get_effective_rate stored function.
     */
    public function findAllWithRates(string $date): array
    {
        $sql = "SELECT
                    id,
                    item_name,
                    authority,
                    get_effective_rate(:date, id) as current_wage
                FROM wage_items
                ORDER BY authority, item_name";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM wage_items WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(WageItemDTO $dto): int
    {
        $sql = "INSERT INTO wage_items (authority, item_name, json_key, unit, fixed_allowance)
                VALUES (:authority, :item_name, :json_key, :unit, :fixed_allowance)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':authority', $dto->authority);
        $stmt->bindValue(':item_name', $dto->item_name);
        $stmt->bindValue(':json_key', $dto->json_key);
        $stmt->bindValue(':unit', $dto->unit);
        $stmt->bindValue(':fixed_allowance', $dto->fixed_allowance);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(WageItemDTO $dto): bool
    {
        $sql = "UPDATE wage_items SET
                    authority = :authority,
                    item_name = :item_name,
                    json_key = :json_key,
                    unit = :unit,
                    fixed_allowance = :fixed_allowance
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':authority', $dto->authority);
        $stmt->bindValue(':item_name', $dto->item_name);
        $stmt->bindValue(':json_key', $dto->json_key);
        $stmt->bindValue(':unit', $dto->unit);
        $stmt->bindValue(':fixed_allowance', $dto->fixed_allowance);
        $stmt->bindValue(':id', $dto->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM wage_items WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
