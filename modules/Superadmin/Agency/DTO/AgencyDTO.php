<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Agency\DTO;

class AgencyDTO
{
    public ?int $id;
    public string $name;
    public ?string $account_no;
    public ?string $ifsc;
    public ?string $bank_name;
    public ?string $gst_no;
    public ?string $pan_no;
    public ?string $address;
    public ?string $contact_person;
    public ?string $email;

    public function __construct(
        string $name,
        ?string $account_no = null,
        ?string $ifsc = null,
        ?string $bank_name = null,
        ?string $gst_no = null,
        ?string $pan_no = null,
        ?string $address = null,
        ?string $contact_person = null,
        ?string $email = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->account_no = $account_no;
        $this->ifsc = $ifsc;
        $this->bank_name = $bank_name;
        $this->gst_no = $gst_no;
        $this->pan_no = $pan_no;
        $this->address = $address;
        $this->contact_person = $contact_person;
        $this->email = $email;
    }

    public static function fromRequest(array $data): self
    {
        // Helper to return null if string is empty
        $val = fn($key) => !empty($data[$key]) ? trim($data[$key]) : null;

        return new self(
            $data['name'],
            $val('account_no'),
            $val('ifsc'),
            $val('bank_name'),
            $val('gst_no'),
            $val('pan_no'),
            $val('address'),
            $val('contact_person'),
            $val('email'),
            isset($data['id']) ? (int)$data['id'] : null
        );
    }
}
