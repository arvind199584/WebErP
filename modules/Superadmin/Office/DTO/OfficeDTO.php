<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Office\DTO;

/**
 * Data Transfer Object for an Office entity.
 */
class OfficeDTO
{
    public ?int $Officeid;
    public string $OfficeName;
    public ?string $OfficeCode;
    public ?string $Address;
    public ?string $Phone;
    public ?string $email;
    public ?string $ContactPerson;
    public bool $has_finance;
    public bool $has_store;
    public bool $has_workshop;
    public bool $has_hr;

    public function __construct(
        string $OfficeName,
        ?string $Address = null,
        ?string $Phone = null,
        ?string $email = null,
        ?string $ContactPerson = null,
        ?int $Officeid = null,
        ?string $OfficeCode = null,
        bool $has_finance = false,
        bool $has_store = false,
        bool $has_workshop = false,
        bool $has_hr = true
    ) {
        $this->Officeid = $Officeid;
        $this->OfficeName = $OfficeName;
        $this->OfficeCode = $OfficeCode;
        $this->Address = $Address;
        $this->Phone = $Phone;
        $this->email = $email;
        $this->ContactPerson = $ContactPerson;
        $this->has_finance = $has_finance;
        $this->has_store = $has_store;
        $this->has_workshop = $has_workshop;
        $this->has_hr = $has_hr;
    }

    /**
     * Creates a DTO from a standard POST request array.
     * @param array $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            $data['OfficeName'],
            $data['Address'] ?? null,
            $data['Phone'] ?? null,
            $data['email'] ?? null,
            $data['ContactPerson'] ?? null,
            isset($data['Officeid']) ? (int)$data['Officeid'] : null,
            $data['OfficeCode'] ?? null,
            isset($data['has_finance']) && ($data['has_finance'] == '1' || $data['has_finance'] === true),
            isset($data['has_store']) && ($data['has_store'] == '1' || $data['has_store'] === true),
            isset($data['has_workshop']) && ($data['has_workshop'] == '1' || $data['has_workshop'] === true),
            !isset($data['OfficeName']) || (isset($data['has_hr']) && ($data['has_hr'] == '1' || $data['has_hr'] === true))
        );
    }

    /**
     * Converts the DTO to an array.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'Officeid' => $this->Officeid,
            'OfficeName' => $this->OfficeName,
            'OfficeCode' => $this->OfficeCode,
            'Address' => $this->Address,
            'Phone' => $this->Phone,
            'email' => $this->email,
            'ContactPerson' => $this->ContactPerson,
            'has_finance' => $this->has_finance,
            'has_store' => $this->has_store,
            'has_workshop' => $this->has_workshop,
            'has_hr' => $this->has_hr,
        ];
    }
}
