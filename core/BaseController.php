<?php

declare(strict_types=1);

namespace App\Core;

require_once __DIR__ . '/../modules/Superadmin/Users/Services/UserService.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CSRFManager.php';

use App\Modules\Superadmin\Users\Services\UserService;
use App\Core\Database;
use App\Core\CSRFManager;
use Exception;
use PDO;

abstract class BaseController
{
    protected ?array $currentUser;
    protected string $moduleName;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;

        // --- GLOBAL SECURITY HEADERS ---
        if (!headers_sent()) {
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: SAMEORIGIN");
            header("X-XSS-Protection: 1; mode=block");
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
            header("Referrer-Policy: strict-origin-when-cross-origin");
        }

        if (session_status() == PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../sessions_new';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }
            session_save_path($sessionPath);

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }

        // --- GLOBAL CSRF VALIDATION ---
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = null;
            
            // 1. Check Standard Form Post
            if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            } 
            // 2. Check JSON/Raw Payload (for AJAX)
            else {
                $rawInput = json_decode(file_get_contents('php://input'), true);
                if (isset($rawInput['csrf_token'])) {
                    $token = $rawInput['csrf_token'];
                }
            }

            if (!CSRFManager::validateToken($token)) {
                http_response_code(403);
                die("Security Error: CSRF token validation failed.");
            }
        }

        $this->currentUser = UserService::getCurrentUser();

        if ($this->currentUser) {
            $this->setDatabaseContext();
        }
    }

    private function setDatabaseContext(): void
    {
        try {
            $db = Database::getInstance()->getConnection();

            $officeId = (int)$this->currentUser['officeid'];
            $role = $this->currentUser['role'];
            $userId = (int)$this->currentUser['id'];

            // PostgreSQL session variables
            $db->prepare("SELECT set_config('app.current_office_id', :officeId, false)")->execute(['officeId' => (string)$officeId]);
            $db->prepare("SELECT set_config('app.current_user_role', :role, false)")->execute(['role' => $role]);
            $db->prepare("SELECT set_config('app.current_user_id', :userId, false)")->execute(['userId' => (string)$userId]);

        } catch (Exception $e) {
            die("Security Context Error: " . $e->getMessage());
        }
    }

    protected function checkPermission(string $action): void
    {
        // Skip permission check for login/logout to avoid loops
        if (in_array($action, ['login', 'logout'])) {
            return;
        }

        // ROBUST ROLE CHECK: Trim and Lowercase
        $userRole = strtolower(trim($this->currentUser['role'] ?? 'guest'));

        // Superusers bypass all permission checks globally
        if ($userRole === 'superuser') {
            return;
        }

        // --- SUPERVISOR ROLE ENFORCEMENT (READ-ONLY) ---
        // Supervisors can only view data within their office and cannot perform any write/mutating actions.
        if ($userRole === 'supervisor') {
            $writeActions = [
                'create', 'showcreateform', 'showeditform', 'edit', 'update', 
                'delete', 'deleteall', 'import', 'upload', 'process_batch', 
                'applyauditfixes', 'updatescope', 'save', 'store', 'destroy',
                'createlogs', 'validatestagedlog', 'createbulkreceipt', 'createbulkindent',
                'addcustomservicetype'
            ];
            $actionLower = strtolower($action);
            if (in_array($actionLower, $writeActions) || (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !str_starts_with($actionLower, 'get'))) {
                throw new Exception("Permission Denied: Supervisor role has read-only access and cannot perform write/modify action '{$action}'.");
            }
        }

        $normalizedNs = str_replace('/', '\\', $this->moduleName);
        $normalizedPath = str_replace('\\', '/', $this->moduleName);

        $permissionManagerClass = "App\\Modules\\{$normalizedNs}\\Security\\PermissionManager";
        $permissionFile = __DIR__ . "/../modules/{$normalizedPath}/Security/PermissionManager.php";

        if (!file_exists($permissionFile)) {
            throw new Exception("Security definition file not found for module '{$this->moduleName}'.");
        }
        require_once $permissionFile;

        if (!class_exists($permissionManagerClass)) {
            throw new Exception("PermissionManager class not found for module '{$this->moduleName}'.");
        }

        $permissions = $permissionManagerClass::getPermissions();

        // Check if role is allowed directly
        $allowedRoles = $permissions[$action] ?? [];
        $hasAccess = in_array($userRole, $allowedRoles);

        // Supervisors inherit read access wherever staff is permitted for view/read actions
        if (!$hasAccess && $userRole === 'supervisor') {
            $readOnlyActions = [
                'list', 'index', 'status', 'viewdetails', 'details', 'show', 
                'view', 'exporttemplate', 'getformdata', 'getpreviousreading', 
                'getchartdata', 'getmachinelogdata', 'getmachineauditdata', 
                'getofficemachines', 'getmonthwisemachinestats', 'getspareparts', 
                'getmachinedetails', 'jobcards'
            ];
            if (in_array(strtolower($action), $readOnlyActions) && (in_array('staff', $allowedRoles) || in_array('supervisor', $allowedRoles))) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            if ($userRole === 'guest') {
                header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
                exit;
            } else {
                throw new Exception("Permission Denied: Your role ('{$userRole}') is not allowed to perform the action '{$action}' in module '{$this->moduleName}'.");
            }
        }
    }

    /**
     * Unified exception and error handler for all controllers.
     * Prevents infinite redirect loops and renders a detailed debug/error screen in the browser.
     */
    public function handleException(\Throwable $e, string $currentAction = '', string $fallbackAction = 'list'): void
    {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json'));

        $isPermissionDenied = stripos($e->getMessage(), 'permission denied') !== false;
        $statusCode = $isPermissionDenied ? 403 : 500;

        if ($isAjax) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]);
            exit;
        }

        // Loop detection: if already on fallback action or redirect loop suspected
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $redirectKey = 'loop_guard_' . md5($requestUri);
        $redirectCount = (int)($_SESSION[$redirectKey] ?? 0) + 1;
        $_SESSION[$redirectKey] = $redirectCount;

        $isSameAction = empty($currentAction) || (strtolower($currentAction) === strtolower($fallbackAction));

        if ($isSameAction || $redirectCount > 1) {
            // Unset loop guard and render browser error screen immediately
            unset($_SESSION[$redirectKey]);
            $this->renderErrorScreen($e, $currentAction, $statusCode);
            exit;
        }

        // Otherwise safely flash error to session and redirect
        $_SESSION['error'] = $e->getMessage();
        header("Location: ?action={$fallbackAction}");
        exit;
    }

    /**
     * Renders a styled, informative in-browser error and debug screen.
     */
    protected function renderErrorScreen(\Throwable $e, string $action, int $statusCode = 500): void
    {
        http_response_code($statusCode);
        $userName = htmlspecialchars($this->currentUser['usrname'] ?? 'Guest');
        $userRole = htmlspecialchars(ucfirst($this->currentUser['role'] ?? 'guest'));
        $officeId = htmlspecialchars((string)($this->currentUser['officeid'] ?? 'None'));
        $module = htmlspecialchars($this->moduleName);
        $actionName = htmlspecialchars($action ?: 'default');
        $errorMessage = htmlspecialchars($e->getMessage());
        $errorFile = htmlspecialchars($e->getFile());
        $errorLine = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString());
        $is403 = ($statusCode === 403);
        $headerBg = $is403 ? 'bg-warning bg-opacity-75 text-dark' : 'bg-danger text-white';
        $icon = $is403 ? 'shield-lock' : 'exclamation-octagon';
        $title = $is403 ? 'Access Denied (403)' : 'Application Error (500)';
        $alertType = $is403 ? 'warning' : 'danger';

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - {$module} | Daredevil — an Autarchic Maverick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .error-card { max-width: 850px; margin: 40px auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); overflow: hidden; border: none; }
        .error-header { padding: 24px 30px; }
        .error-body { padding: 30px; background: #fff; }
        .debug-box { background: #212529; color: #f8f9fa; border-radius: 8px; padding: 15px; font-family: Consolas, monospace; font-size: 0.85rem; max-height: 250px; overflow-y: auto; }
        .badge-role { font-size: 0.85rem; padding: 6px 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card error-card">
            <div class="error-header {$headerBg}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-1"><i class="bi bi-{$icon}-fill me-2"></i> {$title}</h3>
                        <p class="mb-0 opacity-75">An issue occurred while processing your request.</p>
                    </div>
                    <span class="badge bg-dark badge-role text-white">Office #{$officeId} | {$userRole}</span>
                </div>
            </div>
            <div class="error-body">
                <div class="alert alert-{$alertType} d-flex align-items-start" role="alert">
                    <i class="bi bi-info-circle-fill fs-4 me-3 mt-1"></i>
                    <div>
                        <h5 class="alert-heading mb-1">What went wrong?</h5>
                        <p class="mb-0">{$errorMessage}</p>
                    </div>
                </div>

                <div class="card bg-light border-0 mb-4">
                    <div class="card-body py-2 px-3">
                        <div class="row g-2 text-muted small">
                            <div class="col-md-4"><strong>User:</strong> {$userName} ({$userRole})</div>
                            <div class="col-md-4"><strong>Module:</strong> {$module}</div>
                            <div class="col-md-4"><strong>Action:</strong> <code>{$actionName}</code></div>
                        </div>
                    </div>
                </div>

                <div class="accordion mb-4" id="debugAccordion">
                    <div class="accordion-item border">
                        <h2 class="accordion-header" id="headingDebug">
                            <button class="accordion-button collapsed py-2 text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug">
                                <i class="bi bi-code-square me-2"></i> Technical Debug Details & Stack Trace
                            </button>
                        </h2>
                        <div id="collapseDebug" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                            <div class="accordion-body bg-light">
                                <p class="mb-1 small text-muted"><strong>File:</strong> {$errorFile} (Line: {$errorLine})</p>
                                <pre class="debug-box mt-2 mb-0"><code>{$trace}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button onclick="window.history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Go Back
                    </button>
                    <div>
                        <a href="/index.php" class="btn btn-primary">
                            <i class="bi bi-house-door me-1"></i> Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
    }

    protected function render(string $viewPath, array $data = [], bool $useLayout = true): void
    {
        if (!empty($data)) {
            extract($data);
        }

        if ($useLayout) {
            require_once __DIR__ . '/Layout.php';
        } else {
            require_once $viewPath;
        }
    }

    abstract public function handleRequest(): void;
}
