<?php

declare(strict_types=1);

namespace App\Modules\Finance\Budget\Services;

use App\Modules\Finance\Budget\DTO\BudgetDTO;
use App\Modules\Finance\Budget\Models\BudgetModel;
use App\Core\Database;

require_once __DIR__ . '/../Models/BudgetModel.php';
require_once __DIR__ . '/../DTO/BudgetDTO.php';
require_once __DIR__ . '/../../../../core/Database.php';

class BudgetService
{
    private BudgetModel $budgetModel;

    public function __construct()
    {
        $this->budgetModel = new BudgetModel();
    }

    public function getAllBudgets(): array { return $this->budgetModel->findAll(); }

    public function getBudgetById(int $id): ?BudgetDTO {
        $data = $this->budgetModel->findById($id);
        if (!$data) return null;
        return new BudgetDTO((int)$data['officeid'], $data['fy'], $data['code'], (float)$data['provision'], $data['name_of_work'], (int)$data['id']);
    }

    /**
     * Resolves the correct Budget ID based on Code, Date, and Office.
     */
    public function resolveBudgetId(string $code, string $date, int $officeId): int {
        $dt = new \DateTime($date);
        $month = (int)$dt->format('m');
        $year = (int)$dt->format('Y');

        // Calculate FY (e.g., 2025-26)
        $fyStartYear = ($month <= 3) ? $year - 1 : $year;
        $fy = $fyStartYear . '-' . substr((string)($fyStartYear + 1), 2);

        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id FROM budget WHERE code = :code AND fy = :fy AND officeid = :oid";
        $stmt = $db->prepare($sql);
        $stmt->execute(['code' => $code, 'fy' => $fy, 'oid' => $officeId]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            throw new \Exception("Budget provision for Code '$code' not found for Financial Year $fy.");
        }

        return (int)$id;
    }

    public function createBudget(BudgetDTO $dto): int { return $this->budgetModel->create($dto); }
    public function updateBudget(BudgetDTO $dto): bool { return $this->budgetModel->update($dto); }
    public function deleteBudget(int $id): bool { return $this->budgetModel->delete($id); }
    public function getCurrentFY(): string { return $this->budgetModel->getCurrentFinancialYear(); }

    public function getAvailableBudget(int $budgetid): float {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT provision * 100000 FROM budget WHERE id = :id");
        $stmt->bindValue(':id', $budgetid);
        $stmt->execute();
        $provision = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(aa_es_amount), 0) FROM aa_es WHERE budgetid = :id");
        $stmt->bindValue(':id', $budgetid);
        $stmt->execute();
        $used = (float)$stmt->fetchColumn();

        return $provision - $used;
    }
}
