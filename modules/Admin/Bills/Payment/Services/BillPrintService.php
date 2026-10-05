<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\Payment\Services;

use App\Core\Database;
use PDO;
require_once __DIR__ . '/../../../../../vendor/autoload.php'; // Composer Autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class BillPrintService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function generateDocument(int $billId, string $type): string {
        $data = $this->fetchBillData($billId);
        if (!$data) { throw new \Exception("Bill data not found."); }

        if ($type === 'excel') {
            return $this->generateExcel($data);
        } else {
            return $this->generateWord($data);
        }
    }

    private function generateExcel(array $data): string {
        $templatePath = __DIR__ . '/../../../../../storage/templates/Abstract-template.xlsx';
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Simple replacements
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $val = $cell->getValue();
                if (is_string($val)) {
                    if (strpos($val, '${office_bill_no}') !== false) {
                        $cell->setValue(str_replace('${office_bill_no}', $data['office_bill_no'], $val));
                    }
                    // Add more simple replacements here
                }
            }
        }

        // TODO: Add logic for dynamic table rows (items, shortage)

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'bill_') . '.xlsx';
        $writer->save($tempFile);
        return $tempFile;
    }

    private function generateWord(array $data): string {
        $templatePath = __DIR__ . '/../../../../../storage/templates/Bill-template.docx';
        $templateProcessor = new TemplateProcessor($templatePath);

        // Simple replacements
        $templateProcessor->setValue('office_bill_no', $data['office_bill_no']);
        $templateProcessor->setValue('name_of_work', $data['name_of_work']);
        $templateProcessor->setValue('sub_head', $data['sub_head']);
        $templateProcessor->setValue('agency_name', $data['agency_name']);
        // ... add all other simple placeholders

        // TODO: Add logic for dynamic table rows

        $tempFile = tempnam(sys_get_temp_dir(), 'bill_') . '.docx';
        $templateProcessor->saveAs($tempFile);
        return $tempFile;
    }

    private function fetchBillData(int $billId): ?array {
        $sql = "
            SELECT
                b.*,
                a.agreement_no, a.tendered_amount, a.period as agreement_period, a.service_charge_percent,
                ag.name as agency_name, ag.pan_no, ag.gst_no, ag.bank_name, ag.account_no, ag.ifsc,
                bg.code as budget_code, bg.name_of_work, bg.provision,
                ae.sub_head,
                o.officecode as office_code
            FROM bills b
            LEFT JOIN agreements a ON b.agreement_id = a.id
            LEFT JOIN agencies ag ON a.agency_id = ag.id
            LEFT JOIN aa_es ae ON a.aa_es_id = ae.id
            LEFT JOIN budget bg ON ae.budgetid = bg.id
            LEFT JOIN office o ON a.officeid = o.Officeid
            WHERE b.id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $billId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
