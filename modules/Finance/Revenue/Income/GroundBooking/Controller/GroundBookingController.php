<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Income\GroundBooking\Controller;

use App\Core\BaseController;

class GroundBookingController extends BaseController {
    public function __construct() {
        parent::__construct('Income');
    }
    public function handleRequest(): void {
        echo '<h1>GroundBooking Module</h1><p>Coming Soon...</p>';
    }
}