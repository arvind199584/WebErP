<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Office\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/OfficeService.php';
require_once __DIR__ . '/../DTO/OfficeDTO.php';

use App\Core\BaseController;
use App\Modules\Superadmin\Office\Services\OfficeService;
use App\Modules\Superadmin\Office\DTO\OfficeDTO;
use Exception;

class OfficeController extends BaseController
{
    private OfficeService $officeService;

    public function __construct()
    {
        parent::__construct('Office');
        $this->officeService = new OfficeService();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'list';

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'showEditForm': $this->showEditForm(); break;
                case 'update': $this->update(); break;
                case 'delete': $this->delete(); break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $redirect = $this->currentUser ? '/modules/Superadmin/Office/Controller/OfficeController.php?action=list' : '/index.php';
            header("Location: $redirect");
            exit;
        }
    }

    private function list(): void
    {
        $searchTerm = $_GET['search'] ?? null;
        $offices = $this->officeService->getAllOffices($searchTerm);
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'offices' => $offices,
            'message' => $message,
            'error' => $error,
            'searchTerm' => $searchTerm
        ]);
    }

    private function showCreateForm(): void
    {
        $this->render(__DIR__ . '/../Views/create.php');
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $officeDTO = OfficeDTO::fromRequest($_POST);
            $this->officeService->createOffice($officeDTO);
            $_SESSION['message'] = 'Office created successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function showEditForm(): void
    {
        $officeId = (int)($_GET['id'] ?? 0);
        $office = $this->officeService->getOfficeById($officeId);
        if (!$office) throw new Exception("Office not found.");

        $this->render(__DIR__ . '/../Views/edit.php', ['office' => $office]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $officeDTO = OfficeDTO::fromRequest($_POST);
            $this->officeService->updateOffice($officeDTO);
            $_SESSION['message'] = 'Office updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function delete(): void
    {
        $officeId = (int)($_GET['id'] ?? 0);
        $this->officeService->deleteOffice($officeId);
        $_SESSION['message'] = 'Office deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new OfficeController();
$controller->handleRequest();
