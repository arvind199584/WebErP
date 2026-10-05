<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';
// We will need services later to execute actions
require_once __DIR__ . '/../Machines/Services/MachineService.php';

use App\Core\BaseController;
use App\Modules\Workshop\Machine\TurfManagement\Machines\Services\MachineService;
use Exception;
use PDOException;

class AIEntryController extends BaseController {

    // We will instantiate services as needed based on the AI's response

    public function __construct() {
        parent::__construct('TurfManagement');
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';

        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $officeId = $_SESSION['office_id'] ?? 1;
            $userRole = strtolower($this->currentUser['role'] ?? 'guest');

            // This entire controller is for superusers only
            if ($userRole !== 'superuser') {
                throw new Exception("Access Denied: This feature is available for superusers only.");
            }

            if ($action === 'executeAction' && $this->currentUser) {
                 $this->executeAction($officeId);
                 return;
            }

            $this->checkPermission($action);

            switch ($action) {
                case 'index':
                    $this->showEntryForm();
                    break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['error' => $e->getMessage()]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /index.php'); // Redirect to main dashboard on error
            }
            exit;
        }
    }

    private function showEntryForm(): void {
        $this->render(__DIR__ . '/../Views/ai_entry.php');
    }

    private function executeAction(int $officeId): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method for action execution.");
            }

            $postData = json_decode(file_get_contents('php://input'), true);
            $intent = $postData['intent'] ?? null;
            $actionDetails = $postData['action_details'] ?? null;

            if (!$intent || !$actionDetails) {
                throw new Exception("Invalid action data received from AI.");
            }

            // Add officeid to the data payload for the service
            $actionDetails['data']['officeid'] = $officeId;

            $message = '';

            // Securely call the appropriate service based on the intent
            switch ($intent) {
                case 'create_machine':
                    $machineService = new MachineService();
                    $machineService->createMachine($actionDetails['data']);
                    $message = "Successfully created new machine: " . ($actionDetails['data']['name'] ?? '');
                    break;

                // We will add more cases here for other actions later
                // case 'create_inventory_item':
                //     ...
                //     break;

                default:
                    throw new Exception("Backend does not support the action for intent: '{$intent}'");
            }

            echo json_encode(['status' => 'success', 'message' => $message]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database Action Failed: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

$controller = new AIEntryController();
$controller->handleRequest();
