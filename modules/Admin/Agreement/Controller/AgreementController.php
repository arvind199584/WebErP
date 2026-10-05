<?php

declare(strict_types=1);

namespace App\Modules\Admin\Agreement\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AgreementService.php';
require_once __DIR__ . '/../DTO/AgreementDTO.php';
require_once __DIR__ . '/../../AA_ES/Services/AA_ES_Service.php';
require_once __DIR__ . '/../../../Superadmin/Agency/Services/AgencyService.php';

use App\Core\BaseController;
use App\Modules\Admin\Agreement\Services\AgreementService;
use App\Modules\Admin\Agreement\DTO\AgreementDTO;
use App\Modules\Admin\AA_ES\Services\AA_ES_Service;
use App\Modules\Superadmin\Agency\Services\AgencyService;
use Exception;
use PDOException;

class AgreementController extends BaseController
{
    private AgreementService $agreementService;

    public function __construct()
    {
        parent::__construct('Agreement');
        $this->agreementService = new AgreementService();
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
                case 'showEditScopeForm': $this->showEditScopeForm(); break;
                case 'updateScope': $this->updateScope(); break;
                // ... other actions
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void
    {
        $agreements = $this->agreementService->getAllAgreements();
        $this->render(__DIR__ . '/../Views/index.php', ['agreements' => $agreements]);
    }

    private function showCreateForm(): void
    {
        if (!isset($_GET['step'])) {
            unset($_SESSION['agreement_create_data']);
        }

        $step = $_GET['step'] ?? 1;
        $data = $_SESSION['agreement_create_data'] ?? [];

        if ($step == 1) {
            $aaEsService = new AA_ES_Service();
            $agencyService = new AgencyService();
            $this->render(__DIR__ . '/../Views/create_step1.php', [
                'aa_es_list' => $aaEsService->getAllAA_ES(),
                'agencies' => $agencyService->getAllAgencies(),
                'activeAgreements' => $this->agreementService->findActiveAgreements(),
                'data' => $data
            ]);
        } elseif ($step == 2) {
            if (empty($data)) { header('Location: ?action=showCreateForm&step=1'); exit; }
            $aaEsService = new AA_ES_Service();
            $aa_es = $aaEsService->getAA_ESById((int)$data['aa_es_id']);
            $this->render(__DIR__ . '/../Views/create_step2.php', ['data' => $data, 'aa_es' => $aa_es]);
        } elseif ($step == 3) {
            if (empty($data)) { header('Location: ?action=showCreateForm&step=2'); exit; }
            $aaEsService = new AA_ES_Service();
            $aa_es = $aaEsService->getAA_ESById((int)$data['aa_es_id']);

            $predecessorEndDate = null;
            if (!empty($data['predecessor_id'])) {
                $pred = $this->agreementService->getAgreementById((int)$data['predecessor_id']);
                $predecessorEndDate = $pred ? $pred->period_to : null;
            }

            $this->render(__DIR__ . '/../Views/create_step3.php', [
                'data' => $data, 
                'aa_es' => $aa_es,
                'predecessorEndDate' => $predecessorEndDate
            ]);
        }
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step = $_POST['step'];

            $_SESSION['agreement_create_data'] = array_merge($_SESSION['agreement_create_data'] ?? [], $_POST);

            if ($step == 1) {
                header('Location: ?action=showCreateForm&step=2');
                exit;
            } elseif ($step == 2) {
                header('Location: ?action=showCreateForm&step=3');
                exit;
            } elseif ($step == 3) {
                try {
                    $data = $_SESSION['agreement_create_data'];

                    // Scope is NULL by default on creation
                    $data['scope'] = null;

                    $dto = AgreementDTO::fromRequest($data, (int)$this->currentUser['officeid']);
                    $this->agreementService->createAgreement($dto);

                    unset($_SESSION['agreement_create_data']);
                    $_SESSION['message'] = 'Agreement created successfully!';
                    header('Location: ?action=list');
                    exit;
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database Error: " . $e->getMessage();
                    header('Location: ?action=showCreateForm&step=3');
                    exit;
                }
            }
        }
    }

    private function showEditForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $agreement = $this->agreementService->getAgreementById($id);
        if (!$agreement) {
            throw new Exception("Agreement not found.");
        }

        $aaEsService = new AA_ES_Service();
        $agencyService = new AgencyService();

        $this->render(__DIR__ . '/../Views/edit.php', [
            'agreement' => $agreement,
            'aa_es_list' => $aaEsService->getAllAA_ES(),
            'agencies' => $agencyService->getAllAgencies(),
            'activeAgreements' => $this->agreementService->findActiveAgreements($id),
        ]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dto = AgreementDTO::fromRequest($_POST, (int)$this->currentUser['officeid']);
                $this->agreementService->updateAgreement($dto);

                $_SESSION['message'] = 'Agreement updated successfully!';
                header('Location: ?action=list');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Update Error: " . $e->getMessage();
                header('Location: ?action=showEditForm&id=' . (int)$_POST['id']);
                exit;
            }
        }
    }

    private function showEditScopeForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $agreement = $this->agreementService->getAgreementById($id);
        if (!$agreement) throw new Exception("Agreement not found.");

        // If scope is NULL, generate it from the AA&ES
        if ($agreement->scope === null) {
            $aaEsService = new AA_ES_Service();
            $aa_es = $aaEsService->getAA_ESById($agreement->aa_es_id);
            $initialItems = [];
            if ($aa_es && $aa_es->type === 'Manpower') {
                foreach ($aa_es->boq['Items'] as $item) {
                    $initialItems[$item['description']] = $item['qty'];
                }
            }
            $agreement->scope = [[
                'datefrom' => $agreement->period_from,
                'dateto' => null,
                'Items' => $initialItems
            ]];
        }

        $this->render(__DIR__ . '/../Views/edit_scope.php', ['agreement' => $agreement]);
    }

    private function updateScope(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $newScopeDate = $_POST['new_scope_date'];
            $newItems = [];
            foreach ($_POST['items'] as $key => $value) {
                $newItems[$key] = (int)$value;
            }

            $this->agreementService->updateScope($id, $newScopeDate, $newItems);
            $_SESSION['message'] = 'Scope updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }
}

$controller = new AgreementController();
$controller->handleRequest();
