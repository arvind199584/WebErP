<?php
declare(strict_types=1);

namespace App\Modules\Admin\Bills\Payment\Services;

require_once __DIR__ . '/../../../../../core/Database.php';

use App\Core\Database;
use Exception;
use PDO;

class ValidationService {
    private PDO $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function validateStep1(array $data): void {
        if (empty($data['bill_type']) || empty($data['bill_date'])) {
            throw new Exception("Bill Type and Bill Date are required.");
        }

        if ($data['bill_type'] === 'Compliance') {
            if (empty($data['budget_id'])) {
                throw new Exception('Compliance bills must have a Budget ID.');
            }
            if (!empty($data['agreement_id']) || !empty($data['work_order_id']) || !empty($data['supply_order_id'])) {
                throw new Exception('Compliance bills cannot be linked to an Agreement, Work Order, or Supply Order.');
            }
        } else {
            $ag = !empty($data['agreement_id']) ? 1 : 0;
            $wo = !empty($data['work_order_id']) ? 1 : 0;
            $so = !empty($data['supply_order_id']) ? 1 : 0;
            $link_count = $ag + $wo + $so;

            // DEBUG: Print the calculation
            echo "DEBUG VALIDATION: AG=$ag, WO=$wo, SO=$so, COUNT=$link_count<br>";

            if ($link_count !== 1) {
                throw new Exception('A non-compliance bill must be linked to exactly one item (Agreement, Work Order, or Supply Order).');
            }
        }
    }

    public function validateAgreementScope(int $agreementId, float $newBillAmount): void {
        $stmt = $this->db->prepare("
            SELECT ae.sanctioned_amount, COALESCE(ob.items_paid, 0) as items_paid
            FROM agreements a
            JOIN aa_es ae ON a.aa_es_id = ae.id
            LEFT JOIN opening_balances ob ON a.id = ob.agreement_id
            WHERE a.id = :agreement_id
        ");
        $stmt->execute(['agreement_id' => $agreementId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Could not find agreement or linked AA&ES to validate scope.");
        }

        $potentialTotal = (float)$result['items_paid'] + $newBillAmount;
        if ($potentialTotal > (float)$result['sanctioned_amount']) {
            throw new Exception(sprintf(
                "Validation Failed: Bill amount (%.2f) would exceed sanctioned scope (%.2f). Total spent so far: %.2f",
                $newBillAmount, $result['sanctioned_amount'], $result['items_paid']
            ));
        }
    }

    public function validateBillPeriodChronology(int $agreementId, string $periodFrom, string $periodTo): void {
        $stmt = $this->db->prepare("
            SELECT a.period as agreement_period, ob.items_upto, ob.items_gap
            FROM agreements a
            LEFT JOIN opening_balances ob ON a.id = ob.agreement_id
            WHERE a.id = :agreement_id
        ");
        $stmt->execute(['agreement_id' => $agreementId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Could not find agreement to validate period.");
        }

        if ($result['items_upto']) {
            if ($periodFrom > $result['items_upto']) {
                // OK
            } else {
                if (!$result['items_gap']) {
                    throw new Exception("Validation Failed: Bill period is not after the 'Items Paid Upto' date (" . $result['items_upto'] . ") and does not fall within the defined 'Items Gap'.");
                }
            }
        }

        $stmt = $this->db->prepare("SELECT office_bill_no FROM bills WHERE agreement_id = :id AND DATERANGE(period_from, period_to, '[]') && DATERANGE(:from, :to, '[]') LIMIT 1");
        $stmt->execute(['id' => $agreementId, 'from' => $periodFrom, 'to' => $periodTo]);
        if ($stmt->fetchColumn()) {
            throw new Exception("Validation Failed: A bill for this specific period already exists in the system.");
        }
    }
}
