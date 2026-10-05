<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\DTO;

class WageOrderDTO
{
    public ?int $id;
    public string $authority;
    public string $letter_no;
    public string $letter_date;
    public string $valid_from;
    public string $valid_to;
    public array $rates; // This will be stored as JSONB

    public function __construct(
        string $authority,
        string $letter_no,
        string $letter_date,
        string $valid_from,
        array $rates,
        string $valid_to = '9999-12-31',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->authority = $authority;
        $this->letter_no = $letter_no;
        $this->letter_date = $letter_date;
        $this->valid_from = $valid_from;
        $this->valid_to = $valid_to;
        $this->rates = $rates;
    }

    public static function fromArray(array $data): self
    {
        // Handle JSON decoding if rates come as a string from DB
        $rates = is_string($data['rates']) ? json_decode($data['rates'], true) : $data['rates'];

        return new self(
            $data['authority'],
            $data['letter_no'],
            $data['letter_date'],
            $data['valid_from'],
            $rates,
            $data['valid_to'] ?? '9999-12-31',
            isset($data['id']) ? (int)$data['id'] : null
        );
    }
}
