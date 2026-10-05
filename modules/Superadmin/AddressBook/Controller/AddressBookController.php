<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\AddressBook\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AddressBookService.php';

use App\Core\BaseController;
use App\Modules\Superadmin\AddressBook\Services\AddressBookService;
use Exception;

class AddressBookController extends BaseController {
    private AddressBookService $service;

    public function __construct() {
        parent::__construct('AddressBook');
        $this->service = new AddressBookService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'create': $this->create(); break;
                case 'delete': $this->delete(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $addresses = $this->service->getAllAddresses();
        $this->render(__DIR__ . '/../Views/index.php', ['addresses' => $addresses]);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->service->addAddress((int)$this->currentUser['officeid'], $_POST);
            $_SESSION['message'] = "Address added successfully!";
            header("Location: ?action=list");
            exit;
        }
        $this->render(__DIR__ . '/../Views/create.php');
    }

    private function delete(): void {
        $id = (int)$_GET['id'];
        $this->service->deleteAddress($id);
        $_SESSION['message'] = "Address deleted.";
        header("Location: ?action=list");
        exit;
    }
}

$controller = new AddressBookController();
$controller->handleRequest();
