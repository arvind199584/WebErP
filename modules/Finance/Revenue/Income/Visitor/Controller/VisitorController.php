<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Visitor\Controller;

require_once __DIR__ . '/../../../../../../core/BaseController.php';

use App\Core\BaseController;

class VisitorController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json'));

        if ($isAjax) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'The Visitor module and API are temporarily disabled.'
            ]);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error'] = 'The Visitor module is temporarily disabled and under active development.';
        header('Location: /index.php');
        exit;
    }
}

$controller = new VisitorController();
$controller->handleRequest();
