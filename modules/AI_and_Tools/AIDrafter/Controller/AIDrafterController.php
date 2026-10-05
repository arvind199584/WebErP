<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIDrafter\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/GeminiService.php';
require_once __DIR__ . '/../Services/PromptService.php';
require_once __DIR__ . '/../../../Admin/Agreement/Services/AgreementService.php';
require_once __DIR__ . '/../../../Superadmin/Agency/Services/AgencyService.php';
require_once __DIR__ . '/../../../Superadmin/AddressBook/Services/AddressBookService.php';
require_once __DIR__ . '/../../../Admin/AA_ES/Services/AA_ES_Service.php';

use App\Core\BaseController;
use App\Modules\AI_and_Tools\AIDrafter\Services\GeminiService;
use App\Modules\AI_and_Tools\AIDrafter\Services\PromptService;
use App\Modules\Admin\Agreement\Services\AgreementService;
use App\Modules\Superadmin\Agency\Services\AgencyService;
use App\Modules\Superadmin\AddressBook\Services\AddressBookService;
use App\Modules\Admin\AA_ES\Services\AA_ES_Service;
use Exception;

class AIDrafterController extends BaseController {
    private GeminiService $geminiService;

    public function __construct() {
        parent::__construct('AIDrafter');
        $this->geminiService = new GeminiService();
    }

    public function handleRequest(): void {
        // CORRECTED: Default action is 'index', not 'list'
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index': $this->index(); break;
                case 'generate': $this->generate(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: /");
            exit;
        }
    }

    private function index(): void {
        $agreementService = new AgreementService();
        $agreements = $agreementService->getAllAgreements();

        $aaEsService = new AA_ES_Service();
        $aa_es_list = $aaEsService->getAllAA_ES();

        $addressService = new AddressBookService();
        $addresses = $addressService->getAllAddresses();

        $this->render(__DIR__ . '/../Views/index.php', [
            'agreements' => $agreements,
            'aa_es_list' => $aa_es_list,
            'addresses' => $addresses
        ]);
    }

    private function generate(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $params = [
                'type' => $_POST['letter_type'],
                'context' => $_POST['custom_context'] ?? '',
                'office_name' => $this->currentUser['officename'] ?? 'DDA Sports Complex'
            ];

            if (!empty($_POST['address_id'])) {
                $addressService = new AddressBookService();
                $params['recipient'] = $addressService->getAddressById((int)$_POST['address_id']);
            }

            if (!empty($_POST['agreement_id'])) {
                $agreementService = new AgreementService();
                $ag = $agreementService->getAgreementById((int)$_POST['agreement_id']);
                $agencyService = new AgencyService();
                $agency = $agencyService->getAgencyById($ag->agency_id);
                $params['agreement'] = ['agreement_no' => $ag->agreement_no, 'tendered_amount' => $ag->tendered_amount];
                $params['agency'] = ['name' => $agency->name];
            } elseif (!empty($_POST['aa_es_id'])) {
                $aaEsService = new AA_ES_Service();
                $ae = $aaEsService->getAA_ESById((int)$_POST['aa_es_id']);
                $params['sanction'] = ['sub_head' => $ae->sub_head, 'amount' => $ae->aa_es_amount];
            }

            $draft = $this->geminiService->generateDraft($params);

            $this->render(__DIR__ . '/../Views/editor.php', [
                'draft' => $draft,
                'letterType' => $params['type']
            ]);
        }
    }
}

$controller = new AIDrafterController();
$controller->handleRequest();
