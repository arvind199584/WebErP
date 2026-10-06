<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Noticeboard\Controller;

require_once __DIR__ . '/../../../../core/BaseController.php';
require_once __DIR__ . '/../Models/NoticeboardModel.php';

use App\Core\BaseController;
use App\Modules\Superadmin\Noticeboard\Models\NoticeboardModel;
use Exception;

class NoticeboardController extends BaseController
{
    private NoticeboardModel $noticeModel;

    public function __construct()
    {
        parent::__construct('Superadmin/Noticeboard');
        $this->noticeModel = new NoticeboardModel();
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'list';

        try {
            if (!$this->currentUser) {
                header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
                exit;
            }

            switch ($action) {
                case 'list': $this->list(); break;
                case 'showCreateForm': $this->showCreateForm(); break;
                case 'create': $this->create(); break;
                case 'showEditForm': $this->showEditForm(); break;
                case 'update': $this->update(); break;
                case 'delete': $this->delete(); break;
                default:
                    throw new Exception("Unknown noticeboard action.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?action=list");
            exit;
        }
    }

    private function list(): void
    {
        $notices = $this->noticeModel->getAllNotices();
        $message = $_SESSION['message'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);

        $this->render(__DIR__ . '/../Views/index.php', [
            'notices' => $notices,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function showCreateForm(): void
    {
        $this->render(__DIR__ . '/../Views/create.php');
    }

    private function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=list');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $badge_type = $_POST['badge_type'] ?? 'Announcement';
        $priority = (int)($_POST['priority'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Title and content are required.';
            header('Location: ?action=showCreateForm');
            exit;
        }

        $this->noticeModel->create([
            'title' => $title,
            'content' => $content,
            'badge_type' => $badge_type,
            'priority' => $priority,
            'is_active' => $is_active,
            'created_by' => $this->currentUser['id'] ?? null,
        ]);

        $_SESSION['message'] = 'Notice published successfully!';
        header('Location: ?action=list');
        exit;
    }

    private function showEditForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $notice = $this->noticeModel->getById($id);
        if (!$notice) {
            $_SESSION['error'] = 'Notice not found.';
            header('Location: ?action=list');
            exit;
        }

        $this->render(__DIR__ . '/../Views/edit.php', ['notice' => $notice]);
    }

    private function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=list');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $badge_type = $_POST['badge_type'] ?? 'Announcement';
        $priority = (int)($_POST['priority'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Title and content are required.';
            header("Location: ?action=showEditForm&id=$id");
            exit;
        }

        $this->noticeModel->update($id, [
            'title' => $title,
            'content' => $content,
            'badge_type' => $badge_type,
            'priority' => $priority,
            'is_active' => $is_active,
        ]);

        $_SESSION['message'] = 'Notice updated successfully!';
        header('Location: ?action=list');
        exit;
    }

    private function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->noticeModel->delete($id);
            $_SESSION['message'] = 'Notice deleted successfully.';
        }
        header('Location: ?action=list');
        exit;
    }
}

// Controller runner
$controller = new NoticeboardController();
$controller->handleRequest();
