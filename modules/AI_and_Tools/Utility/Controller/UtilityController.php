<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\Utility\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/NLPService.php';
require_once __DIR__ . '/../Services/PendencyService.php';
require_once __DIR__ . '/../Services/BudgetTrackingService.php';

use App\Core\BaseController;
use App\Modules\AI_and_Tools\Utility\Services\NLPService;
use App\Modules\AI_and_Tools\Utility\Services\PendencyService;
use App\Modules\AI_and_Tools\Utility\Services\BudgetTrackingService;
use Exception;

class UtilityController extends BaseController {
    private NLPService $nlpService;
    private PendencyService $pendencyService;
    private BudgetTrackingService $budgetService;

    public function __construct() {
        parent::__construct('AI_and_Tools/Utility');
        $this->nlpService = new NLPService();
        $this->pendencyService = new PendencyService();
        $this->budgetService = new BudgetTrackingService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index': $this->index(); break;
                case 'pendency': $this->pendency(); break;
                case 'budget_tracking': $this->budgetTracking(); break;
                case 'nlp': $this->nlpTool(); break;
                case 'train_nlp': $this->trainNlp(); break;
                case 'normalize_sql_ajax': $this->normalizeSqlAjax(); break;
                case 'merge': $this->render(__DIR__ . '/../Views/merge.php'); break;
                case 'compress': $this->render(__DIR__ . '/../Views/compress.php'); break;
                case 'convert_word': $this->render(__DIR__ . '/../Views/convert_word.php'); break;
                case 'convert_excel': $this->render(__DIR__ . '/../Views/convert_excel.php'); break;
                case 'compress_jpeg': $this->render(__DIR__ . '/../Views/compress_jpeg.php'); break;
                case 'pdf_to_jpeg': $this->render(__DIR__ . '/../Views/pdf_to_jpeg.php'); break;
                case 'jpeg_to_pdf': $this->render(__DIR__ . '/../Views/jpeg_to_pdf.php'); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function index(): void {
        $this->render(__DIR__ . '/../Views/index.php');
    }

    private function pendency(): void {
        $pendencies = $this->pendencyService->getPendencies((int)$this->currentUser['officeid'], $this->currentUser['role']);
        $this->render(__DIR__ . '/../Views/pendency.php', ['pendencies' => $pendencies]);
    }

    private function budgetTracking(): void {
        $today = new \DateTime();
        $currentMonth = (int)$today->format('m');
        $currentYear = (int)$today->format('Y');
        $fyStartYear = ($currentMonth <= 3) ? $currentYear - 1 : $currentYear;

        $fyStart = $_GET['fy_start'] ?? "$fyStartYear-04-01";
        $fyEnd = $_GET['fy_end'] ?? ($fyStartYear + 1) . "-03-31";

        $report = $this->budgetService->getBudgetTable((int)$this->currentUser['officeid'], $fyStart, $fyEnd);
        $trend = $this->budgetService->getMonthlyTrendWithProjections((int)$this->currentUser['officeid'], $fyStart, $fyEnd);

        // NEW: BUDGET-WISE PERCENTAGE TREND
        $budgetTrend = $this->budgetService->getBudgetWiseTrend((int)$this->currentUser['officeid'], $fyStart, $fyEnd);

        $this->render(__DIR__ . '/../Views/budget_tracking.php', [
            'report' => $report,
            'trend' => $trend,
            'budgetTrend' => $budgetTrend,
            'fyStart' => $fyStart,
            'fyEnd' => $fyEnd
        ]);
    }

    private function nlpTool(): void {
        $response = null;
        $query = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $query = $_POST['query'] ?? '';
            if ($query) {
                $response = $this->nlpService->processQuery($query);
            }
        }
        $this->render(__DIR__ . '/../Views/nlp_tool.php', ['response' => $response, 'query' => $query]);
    }

    private function trainNlp(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $text = $_POST['text'] ?? '';
            $sql = $_POST['sql'] ?? '';
            if ($text && $sql) {
                $this->nlpService->saveTrainingExample($text, $sql);
                $_SESSION['message'] = "AI trained successfully!";
            }
        }
        header("Location: ?action=nlp");
        exit;
    }

    private function normalizeSqlAjax(): void {
        header('Content-Type: application/json');
        $fuzzySql = $_GET['sql'] ?? '';
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post('http://127.0.0.1:5000/normalize-sql', [
                'json' => ['sql' => $fuzzySql, 'schema' => []]
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            echo json_encode(['exact_sql' => $result['exact_sql'] ?? $fuzzySql]);
        } catch (\Exception $e) {
            echo json_encode(['exact_sql' => $fuzzySql]);
        }
        exit;
    }
}

$controller = new UtilityController();
$controller->handleRequest();
