<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Users\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../DTO/UserDTO.php';
require_once __DIR__ . '/../../Office/Services/OfficeService.php';
require_once __DIR__ . '/../../Noticeboard/Models/NoticeboardModel.php';

use App\Core\BaseController;
use App\Modules\Superadmin\Users\Services\UserService;
use App\Modules\Superadmin\Users\DTO\UserDTO;
use App\Modules\Superadmin\Office\Services\OfficeService;
use App\Modules\Superadmin\Noticeboard\Models\NoticeboardModel;
use Exception;

class UserController extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct('Superadmin/Users');
        $this->userService = new UserService();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'login';

        try {
            // Only check permissions for actions OTHER than login, logout, guest_login, reset_demo
            if (!in_array($action, ['login', 'logout', 'guest_login', 'reset_demo'])) {
                if (!$this->currentUser) {
                    header('Location: ?action=login');
                    exit;
                }
                $this->checkPermission($action);
            }

            switch ($action) {
                case 'login': $this->login(); break;
                case 'guest_login': $this->guestLogin(); break;
                case 'reset_demo': $this->resetDemo(); break;
                case 'logout': $this->logout(); break;
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'showEditForm': $this->showEditForm(); break;
                case 'update': $this->update(); break;
                case 'delete': $this->delete(); break;
                default:
                    throw new Exception("Unknown action requested.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            if ($action === 'login') {
                $noticeModel = new NoticeboardModel();
                $activeNotices = [];
                try {
                    $activeNotices = $noticeModel->getActiveNotices();
                } catch (\Throwable $ignore) {}

                $this->render(__DIR__ . '/../Views/login.php', [
                    'error' => $e->getMessage(),
                    'notices' => $activeNotices
                ], false);
                exit;
            }
            $redirectAction = $this->currentUser ? 'list' : 'login';
            header("Location: ?action=$redirectAction");
            exit;
        }
    }

    private function login(): void
    {
        // If user is already logged in, redirect them away from the login page.
        if ($this->currentUser) {
            header('Location: /index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->userService->authenticate($_POST['usrname'], $_POST['password']);
            if ($user) {
                // Clear any lingering demo mode flag for real logins
                unset($_SESSION['is_demo_guest']);
                $this->userService->startUserSession($user);
                header('Location: /index.php');
                exit;
            } else {
                $_SESSION['error'] = 'Invalid username or password.';
            }
        }

        $noticeModel = new NoticeboardModel();
        $activeNotices = [];
        try {
            $activeNotices = $noticeModel->getActiveNotices();
        } catch (\Throwable $ignore) {}

        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        // Render rich login page WITHOUT the main layout
        $this->render(__DIR__ . '/../Views/login.php', [
            'notices' => $activeNotices,
            'error' => $error
        ], false);
    }

    private function guestLogin(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../../../../sessions_new';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }
            session_save_path($sessionPath);
            session_start();
        }

        session_regenerate_id(true);

        // Flag demo sandbox mode
        $_SESSION['is_demo_guest'] = true;

        try {
            $demoDb = \App\Core\Database::getInstance(true)->getConnection();
            $stmt = $demoDb->prepare("SELECT * FROM public.users WHERE usrname = 'guest_demo' LIMIT 1");
            $stmt->execute();
            $userData = $stmt->fetch();

            if ($userData) {
                $_SESSION['user'] = [
                    'id' => (int)$userData['id'],
                    'username' => $userData['usrname'],
                    'fullname' => $userData['firstname'] . ' ' . ($userData['lastname'] ?? ''),
                    'role' => $userData['role'],
                    'officeid' => (int)$userData['officeid'],
                    'is_demo' => true
                ];
            }
        } catch (\Throwable $e) {
            error_log("Guest login lookup failed: " . $e->getMessage());
        }

        if (empty($_SESSION['user'])) {
            $_SESSION['user'] = [
                'id' => 2,
                'username' => 'guest_demo',
                'fullname' => 'Guest Evaluator',
                'role' => 'superuser',
                'officeid' => 1,
                'is_demo' => true
            ];
        }

        $_SESSION['message'] = "Welcome to the Enterprise ERP Demo Sandbox! You can freely explore, create, edit, and test all modules.";
        header('Location: /index.php');
        exit;
    }

    private function resetDemo(): void
    {
        require_once __DIR__ . '/../../../../scripts/restore_demo_db.php';
        $res = restoreDemoDatabase();
        if ($res['success']) {
            $_SESSION['message'] = "Demo database has been successfully restored to its pristine initial state!";
        } else {
            $_SESSION['error'] = "Failed to restore demo database: " . ($res['error'] ?? 'Unknown error');
        }
        header('Location: /index.php');
        exit;
    }

    private function logout(): void
    {
        $wasDemo = !empty($_SESSION['is_demo_guest']);
        $this->userService->logout();
        if ($wasDemo) {
            require_once __DIR__ . '/../../../../scripts/restore_demo_db.php';
            restoreDemoDatabase();
        }
        header('Location: ?action=login');
        exit;
    }

    private function list(): void
    {
        $users = $this->userService->getAllUsers();
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'users' => $users,
            'message' => $message,
            'error' => $error
        ]);
    }

    private function showCreateForm(): void
    {
        $officeService = new OfficeService();
        $offices = $officeService->getAllOffices();

        $this->render(__DIR__ . '/../Views/create.php', ['offices' => $offices]);
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userDTO = UserDTO::fromRequest($_POST);
            $this->userService->createUser($userDTO);
            $_SESSION['message'] = 'User created successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function showEditForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userService->getUserById($id);
        if (!$user) throw new Exception("User not found.");

        $officeService = new OfficeService();
        $offices = $officeService->getAllOffices();

        $this->render(__DIR__ . '/../Views/edit.php', [
            'user' => $user,
            'offices' => $offices
        ]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userDTO = UserDTO::fromRequest($_POST);
            $this->userService->updateUser($userDTO);
            $_SESSION['message'] = 'User updated successfully!';
            header('Location: ?action=list');
            exit;
        }
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->userService->deleteUser($id);
        $_SESSION['message'] = 'User deleted successfully!';
        header('Location: ?action=list');
        exit;
    }
}

// Global runner entrypoint for UserController
$controller = new UserController();
$controller->handleRequest();
