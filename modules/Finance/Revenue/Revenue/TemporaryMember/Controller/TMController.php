<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Revenue\TemporaryMember\Controller;

require_once __DIR__ . '/../../../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/TMService.php';

use App\Core\BaseController;
use App\Modules\Finance\Revenue\Revenue\TemporaryMember\Services\TMService;
use Exception;

class TMController extends BaseController {
    private TMService $service;

    public function __construct() {
        parent::__construct('Finance/Revenue/Revenue/TemporaryMember');
        $this->service = new TMService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index':
                case 'list': $this->index(); break;
                case 'showCreate': $this->showCreate(); break;
                case 'create': $this->create(); break;
                case 'showEdit': $this->showEdit(); break;
                case 'update': $this->update(); break;
                case 'delete': $this->delete(); break;
                case 'print_bill': $this->printBill(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->render(__DIR__ . '/../Views/index.php', ['members' => []]);
        }
    }

    private function index(): void {
        $members = $this->service->getAll();
        $this->render(__DIR__ . '/../Views/index.php', ['members' => $members]);
    }

    private function showCreate(): void {
        $this->render(__DIR__ . '/../Views/create.php');
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'office_id' => (int)$this->currentUser['officeid'],
                'tm_no' => $_POST['tm_no'],
                'name' => $_POST['name'],
                'dob' => $_POST['dob'],
                'address' => $_POST['address'],
                'dependents' => $_POST['dependents'] ?? '[]',
                'validity' => $_POST['validity'], // Expected string '[start, end]'
                'payment_amount' => (float)$_POST['payment_amount'],
                'payment_reference' => $_POST['payment_reference'] ?? ''
            ];
            if ($this->service->create($data)) {
                $db = \App\Core\Database::getInstance()->getConnection();
                $newId = $db->lastInsertId();
                $_SESSION['message'] = "Temporary Member created successfully!";
                header("Location: ?action=print_bill&id=" . $newId);
                exit;
            }
        }
    }

    private function showEdit(): void {
        $id = (int)$_GET['id'];
        $member = $this->service->getById($id);
        $this->render(__DIR__ . '/../Views/create.php', ['member' => $member, 'isEdit' => true]);
    }

    private function update(): void {
        $id = (int)$_POST['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tm_no' => $_POST['tm_no'],
                'name' => $_POST['name'],
                'dob' => $_POST['dob'],
                'address' => $_POST['address'],
                'dependents' => $_POST['dependents'] ?? '[]',
                'validity' => $_POST['validity'],
                'payment_amount' => (float)$_POST['payment_amount'],
                'payment_reference' => $_POST['payment_reference'] ?? ''
            ];
            if ($this->service->update($id, $data)) {
                $_SESSION['message'] = "Member updated successfully!";
                header("Location: ?action=index");
                exit;
            }
        }
    }

    private function delete(): void {
        $id = (int)$_GET['id'];
        if ($this->service->delete($id)) {
            $_SESSION['message'] = "Member deleted.";
        }
        header("Location: ?action=index");
        exit;
    }

    private function printBill(): void {
        $id = (int)$_GET['id'];
        $member = $this->service->getById($id);
        if (!$member) throw new Exception("Member not found.");

        // Fetch Office Details
        $db = \App\Core\Database::getInstance()->getConnection();
        $office = $db->query("SELECT * FROM office WHERE officeid = {$member['office_id']}")->fetch(\PDO::FETCH_ASSOC);

        // Fetch Rates used for breakdown
        $rates = $db->query("SELECT * FROM tm_rates WHERE office_id = {$member['office_id']} AND is_active = TRUE ORDER BY effective_from DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        
        // Calculate Breakdown for the Bill
        $dependents = json_decode($member['dependents'], true);
        $depCount = is_array($dependents) ? count($dependents) : 0;
        
        $baseTotal = $rates['main_member_rate'] + ($depCount * $rates['dependent_rate']);
        $icardTotal = (1 + $depCount) * $rates['icard_rate'];
        $subtotal = $baseTotal + $icardTotal;
        $gstAmount = $subtotal * ($rates['gst_percent'] / 100);
        
        $this->render(__DIR__ . '/../Views/bill_print.php', [
            'member' => $member,
            'office' => $office,
            'rates' => $rates,
            'breakdown' => [
                'dep_count' => $depCount,
                'base_total' => $baseTotal,
                'icard_total' => $icardTotal,
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'total' => $member['payment_amount']
            ]
        ], false); // No layout for printing
    }
}

$controller = new TMController();
$controller->handleRequest();
