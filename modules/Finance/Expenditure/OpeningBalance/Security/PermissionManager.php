<?php
declare(strict_types=1);
namespace App\Modules\Finance\Expenditure\OpeningBalance\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'edit' => ['superuser', 'manager', 'staff'], // Staff can view, but controller logic prevents saving
        ];
    }
}
