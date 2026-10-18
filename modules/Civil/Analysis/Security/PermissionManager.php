<?php
declare(strict_types=1);

namespace App\Modules\Civil\Analysis\Security;

class PermissionManager
{
    public static function getPermissions(): array
    {
        return [
            'list' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'index' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'calculate' => ['superuser', 'manager', 'admin'],
            'save' => ['superuser', 'manager', 'admin'],
            'viewStatement' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'delete' => ['superuser', 'manager'],
        ];
    }
}
