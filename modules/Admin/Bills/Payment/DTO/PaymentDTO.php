<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\Payment\DTO;

class PaymentDTO {
    public int $officeid;
    public ?string $agency_bill_no;
    public string $bill_date;
    public ?string $office_bill_no;
    public string $bill_type;
    public ?int $agreement_id;
    public ?int $work_order_id;
    public ?int $supply_order_id;
    public ?int $budget_id;
    public ?array $bill_items;
    public ?array $withheld;

    public function __construct(array $data, int $officeid) {
        $this->officeid = $officeid;
        $this->agency_bill_no = $data['agency_bill_no'] ?? null;
        $this->bill_date = $data['bill_date'];
        $this->office_bill_no = $data['office_bill_no'] ?? null;
        $this->bill_type = $data['bill_type'];
        $this->agreement_id = isset($data['agreement_id']) && $data['agreement_id'] ? (int)$data['agreement_id'] : null;
        $this->work_order_id = isset($data['work_order_id']) && $data['work_order_id'] ? (int)$data['work_order_id'] : null;
        $this->supply_order_id = isset($data['supply_order_id']) && $data['supply_order_id'] ? (int)$data['supply_order_id'] : null;
        $this->budget_id = isset($data['budget_id']) && $data['budget_id'] ? (int)$data['budget_id'] : null;
        $this->bill_items = $data['bill_items'] ?? null;
        $this->withheld = $data['withheld'] ?? null;
    }
}
