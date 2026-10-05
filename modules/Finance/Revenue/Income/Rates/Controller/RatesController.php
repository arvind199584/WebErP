<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Rates\Controller;

use App\Core\BaseController;

class RatesController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>Rates Module</h1><p>Coming Soon...</p>';
    }
}