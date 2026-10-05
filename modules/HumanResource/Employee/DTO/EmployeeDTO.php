<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Employee\DTO;

class EmployeeDTO {
    public ?int $id;
    public int $officeid;
    public int $agreement_id;
    public string $full_name;
    public string $designation;
    public ?string $account_no;
    public ?string $ifsc;
    public ?string $bank_name;
    public ?string $uan_no;
    public ?string $esic_no;
    public string $joining_date;
    public ?string $leaving_date;
    public ?string $default_rest_day;
    public bool $is_reliever;
    public ?int $inherited_from_id;
    public ?int $deployed_office_id;

    public function __construct(
        int $officeid, int $agreement_id, string $full_name, string $designation,
        string $joining_date, ?string $leaving_date, ?string $default_rest_day,
        bool $is_reliever, ?string $account_no = null, ?string $ifsc = null,
        ?string $bank_name = null, ?string $uan_no = null, ?string $esic_no = null,
        ?int $inherited_from_id = null, ?int $id = null, ?int $deployed_office_id = null
    ) {
        $this->id = $id;
        $this->officeid = $officeid;
        $this->agreement_id = $agreement_id;
        $this->full_name = $full_name;
        $this->designation = $designation;
        $this->joining_date = $joining_date;
        $this->leaving_date = $leaving_date;
        $this->default_rest_day = $default_rest_day;
        $this->is_reliever = $is_reliever;
        $this->account_no = $account_no;
        $this->ifsc = $ifsc;
        $this->bank_name = $bank_name;
        $this->uan_no = $uan_no;
        $this->esic_no = $esic_no;
        $this->inherited_from_id = $inherited_from_id;
        $this->deployed_office_id = $deployed_office_id;
    }
}
