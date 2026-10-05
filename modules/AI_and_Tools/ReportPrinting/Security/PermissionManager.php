<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\ReportPrinting\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'view' => ['superuser', 'manager', 'staff'], // Everyone can view reports
        ];
    }
}
