<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\ActivityLog\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/ActivityLogService.php';
require_once __DIR__ . '/../Services/ActivityLogSchemaService.php';
require_once __DIR__ . '/../Services/CloudSyncDiffService.php';

use App\Core\BaseController;
use App\Modules\Superadmin\ActivityLog\Services\ActivityLogService;
use App\Modules\Superadmin\ActivityLog\Services\ActivityLogSchemaService;
use App\Modules\Superadmin\ActivityLog\Services\CloudSyncDiffService;
use Exception;

class ActivityLogController extends BaseController {
    private ActivityLogService $service;
    private CloudSyncDiffService $cloudDiffService;

    public function __construct() {
        parent::__construct('ActivityLog');

        // Initialize Schema
        $schemaService = new ActivityLogSchemaService();
        $schemaService->initSchema();

        $this->service = new ActivityLogService();
        $this->cloudDiffService = new CloudSyncDiffService();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        try {
            $this->checkPermission($action);
            switch ($action) {
                case 'index': $this->index(); break;
                case 'cloudDiff': $this->cloudDiff(); break;
                case 'commitCloudChange': $this->commitCloudChange(); break;
                case 'cancelCloudChange': $this->cancelCloudChange(); break;
                case 'backupManagement': $this->backupManagement(); break;
                case 'backupLocalDb': $this->backupLocalDb(); break;
                case 'restoreLocalDb': $this->restoreLocalDb(); break;
                case 'refreshNeonDb': $this->refreshNeonDb(); break;
                default: throw new Exception("Unknown action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header('Location: ?action=index');
            exit;
        }
    }

    private function index(): void {
        $logs = $this->service->getLogs();
        $this->render(__DIR__ . '/../Views/index.php', ['logs' => $logs]);
    }

    private function cloudDiff(): void {
        $diffs = $this->cloudDiffService->getCloudDiffs();
        $this->render(__DIR__ . '/../Views/cloud_diff.php', ['diffs' => $diffs]);
    }

    private function commitCloudChange(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $table = $_POST['table'] ?? '';
            $id = (int)($_POST['id'] ?? 0);
            $this->cloudDiffService->commitChange($table, $id);
            $_SESSION['message'] = "Cloud data for table '$table' (ID: $id) successfully committed into Local PostgreSQL DB!";
            header('Location: ?action=cloudDiff');
            exit;
        }
    }

    private function cancelCloudChange(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $table = $_POST['table'] ?? '';
            $id = (int)($_POST['id'] ?? 0);
            $this->cloudDiffService->cancelChange($table, $id);
            $_SESSION['message'] = "Cloud data for table '$table' (ID: $id) was cancelled and reverted on Neon DB.";
            header('Location: ?action=cloudDiff');
            exit;
        }
    }

    private function backupManagement(): void {
        $backups = $this->cloudDiffService->listLocalBackups();
        $this->render(__DIR__ . '/../Views/backup_management.php', ['backups' => $backups]);
    }

    private function backupLocalDb(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->cloudDiffService->createLocalBackup();
            $_SESSION['message'] = "Local Database backup created successfully: " . $res['filename'] . " (" . number_format($res['size'] / 1024, 2) . " KB)";
            header('Location: ?action=backupManagement');
            exit;
        }
    }

    private function restoreLocalDb(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filename = $_POST['filename'] ?? '';
            $this->cloudDiffService->restoreLocalBackup($filename);
            $_SESSION['message'] = "Local Database successfully restored from backup: " . htmlspecialchars($filename);
            header('Location: ?action=backupManagement');
            exit;
        }
    }

    private function refreshNeonDb(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->cloudDiffService->refreshNeonFromLocal();
            $_SESSION['message'] = "Neon Cloud DB successfully refreshed with latest local data!";
            header('Location: ?action=backupManagement');
            exit;
        }
    }
}

$controller = new ActivityLogController();
$controller->handleRequest();

