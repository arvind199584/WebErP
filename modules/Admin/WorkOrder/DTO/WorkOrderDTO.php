<?php
declare(strict_types=1);
namespace App\Modules\Admin\WorkOrder\DTO;

class WorkOrderDTO {
    public ?int $id;
    public int $officeid;
    public int $aa_es_id;
    public int $agency_id;
    public string $work_order_no;
    public float $estimated_cost;
    public float $tendered_amount;
    public float $service_charge_percent;
    public string $status;

    public function __construct(
        int $officeid, int $aa_es_id, int $agency_id, string $work_order_no,
        float $estimated_cost, float $tendered_amount, float $service_charge_percent,
        string $status = 'Draft', ?int $id = null
    ) {
        $this->id = $id;
        $this->officeid = $officeid;
        $this->aa_es_id = $aa_es_id;
        $this->agency_id = $agency_id;
        $this->work_order_no = $work_order_no;
        $this->estimated_cost = $estimated_cost;
        $this->tendered_amount = $tendered_amount;
        $this->service_charge_percent = $service_charge_percent;
        $this->status = $status;
    }
}
