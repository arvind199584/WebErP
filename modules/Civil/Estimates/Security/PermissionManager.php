<?php
declare(strict_types=1);

namespace App\Modules\Civil\Estimates\Security;

class PermissionManager
{
    public static function getPermissions(): array
    {
        return [
            'list' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'index' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'showCreateForm' => ['superuser', 'manager', 'admin'],
            'create' => ['superuser', 'manager', 'admin'],
            'view' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'boqBuilder' => ['superuser', 'manager', 'admin'],
            'searchDsr' => ['superuser', 'manager', 'admin', 'supervisor', 'staff'],
            'addItem' => ['superuser', 'manager', 'admin'],
            'deleteItem' => ['superuser', 'manager', 'admin'],
            'updateStatus' => ['superuser', 'manager', 'admin'],
        ];
    }
}
