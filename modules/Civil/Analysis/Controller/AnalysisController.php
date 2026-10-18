<?php
declare(strict_types=1);

namespace App\Modules\Civil\Analysis\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/AnalysisService.php';
require_once __DIR__ . '/../../Estimates/Services/EstimateService.php';

use App\Core\BaseController;
use App\Modules\Civil\Analysis\Services\AnalysisService;
use App\Modules\Civil\Estimates\Services\EstimateService;
use Exception;

class AnalysisController extends BaseController
{
    private AnalysisService $service;
    private EstimateService $estimateService;

    public function __construct()
    {
        parent::__construct('Civil/Analysis');
        $this->service = new AnalysisService();
        $this->estimateService = new EstimateService();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'list';

        try {
            $this->checkPermission($action);

            switch ($action) {
                case 'list':
                case 'index':
                    $this->list();
                    break;
                case 'calculate':
                    $this->calculate();
                    break;
                case 'save':
                    $this->save();
                    break;
                case 'viewStatement':
                    $this->viewStatement();
                    break;
                default:
                    throw new Exception("Unknown action '{$action}' requested.");
            }
        } catch (\Throwable $e) {
            $this->renderErrorScreen($e, $action);
        }
    }

    private function list(): void
    {
        $officeId = ($this->currentUser['role'] === 'superuser') ? null : (int)$this->currentUser['officeid'];
        $justifications = $this->service->getAllJustifications($officeId);
        $estimates = $this->estimateService->getAllEstimates($officeId);

        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'justifications' => $justifications,
            'estimates' => $estimates,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function calculate(): void
    {
        $estimateId = (int)($_GET['estimate_id'] ?? 0);
        $officeId = ($this->currentUser['role'] === 'superuser') ? null : (int)$this->currentUser['officeid'];
        $estimates = $this->estimateService->getAllEstimates($officeId);

        if ($estimateId === 0 && !empty($estimates)) {
            $estimateId = (int)$estimates[0]['id'];
        }

        $waterPct = (float)($_GET['water_charges'] ?? 1.0);
        $cpOhPct = (float)($_GET['cp_oh'] ?? 15.0);
        $gstPct = (float)($_GET['gst'] ?? 18.0);
        $cessPct = (float)($_GET['cess'] ?? 1.0);

        $analysisData = ($estimateId > 0)
            ? $this->service->computeJustification($estimateId, $waterPct, $cpOhPct, $gstPct, $cessPct)
            : null;

        $this->render(__DIR__ . '/../Views/calculate.php', [
            'estimateId' => $estimateId,
            'estimates' => $estimates,
            'analysis' => $analysisData,
            'waterPct' => $waterPct,
            'cpOhPct' => $cpOhPct,
            'gstPct' => $gstPct,
            'cessPct' => $cessPct,
        ]);
    }

    private function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method.");
        }

        $estimateId = (int)$_POST['estimate_id'];
        $waterPct = (float)($_POST['water_charges_percent'] ?? 1.0);
        $cpOhPct = (float)($_POST['cp_oh_percent'] ?? 15.0);
        $gstPct = (float)($_POST['gst_percent'] ?? 18.0);
        $cessPct = (float)($_POST['cess_percent'] ?? 1.0);

        // Recompute fresh analysis
        $computed = $this->service->computeJustification($estimateId, $waterPct, $cpOhPct, $gstPct, $cessPct);
        $est = $computed['estimate'];
        $summary = $computed['summary'];

        $justificationNo = trim((string)$_POST['justification_no']);
        if ($justificationNo === '') {
            $justificationNo = 'JUST/' . $est['estimate_no'] . '/' . date('Ymd');
        }

        $saveData = [
            'officeid' => (int)$est['officeid'],
            'estimate_id' => $estimateId,
            'justification_no' => $justificationNo,
            'title' => 'Rate Justification for ' . $est['work_name'],
            'estimated_cost' => $summary['total_estimated_cost'],
            'justified_cost' => $summary['total_justified_cost'],
            'justification_percentage' => $summary['justification_percentage'],
            'water_charges_percent' => $waterPct,
            'cp_oh_percent' => $cpOhPct,
            'gst_percent' => $gstPct,
            'cess_percent' => $cessPct,
            'item_breakdowns' => $computed['items'],
            'status' => 'Approved'
        ];

        $savedId = $this->service->saveJustificationStatement($saveData, (int)$this->currentUser['id']);
        $_SESSION['message'] = "Rate Justification Statement '{$justificationNo}' saved successfully!";
        header("Location: ?action=viewStatement&id={$savedId}");
        exit;
    }

    private function viewStatement(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $statement = $this->service->getJustificationDetails($id);

        $this->render(__DIR__ . '/../Views/justification_statement.php', [
            'statement' => $statement
        ]);
    }
}

$controller = new AnalysisController();
$controller->handleRequest();
