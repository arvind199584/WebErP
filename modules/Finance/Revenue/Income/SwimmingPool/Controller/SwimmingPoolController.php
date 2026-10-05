<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\SwimmingPool\Controller;

use App\Core\BaseController;

class SwimmingPoolController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>SwimmingPool Module</h1><p>Coming Soon...</p>';
    }
}