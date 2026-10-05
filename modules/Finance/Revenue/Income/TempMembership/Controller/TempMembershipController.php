<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\TempMembership\Controller;

use App\Core\BaseController;

class TempMembershipController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>TempMembership Module</h1><p>Coming Soon...</p>';
    }
}