<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Users\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../DTO/UserDTO.php';
require_once __DIR__ . '/../../Office/Services/OfficeService.php';

use App\Core\BaseController;
use App\Modules\Superadmin\Users\Services\UserService;
use App\Modules\Superadmin\Users\DTO\UserDTO;
use App\Modules\Superadmin\Office\Services\OfficeService;
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
            // Only check permissions for actions OTHER than login and logout
            if (!in_array($action, ['login', 'logout'])) {
                if (!$this->currentUser) {
                    header('Location: ?action=login');
                    exit;
                }
                $this->checkPermission($action);
            }

            switch ($action) {
                case 'login': $this->login(); break;
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
                $this->render(__DIR__ . '/../Views/login.php', ['error' => $e->getMessage()], false);
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
                $this->userService->startUserSession($user);
                header('Location: /index.php');
                exit;
            } else {
                $_SESSION['error'] = 'Invalid username or password.';
            }
        }

        // Render login page WITHOUT the main layout
        $this->render(__DIR__ . '/../Views/login.php', [], false);
    }

    private function logout(): void
    {
        $this->userService->logout();
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
        $_SESSION['message'] = 'User deleted successfully.';
        header('Location: ?action=list');
        exit;
    }
}

$controller = new UserController();
$controller->handleRequest();
