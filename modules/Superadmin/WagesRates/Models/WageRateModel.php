<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Models;

use App\Core\Database;
use App\Modules\Superadmin\WagesRates\DTO\WageOrderDTO;
use PDO;

class WageRateModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Fetches all wage orders, ordered by effective date descending.
     */
    public function getAllOrders(): array
    {
        $sql = "SELECT * FROM wage_orders ORDER BY valid_from DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches a single wage order by ID.
     */
    public function getOrderById(int $id): ?array
    {
        $sql = "SELECT * FROM wage_orders WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Adds a new wage order using the stored procedure.
     * This automatically handles closing the previous order.
     */
    public function addOrder(WageOrderDTO $dto): void
    {
        $sql = "SELECT add_wage_order(:authority, :letter_no, :letter_date, :valid_from, :rates)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':authority', $dto->authority);
        $stmt->bindValue(':letter_no', $dto->letter_no);
        $stmt->bindValue(':letter_date', $dto->letter_date);
        $stmt->bindValue(':valid_from', $dto->valid_from);
        $stmt->bindValue(':rates', json_encode($dto->rates)); // Convert array to JSON string

        $stmt->execute();
    }

    /**
     * Fetches the current effective rates for all items using the view.
     */
    public function getCurrentRates(): array
    {
        $sql = "SELECT * FROM view_current_item_rates ORDER BY authority, item_name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all wage items (categories) for management.
     */
    public function getAllItems(): array
    {
        $sql = "SELECT * FROM wage_items ORDER BY authority, item_name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
