<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\WaterLog\DTO;

class WaterLogDTO
{
    public readonly ?int $id;
    public readonly int $office_id;
    public readonly string $date;
    public readonly ?float $morning_opening;
    public readonly ?float $morning_closing;
    public readonly ?float $evening_opening;
    public readonly ?float $evening_closing;

    public function __construct(
        ?int $id,
        int $office_id,
        string $date,
        ?float $morning_opening,
        ?float $morning_closing,
        ?float $evening_opening,
        ?float $evening_closing
    ) {
        $this->id = $id;
        $this->office_id = $office_id;
        $this->date = $date;
        $this->morning_opening = $morning_opening;
        $this->morning_closing = $morning_closing;
        $this->evening_opening = $evening_opening;
        $this->evening_closing = $evening_closing;
    }

    public function validate(): void
    {
        if ($this->morning_closing !== null && $this->morning_opening !== null) {
            if ($this->morning_closing < $this->morning_opening) {
                throw new \Exception("Morning closing reading cannot be less than opening reading.");
            }
        }
        if ($this->evening_closing !== null && $this->evening_opening !== null) {
            if ($this->evening_closing < $this->evening_opening) {
                throw new \Exception("Evening closing reading cannot be less than opening reading.");
            }
        }
    }

    public static function fromRequest(array $data, int $office_id): self
    {
        return new self(
            isset($data['id']) ? (int)$data['id'] : null,
            $office_id,
            $data['date'],
            isset($data['morning_opening']) && $data['morning_opening'] !== '' ? (float)$data['morning_opening'] : null,
            isset($data['morning_closing']) && $data['morning_closing'] !== '' ? (float)$data['morning_closing'] : null,
            isset($data['evening_opening']) && $data['evening_opening'] !== '' ? (float)$data['evening_opening'] : null,
            isset($data['evening_closing']) && $data['evening_closing'] !== '' ? (float)$data['evening_closing'] : null
        );
    }
}
