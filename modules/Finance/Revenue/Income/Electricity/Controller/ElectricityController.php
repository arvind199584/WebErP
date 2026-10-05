<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Electricity\Controller;

use App\Core\BaseController;

class ElectricityController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>Electricity Module</h1><p>Coming Soon...</p>';
    }
}