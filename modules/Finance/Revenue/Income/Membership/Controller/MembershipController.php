<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Membership\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/MembershipService.php';
require_once __DIR__ . '/../Services/MembershipSchemaService.php';

use App\Core\BaseController;
use App\Modules\Finance\Revenue\Income\Membership\Services\MembershipService;
use App\Modules\Finance\Revenue\Income\Membership\Services\MembershipSchemaService;
use Exception;

class MembershipController extends BaseController {
    private MembershipService $service;

    public function __construct() {
        parent::__construct('Finance/Revenue/Income/Membership');
        $schemaService = new MembershipSchemaService();
        $schemaService->initSchema();
        $this->service = new MembershipService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            // $this->checkPermission($action); // Temporarily disabled until PermissionManager is set up
            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function list(): void {
        $members = $this->service->getAllMembers();
        $this->render(__DIR__ . '/../Views/index.php', ['members' => $members]);
    }

    private function showCreateForm(): void {
        $this->render(__DIR__ . '/../Views/create.php');
    }

    private function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['officeid'] = $this->currentUser['officeid'];

            if ($this->service->createMember($data)) {
                header("Location: ?action=list");
                exit;
            }
        }
    }
}

$controller = new MembershipController();
$controller->handleRequest();
