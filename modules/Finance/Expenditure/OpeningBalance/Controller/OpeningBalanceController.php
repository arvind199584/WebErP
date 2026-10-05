<?php
declare(strict_types=1);
namespace App\Modules\Finance\Expenditure\OpeningBalance\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/OpeningBalanceService.php';

use App\Core\BaseController;
use App\Modules\Finance\Expenditure\OpeningBalance\Services\OpeningBalanceService;
use Exception;

class OpeningBalanceController extends BaseController {
    private OpeningBalanceService $obService;
    public function __construct() {
        parent::__construct('Finance/Expenditure/OpeningBalance');
        $this->obService = new OpeningBalanceService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'list';
        try {
            // Permissions will be handled per action
            switch ($action) {
                case 'list': $this->list(); break;
                case 'edit': $this->edit(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void {
        $this->checkPermission('list');
        $agreements = $this->obService->getAgreementsForOB();
        $this->render(__DIR__ . '/../Views/index.php', ['agreements' => $agreements]);
    }

    private function edit(): void {
        $this->checkPermission('edit');
        $agreementId = (int)($_REQUEST['agreement_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step = (int)$_POST['step'];
            $_SESSION['ob_edit_data'][$agreementId] = array_merge($_SESSION['ob_edit_data'][$agreementId] ?? [], $_POST);

            // Permission Check: Only superuser can edit existing records
            $existingOB = $this->obService->getOBByAgreementId($agreementId);
            if ($existingOB && $this->currentUser['role'] !== 'superuser') {
                throw new Exception("Permission Denied: Only a superuser can alter an existing Opening Balance.");
            }

            if ($step < 3) {
                header('Location: ?action=edit&agreement_id=' . $agreementId . '&step=' . ($step + 1));
                exit;
            } else {
                // Final step: Save the data
                $data = $_SESSION['ob_edit_data'][$agreementId];
                $data['officeid'] = (int)$this->currentUser['officeid'];
                $this->obService->updateOB($data);

                unset($_SESSION['ob_edit_data'][$agreementId]);
                $_SESSION['message'] = 'Opening Balance updated successfully!';
                header('Location: ?action=list');
                exit;
            }
        }

        $step = (int)($_GET['step'] ?? 1);
        $obData = $_SESSION['ob_edit_data'][$agreementId] ?? $this->obService->getOBByAgreementId($agreementId);

        $viewData = ['obData' => $obData, 'agreementId' => $agreementId];
        if ($step === 3) {
            $viewData['aaesBoq'] = $this->obService->getBoqForAgreement($agreementId);
        }

        $this->render(__DIR__ . '/../Views/edit_step' . $step . '.php', $viewData);
    }
}

$controller = new OpeningBalanceController();
$controller->handleRequest();
