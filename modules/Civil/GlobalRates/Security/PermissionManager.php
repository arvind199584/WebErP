<?php
declare(strict_types=1);

namespace App\Modules\Civil\GlobalRates\Security;

class PermissionManager
{
    public static function getPermissions(): array
    {
        return [
            'list' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'index' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'search' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'updateRate' => ['superuser', 'manager', 'admin'],
            'importExcel' => ['superuser', 'manager', 'admin'],
            'exportExcel' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
        ];
    }
}
