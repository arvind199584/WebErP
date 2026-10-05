<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Visitor\Controller;

use App\Core\BaseController;

class VisitorController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>Visitor Module</h1><p>Coming Soon...</p>';
    }
}