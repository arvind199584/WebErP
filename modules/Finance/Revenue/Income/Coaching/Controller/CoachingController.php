<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\Coaching\Controller;

use App\Core\BaseController;

class CoachingController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>Coaching Module</h1><p>Coming Soon...</p>';
    }
}