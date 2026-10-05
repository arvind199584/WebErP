<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\WaterLog\Models;

require_once __DIR__ . '/../../../../../../core/BaseModel.php';

use App\Core\BaseModel;
use App\Modules\Workshop\Machine\TurfManagement\WaterLog\DTO\WaterLogDTO;

class WaterLogModel extends BaseModel
{
    public function getDb(): \PDO
    {
        return $this->db;
    }

    public function findByDate(string $date, int $office_id): ?array
    {
        $sql = "SELECT * FROM Water_log WHERE date = :date AND office_id = :office_id AND deleted_at IS NULL";
        return $this->fetchRow($sql, ['date' => $date, 'office_id' => $office_id]);
    }

    public function getAll(int $office_id): array
    {
        $sql = "SELECT * FROM Water_log WHERE office_id = :office_id AND deleted_at IS NULL ORDER BY date DESC";
        return $this->fetchAll($sql, ['office_id' => $office_id]);
    }

    public function getById(int $id, int $office_id): ?array
    {
        $sql = "SELECT * FROM Water_log WHERE id = :id AND office_id = :office_id AND deleted_at IS NULL";
        return $this->fetchRow($sql, ['id' => $id, 'office_id' => $office_id]);
    }

    public function create(WaterLogDTO $dto): void
    {
        $sql = "INSERT INTO Water_log (office_id, date, morning_opening, morning_closing, evening_opening, evening_closing) VALUES (:office_id, :date, :morning_opening, :morning_closing, :evening_opening, :evening_closing)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'office_id' => $dto->office_id,
            'date' => $dto->date,
            'morning_opening' => $dto->morning_opening,
            'morning_closing' => $dto->morning_closing,
            'evening_opening' => $dto->evening_opening,
            'evening_closing' => $dto->evening_closing,
        ]);
    }

    public function update(WaterLogDTO $dto): void
    {
        $sql = "UPDATE Water_log SET date = :date, morning_opening = :morning_opening, morning_closing = :morning_closing, evening_opening = :evening_opening, evening_closing = :evening_closing WHERE id = :id AND office_id = :office_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $dto->id,
            'office_id' => $dto->office_id,
            'date' => $dto->date,
            'morning_opening' => $dto->morning_opening,
            'morning_closing' => $dto->morning_closing,
            'evening_opening' => $dto->evening_opening,
            'evening_closing' => $dto->evening_closing,
        ]);
    }

    public function delete(int $id, int $office_id): void
    {
        $sql = "UPDATE Water_log SET deleted_at = NOW() WHERE id = :id AND office_id = :office_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'office_id' => $office_id]);
    }
}
