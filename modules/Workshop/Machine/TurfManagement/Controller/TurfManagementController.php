<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Controller;

require_once __DIR__ . '/../../../../../core/BaseController.php';

use App\Core\BaseController;

class TurfManagementController extends BaseController {

    public function __construct() {
        parent::__construct('TurfManagement');
    }

    public function handleRequest(): void {
        $this->render(__DIR__ . '/../Views/index.php');
    }
}

$controller = new TurfManagementController();
$controller->handleRequest();
