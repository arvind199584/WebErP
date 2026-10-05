<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\PaynPlay\Controller;

use App\Core\BaseController;

class PaynPlayController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>PaynPlay Module</h1><p>Coming Soon...</p>';
    }
}