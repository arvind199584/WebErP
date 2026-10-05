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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        if (!isset($permissions[$action]) || !in_array($userRole, $permissions[$action])) {
            if ($userRole === 'guest') {
                header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
                exit;
            } else {
                throw new Exception("Permission Denied: Your role ('{$userRole}') is not allowed to perform the action '{$action}'.");
            }
        }
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
