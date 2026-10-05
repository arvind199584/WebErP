<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Agency\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AgencyService.php';
require_once __DIR__ . '/../DTO/AgencyDTO.php';

use App\Core\BaseController;
use App\Modules\Superadmin\Agency\Services\AgencyService;
use App\Modules\Superadmin\Agency\DTO\AgencyDTO;
use Exception;
use PDOException;

class AgencyController extends BaseController
{
    private AgencyService $agencyService;

    public function __construct()
    {
        parent::__construct('Agency');
        $this->agencyService = new AgencyService();
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
            // REDIRECT TO ROOT TO BREAK THE LOOP
            header("Location: /");
            exit;
        }
    }

    private function list(): void
    {
        $agencies = $this->agencyService->getAllAgencies();
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'agencies' => $agencies,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function showCreateForm(): void
    {
        $oldInput = $_SESSION['old_input'] ?? [];
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['old_input'], $_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/create.php', [
            'oldInput' => $oldInput,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = AgencyDTO::fromRequest($_POST);
                $this->agencyService->createAgency($dto);

                $_SESSION['message'] = 'Agency created successfully!';

                if (isset($_POST['save_and_continue'])) {
                    $_SESSION['old_input'] = []; // Clear input for fresh start
                    header('Location: ?action=showCreateForm');
                    exit;
                } else {
                    header('Location: ?action=list');
                    exit;
                }

            } catch (PDOException $e) {
                if ($e->getCode() == '23505') {
                    $_SESSION['error'] = "Error: An agency with this name already exists.";
                } else {
                    $_SESSION['error'] = "Database Error: " . $e->getMessage();
                }
                $_SESSION['old_input'] = $_POST;
                header('Location: ?action=showCreateForm');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                $_SESSION['old_input'] = $_POST;
                header('Location: ?action=showCreateForm');
                exit;
            }
        }
    }

    private function showEditForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $agency = $this->agencyService->getAgencyById($id);
        if (!$agency) throw new Exception("Agency not found.");

        $this->render(__DIR__ . '/../Views/edit.php', ['agency' => $agency]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = AgencyDTO::fromRequest($_POST);
                $this->agencyService->updateAgency($dto);
                $_SESSION['message'] = 'Agency updated successfully!';
                header('Location: ?action=list');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') {
                    $_SESSION['error'] = "Error: An agency with this name already exists.";
                } else {
                    $_SESSION['error'] = "Database Error: " . $e->getMessage();
                }
                header('Location: ?action=list');
                exit;
            }
        }
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->agencyService->deleteAgency($id);
        $_SESSION['message'] = 'Agency deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new AgencyController();
$controller->handleRequest();
