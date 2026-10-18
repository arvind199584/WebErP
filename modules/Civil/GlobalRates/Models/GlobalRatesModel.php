<?php
declare(strict_types=1);

namespace App\Modules\Civil\GlobalRates\Models;

require_once __DIR__ . '/../../../../core/BaseModel.php';

use App\Core\BaseModel;
use PDO;

class GlobalRatesModel extends BaseModel
{
    public function searchRates(?string $query = null, ?string $category = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT resource_code, category, description, unit, dsr_base_rate, current_market_rate, notes, pdf_page, updated_at
                FROM civil_global_rates
                WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (resource_code ILIKE :q OR description ILIKE :q)";
            $params[':q'] = '%' . trim($query) . '%';
        }

        if (!empty($category) && $category !== 'All') {
            $sql .= " AND category = :cat";
            $params[':cat'] = $category;
        }

        $sql .= " ORDER BY resource_code ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countRates(?string $query = null, ?string $category = null): int
    {
        $sql = "SELECT COUNT(*) FROM civil_global_rates WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (resource_code ILIKE :q OR description ILIKE :q)";
            $params[':q'] = '%' . trim($query) . '%';
        }

        if (!empty($category) && $category !== 'All') {
            $sql .= " AND category = :cat";
            $params[':cat'] = $category;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getCategories(): array
    {
        $sql = "SELECT category, COUNT(*) as count FROM civil_global_rates GROUP BY category ORDER BY category";
        return $this->fetchAll($sql);
    }

    public function findByCode(string $code): ?array
    {
        return $this->fetchRow("SELECT * FROM civil_global_rates WHERE resource_code = :code", [':code' => $code]);
    }

    public function updateMarketRate(string $code, float $marketRate): bool
    {
        $stmt = $this->db->prepare("
            UPDATE civil_global_rates
            SET current_market_rate = :rate, updated_at = CURRENT_TIMESTAMP
            WHERE resource_code = :code
        ");
        return $stmt->execute([':rate' => $marketRate, ':code' => $code]);
    }

    public function getSummaryStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_resources,
                    COUNT(CASE WHEN category ILIKE '%material%' THEN 1 END) as total_materials,
                    COUNT(CASE WHEN category ILIKE '%labour%' THEN 1 END) as total_labour,
                    COUNT(CASE WHEN category ILIKE '%machinery%' OR category ILIKE '%plant%' THEN 1 END) as total_machinery,
                    COUNT(CASE WHEN current_market_rate != dsr_base_rate THEN 1 END) as total_overridden
                FROM civil_global_rates";
        return $this->fetchRow($sql) ?: [];
    }
}
