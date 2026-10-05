<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\ExtraItem\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ExtraItemService.php';
require_once __DIR__ . '/../../../Agreement/Services/AgreementService.php';

use App\Core\BaseController;
use App\Modules\Admin\Bills\ExtraItem\Services\ExtraItemService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use Exception;

class ExtraItemController extends BaseController {
    private ExtraItemService $service;

    public function __construct() {
        parent::__construct('ExtraItem');
        $this->service = new ExtraItemService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $extraItems = $this->service->getAllExtraItems();
        $this->render(__DIR__ . '/../Views/index.php', ['extraItems' => $extraItems]);
    }

    private function showCreateForm(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();
        $this->render(__DIR__ . '/../Views/create.php', ['agreements' => $agreements]);
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['officeid'] = $this->currentUser['officeid'];
            if ($this->service->createExtraItem($data)) {
                $_SESSION['message'] = "Extra Item created successfully!";
                header("Location: ?action=list");
                exit;
            }
        }
    }
}

$controller = new ExtraItemController();
$controller->handleRequest();
