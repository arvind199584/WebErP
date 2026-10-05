<?php

declare(strict_types=1);

namespace App\Modules\Admin\AA_ES\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AA_ES_Service.php';
require_once __DIR__ . '/../DTO/AA_ES_DTO.php';
require_once __DIR__ . '/../../../Finance/Budget/Services/BudgetService.php';
require_once __DIR__ . '/../../../Superadmin/WagesRates/Services/WageItemService.php';
require_once __DIR__ . '/../../../Superadmin/WagesRates/Services/WageRateService.php';

use App\Core\BaseController;
use App\Modules\Admin\AA_ES\Services\AA_ES_Service;
use App\Modules\Admin\AA_ES\DTO\AA_ES_DTO;
use App\Modules\Finance\Budget\Services\BudgetService;
use App\Modules\Superadmin\WagesRates\Services\WageItemService;
use App\Modules\Superadmin\WagesRates\Services\WageRateService;
use Exception;
use PDOException;

class AA_ESController extends BaseController
{
    private AA_ES_Service $aaEsService;

    public function __construct()
    {
        parent::__construct('AA_ES');
        $this->aaEsService = new AA_ES_Service();
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
                case 'view': $this->view(); break;
                case 'showEditForm': $this->showEditForm(); break;
                case 'approve': $this->approve(); break;
                case 'revert': $this->revert(); break;
                case 'delete': $this->delete(); break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            if ($action === 'list') {
                die("Critical Error in AA_ES Module: " . $e->getMessage());
            }
            $_SESSION['error'] = $e->getMessage();
            $redirectAction = $this->currentUser ? 'list' : 'login';
            header("Location: ?action=$redirectAction");
            exit;
        }
    }

    private function list(): void
    {
        $aa_es_list = $this->aaEsService->getAllAA_ES();
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'aa_es_list' => $aa_es_list,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function showCreateForm(): void
    {
        if (!isset($_GET['step'])) {
            unset($_SESSION['aa_es_create_data']);
        }

        $step = (int)($_GET['step'] ?? 1);
        $data = $_SESSION['aa_es_create_data'] ?? [];

        if ($step == 1) {
            $budgetService = new BudgetService();
            $budgets = $budgetService->getAllBudgets();
            $wageRateService = new WageRateService();
            $wageOrders = $wageRateService->getAllOrders();
            $this->render(__DIR__ . '/../Views/create_step1.php', [
                'budgets' => $budgets, 
                'data' => $data,
                'wageOrders' => $wageOrders
            ]);
        } elseif ($step == 2) {
            if (empty($data)) { header('Location: ?action=showCreateForm&step=1'); exit; }

            if ($data['type'] === 'Manpower') {
                $wageItemService = new WageItemService();
                $rateDate = $data['wage_order_date'] ?? date('Y-m-d');
                $wageItems = $wageItemService->getAllItemsWithRates($rateDate);
                $this->render(__DIR__ . '/../Views/create_step2_manpower.php', ['data' => $data, 'wageItems' => $wageItems]);
            } else {
                $this->render(__DIR__ . '/../Views/create_step2_market.php', ['data' => $data]);
            }
        } elseif ($step == 3) {
            if (empty($data['boq'])) { header('Location: ?action=showCreateForm&step=2'); exit; }

            if (empty($data['alias'])) {
                $data['alias'] = $this->aaEsService->generateAlias($data['sub_head'], (int)$this->currentUser['officeid']);
                $_SESSION['aa_es_create_data']['alias'] = $data['alias'];
            }

            $calculatedAmount = $this->calculateAmountForPreview($data);
            $budgetService = new BudgetService();
            $availableBudget = $budgetService->getAvailableBudget((int)$data['budgetid']);

            $this->render(__DIR__ . '/../Views/create_step3.php', [
                'data' => $data,
                'calculatedAmount' => $calculatedAmount,
                'availableBudget' => $availableBudget
            ]);
        }
    }

    private function showEditForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $aa_es = $this->aaEsService->getAA_ESById($id);

        if (!$aa_es) throw new Exception("AA & ES not found.");
        if ($aa_es->status === 'Approved' && $this->currentUser['role'] !== 'superuser') {
            throw new Exception("Cannot edit approved AA & ES.");
        }

        $_SESSION['aa_es_create_data'] = [
            'id' => $aa_es->id,
            'budgetid' => $aa_es->budgetid,
            'sub_head' => $aa_es->sub_head,
            'type' => $aa_es->type,
            'alias' => $aa_es->alias,
            'boq' => $aa_es->boq,
            'status' => $aa_es->status
        ];

        header('Location: ?action=showCreateForm&step=1');
        exit;
    }

    private function approve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            if (empty($_FILES['approval_pdf'])) {
                $_SESSION['error'] = "Please upload the Approval Order PDF.";
                header('Location: ?action=list');
                exit;
            }

            try {
                $this->aaEsService->approveAA_ES($id, $_FILES['approval_pdf']);
                $_SESSION['message'] = "AA & ES Approved successfully.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
            header('Location: ?action=list');
            exit;
        }
    }

    private function revert(): void
    {
        $id = (int)$_GET['id'];
        try {
            $this->aaEsService->revertAA_ES($id);
            $_SESSION['message'] = "AA & ES reverted to Draft.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header('Location: ?action=list');
        exit;
    }

    private function calculateAmountForPreview(array $data): float
    {
        $total = 0.0;
        $boq = $data['boq'];

        if ($data['type'] === 'Market') {
            foreach ($boq as $item) {
                $qty = (float)$item['qty'];
                $rate = (float)$item['rate'];
                $gst = (float)str_replace('%', '', $item['gst']);
                $total += ($qty * $rate * (1 + $gst / 100));
            }
            return (float)round($total);
        } elseif ($data['type'] === 'Manpower') {
            $period = (int)$boq['Period'];
            $is7Day = !empty($boq['is_7_day']);
            $totalEst = 0.0;
            $esic = 0.0;
            $epf = 0.0;
            $bonus = 0.0;

            foreach ($boq['Items'] as $item) {
                $qty = (float)$item['qty'];
                $rate = (float)$item['rate'];

                $effectiveQty = $is7Day ? ($qty * 7.0 / 6.0) : $qty;

                $totalEst += ($effectiveQty * $rate * $period);

                if (!empty($boq['ESIC'])) $esic += ($effectiveQty * $period * 0.0325 * min($rate, 21000));
                if (!empty($boq['EPF'])) $epf += ($effectiveQty * $period * 0.13 * min($rate, 15000));
                if (!empty($boq['Bonus'])) $bonus += ($effectiveQty * $period * 0.0833 * min($rate, 21000));
            }

            $justified = $totalEst * 1.15;
            $total = $justified + ($justified * 0.18) + $esic + $epf + $bonus;
            return (float)round($total);
        }
        return 0.0;
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step = $_POST['step'];

            $_SESSION['aa_es_create_data'] = array_merge($_SESSION['aa_es_create_data'] ?? [], $_POST);

            if ($step == 1) {
                header('Location: ?action=showCreateForm&step=2');
                exit;
            } elseif ($step == 2) {
                $type = $_SESSION['aa_es_create_data']['type'];
                $boq = [];

                if ($type === 'Manpower') {
                    $boq = [
                        'Period' => (int)$_POST['period'],
                        'is_7_day' => isset($_POST['is_7_day']), // ADDED
                        'ESIC' => isset($_POST['esic']),
                        'EPF' => isset($_POST['epf']),
                        'Bonus' => isset($_POST['bonus']),
                        'Items' => []
                    ];
                    if (isset($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            if (!empty($item['qty'])) {
                                $boq['Items'][] = [
                                    'description' => $item['description'],
                                    'qty' => (float)$item['qty'],
                                    'rate' => (float)$item['rate']
                                ];
                            }
                        }
                    }
                } else {
                    if (isset($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            if (!empty($item['description'])) {
                                $boq[] = [
                                    'description' => $item['description'],
                                    'qty' => (float)$item['qty'],
                                    'unit' => $item['unit'],
                                    'rate' => (float)$item['rate'],
                                    'gst' => $item['gst'] . '%'
                                ];
                            }
                        }
                    }
                }

                $_SESSION['aa_es_create_data']['boq'] = $boq;
                header('Location: ?action=showCreateForm&step=3');
                exit;
            } elseif ($step == 3) {
                try {
                    $data = $_SESSION['aa_es_create_data'];
                    $data['alias'] = $_POST['alias'];

                    $dto = AA_ES_DTO::fromRequest($data, (int)$this->currentUser['officeid']);
                    if (isset($data['id'])) {
                        $this->aaEsService->updateAA_ES($dto);
                        $_SESSION['message'] = 'AA & ES updated successfully!';
                    } else {
                        $this->aaEsService->createAA_ES($dto);
                        $_SESSION['message'] = 'AA & ES created successfully!';
                    }

                    unset($_SESSION['aa_es_create_data']);
                    header('Location: ?action=list');
                    exit;
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database Error: " . $e->getMessage();
                    header('Location: ?action=showCreateForm&step=3');
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                    header('Location: ?action=showCreateForm&step=3');
                    exit;
                }
            }
        }
    }

    private function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $aa_es = $this->aaEsService->getAA_ESById($id);
        if (!$aa_es) throw new Exception("AA & ES not found.");
        $this->render(__DIR__ . '/../Views/view.php', ['aa_es' => $aa_es]);
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->aaEsService->deleteAA_ES($id);
        $_SESSION['message'] = 'AA & ES deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new AA_ESController();
$controller->handleRequest();
