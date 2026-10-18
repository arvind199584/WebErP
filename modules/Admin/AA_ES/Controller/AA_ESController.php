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
                case 'searchDsr': $this->searchDsr(); break;
                case 'getDarAnalysis': $this->getDarAnalysis(); break;
                case 'showJustification': $this->showJustification(); break;
                case 'saveJustification': $this->saveJustification(); break;
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
            } elseif ($data['type'] === 'DSR') {
                $this->render(__DIR__ . '/../Views/create_step2_dsr.php', ['data' => $data]);
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
        } elseif ($data['type'] === 'DSR') {
            foreach ($boq as $item) {
                $qty = (float)($item['qty'] ?? 0);
                $rate = (float)($item['rate'] ?? 0);
                $total += ($qty * $rate);
            }
            return (float)round($total, 2);
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
                } elseif ($type === 'DSR') {
                    if (isset($_POST['items'])) {
                        $db = \App\Core\Database::getInstance()->getConnection();
                        foreach ($_POST['items'] as $item) {
                            if (!empty($item['item_code']) && !empty($item['qty'])) {
                                $itemCode = trim((string)$item['item_code']);
                                $qty = (float)$item['qty'];
                                $rate = (float)$item['rate'];
                                $isMarketItem = !empty($item['is_market_item']) || str_starts_with($itemCode, 'MR');
                                $rateBasis = $isMarketItem 
                                    ? 'MARKET_RATE' 
                                    : (!empty($item['analysis_breakup']) || str_contains($itemCode, 'Analyzed') ? 'ANALYZED_DAR' : 'DSR_2023');

                                // 1. Check if user already provided custom analysis payload
                                $analysisBreakup = null;
                                if (!empty($item['analysis_breakup'])) {
                                    $decoded = json_decode($item['analysis_breakup'], true);
                                    if ($decoded !== null) {
                                        $analysisBreakup = $decoded;
                                    }
                                }

                                // 2. If it's a DSR item and no custom analysis was attached, snapshot baseline DAR breakup now
                                if (!$isMarketItem && $analysisBreakup === null) {
                                    $baseCode = trim(str_replace('(Analyzed)', '', $itemCode));
                                    $stmtBreakup = $db->prepare("
                                        SELECT b.resource_code, b.category, b.description, b.unit, 
                                               b.quantity, b.standard_rate, b.standard_amount, b.cost_basis_qty, b.cost_basis_unit,
                                               COALESCE(g.current_market_rate, g.dsr_base_rate, b.standard_rate) as market_rate
                                        FROM dar_item_breakups b
                                        LEFT JOIN civil_global_rates g ON b.resource_code = g.resource_code
                                        WHERE b.item_code = :c
                                        ORDER BY b.id ASC
                                    ");
                                    $stmtBreakup->execute([':c' => $baseCode]);
                                    $comps = $stmtBreakup->fetchAll(\PDO::FETCH_ASSOC);

                                    if (empty($comps)) {
                                        // Try prefix match (e.g. 4.1 for 4.1.3 or vice-versa)
                                        $prefix = substr($baseCode, 0, strrpos($baseCode, '.') ?: strlen($baseCode));
                                        $stmtBreakup->execute([':c' => $prefix]);
                                        $comps = $stmtBreakup->fetchAll(\PDO::FETCH_ASSOC);
                                    }

                                    if (!empty($comps)) {
                                        $costBasisQty = (float)($comps[0]['cost_basis_qty'] ?? 1.0);
                                        $costBasisUnit = $comps[0]['cost_basis_unit'] ?? ($item['unit'] ?? 'cum');
                                        $stdSum = 0.0;
                                        foreach ($comps as &$c) {
                                            $c['quantity'] = (float)$c['quantity'];
                                            $c['standard_rate'] = (float)$c['standard_rate'];
                                            $c['market_rate'] = (float)$c['market_rate'];
                                            $c['amount'] = round($c['quantity'] * $c['standard_rate'], 2);
                                            $stdSum += $c['amount'];
                                        }
                                        $darFactor = ($stdSum > 0 && $rate > 0) 
                                            ? round(($rate * $costBasisQty) / $stdSum, 5) 
                                            : 1.4085;

                                        $analysisBreakup = [
                                            'base_item_code' => $baseCode,
                                            'description' => trim((string)($item['description'] ?? '')),
                                            'snapshotted_at' => date('Y-m-d H:i:s'),
                                            'cost_basis_qty' => $costBasisQty,
                                            'cost_basis_unit' => $costBasisUnit,
                                            'basic_sum_w' => round($stdSum, 2),
                                            'water_charges_x' => round($stdSum * 1.01, 2),
                                            'dar_factor' => $darFactor,
                                            'calculated_rate' => $rate,
                                            'components' => $comps
                                        ];
                                    }
                                }

                                $boq[] = [
                                    'item_code' => $itemCode,
                                    'description' => trim((string)($item['description'] ?? '')),
                                    'qty' => $qty,
                                    'unit' => trim((string)($item['unit'] ?? 'each')),
                                    'rate' => $rate,
                                    'amount' => round($qty * $rate, 2),
                                    'is_market_item' => $isMarketItem,
                                    'rate_basis' => $rateBasis,
                                    'snapshotted_at' => date('Y-m-d H:i:s'),
                                    'estimate_analysis_breakup' => $analysisBreakup,
                                    'analysis_breakup' => $analysisBreakup,
                                    'justified_rate' => $rate,
                                    'justified_amount' => round($qty * $rate, 2),
                                    'justified_analysis_breakup' => null,
                                    'remarks' => trim((string)($item['remarks'] ?? ''))
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

    private function searchDsr(): void
    {
        header('Content-Type: application/json');
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            echo json_encode([]);
            exit;
        }

        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $terms = array_filter(preg_split('/\s+/', $q));
            $conditions = [];
            $params = [];

            // 1. Direct code or full phrase match
            $conditions[] = "(item_code ILIKE :phrase OR description ILIKE :phrase)";
            $params[':phrase'] = "%{$q}%";

            // 2. If multi-word, also match when all terms appear anywhere in description
            if (count($terms) > 1) {
                $wordConds = [];
                $idx = 0;
                foreach ($terms as $term) {
                    $key = ":w{$idx}";
                    $wordConds[] = "description ILIKE {$key}";
                    $params[$key] = "%{$term}%";
                    $idx++;
                }
                $conditions[] = "(" . implode(" AND ", $wordConds) . ")";
            }

            $sql = "SELECT item_code, sub_head_no, sub_head_name, description, unit, rate, volume, pdf_page
                    FROM dsr_2023
                    WHERE " . implode(" OR ", $conditions) . "
                    ORDER BY 
                        CASE 
                            WHEN item_code = :exact_code THEN 1
                            WHEN item_code ILIKE :prefix_code THEN 2
                            ELSE 3 
                        END,
                        sub_head_no ASC, item_code ASC
                    LIMIT 30";

            $params[':exact_code'] = $q;
            $params[':prefix_code'] = "{$q}%";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode($results);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function getDarAnalysis(): void
    {
        header('Content-Type: application/json');
        $code = trim((string)($_GET['item_code'] ?? ''));
        if ($code === '') {
            echo json_encode(['error' => 'Item code is required.']);
            exit;
        }

        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // 1. Get DSR item info
            $stmtItem = $db->prepare("SELECT item_code, description, unit, rate, sub_head_no, sub_head_name FROM dsr_2023 WHERE item_code = :c LIMIT 1");
            $stmtItem->execute([':c' => $code]);
            $dsrItem = $stmtItem->fetch(\PDO::FETCH_ASSOC);

            // 2. Get DAR breakups joined with civil_global_rates
            $stmtBreakup = $db->prepare("
                SELECT b.id, b.item_code, b.resource_code, b.category, b.description, b.unit, 
                       b.quantity, b.standard_rate, b.standard_amount, b.cost_basis_qty, b.cost_basis_unit,
                       COALESCE(g.current_market_rate, g.dsr_base_rate, b.standard_rate) as market_rate
                FROM dar_item_breakups b
                LEFT JOIN civil_global_rates g ON b.resource_code = g.resource_code
                WHERE b.item_code = :c
                ORDER BY b.id ASC
            ");
            $stmtBreakup->execute([':c' => $code]);
            $components = $stmtBreakup->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($components)) {
                // If no exact match, try prefix (e.g. 4.1 for 4.1.3 or vice-versa)
                $prefix = substr($code, 0, strrpos($code, '.') ?: strlen($code));
                $stmtBreakup->execute([':c' => $prefix]);
                $components = $stmtBreakup->fetchAll(\PDO::FETCH_ASSOC);
            }

            $costBasisQty = 1.0;
            $costBasisUnit = $dsrItem['unit'] ?? 'unit';
            $stdBasicTotal = 0.0;

            foreach ($components as &$comp) {
                $comp['quantity'] = (float)$comp['quantity'];
                $comp['standard_rate'] = (float)$comp['standard_rate'];
                $comp['market_rate'] = (float)$comp['market_rate'];
                $comp['amount'] = round($comp['quantity'] * $comp['market_rate'], 2);
                $stdBasicTotal += ($comp['quantity'] * $comp['standard_rate']);
                if (!empty($comp['cost_basis_qty'])) {
                    $costBasisQty = (float)$comp['cost_basis_qty'];
                    $costBasisUnit = $comp['cost_basis_unit'];
                }
            }

            $dsrRate = (float)($dsrItem['rate'] ?? 0);
            // CPWD DAR Composite Markup Factor = (DSR Rate * cost_basis_qty) / standard_basic_total
            $markupFactor = ($stdBasicTotal > 0 && $dsrRate > 0) 
                ? round(($dsrRate * $costBasisQty) / $stdBasicTotal, 5) 
                : 1.4085; // Standard 1.01 * 1.2127 * 1.15 composite factor

            echo json_encode([
                'success' => true,
                'item_code' => $code,
                'description' => $dsrItem['description'] ?? '',
                'unit' => $dsrItem['unit'] ?? $costBasisUnit,
                'cost_basis_qty' => $costBasisQty,
                'cost_basis_unit' => $costBasisUnit,
                'standard_rate' => $dsrRate,
                'std_basic_total' => $stdBasicTotal,
                'markup_factor' => $markupFactor,
                'components' => $components
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function showJustification(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $aa_es = $this->aaEsService->getAA_ESById($id);
        if (!$aa_es) throw new Exception("AA & ES not found.");

        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/justification.php', [
            'aa_es' => $aa_es,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function saveJustification(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=list');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $aa_es = $this->aaEsService->getAA_ESById($id);
        if (!$aa_es) throw new Exception("AA & ES not found.");

        $items = $_POST['items'] ?? [];
        $boq = $aa_es->boq;
        $totalJustified = 0.0;

        foreach ($boq as $idx => &$boqItem) {
            if (isset($items[$idx])) {
                $justifiedRate = (float)($items[$idx]['justified_rate'] ?? $boqItem['rate']);
                $qty = (float)($boqItem['qty'] ?? 1);
                $justifiedLineAmt = round($qty * $justifiedRate, 2);
                $totalJustified += $justifiedLineAmt;

                $boqItem['justified_rate'] = $justifiedRate;
                $boqItem['justified_amount'] = $justifiedLineAmt;
                $boqItem['justified_at'] = date('Y-m-d H:i:s');

                // Preserve original estimate analysis as baseline if not already explicitly stored
                if (empty($boqItem['estimate_analysis_breakup']) && !empty($boqItem['analysis_breakup'])) {
                    $boqItem['estimate_analysis_breakup'] = $boqItem['analysis_breakup'];
                }

                if (!empty($items[$idx]['analysis_breakup'])) {
                    $decoded = json_decode($items[$idx]['analysis_breakup'], true);
                    if ($decoded !== null) {
                        $boqItem['analysis_breakup'] = $decoded;
                        $boqItem['justified_analysis_breakup'] = $decoded;
                    }
                }
                if (!empty($items[$idx]['justification_remarks'])) {
                    $boqItem['justification_remarks'] = trim($items[$idx]['justification_remarks']);
                }
            } else {
                $rate = (float)($boqItem['justified_rate'] ?? $boqItem['rate'] ?? 0);
                $qty = (float)($boqItem['qty'] ?? 1);
                $totalJustified += round($qty * $rate, 2);
            }
        }

        // Update in DB using PDO
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE aa_es SET boq = :boq, justified_amount = :ja WHERE id = :id");
        $stmt->execute([
            ':boq' => json_encode($boq),
            ':ja' => round($totalJustified, 2),
            ':id' => $id
        ]);

        $_SESSION['message'] = "Justification saved successfully! Grand Justified Amount: ₹" . number_format($totalJustified, 2);
        header("Location: ?action=showJustification&id={$id}");
        exit;
    }
}

$controller = new AA_ESController();
$controller->handleRequest();
