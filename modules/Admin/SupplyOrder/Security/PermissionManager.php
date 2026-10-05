<?php
declare(strict_types=1);
namespace App\Modules\Admin\SupplyOrder\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
            'store' => ['superuser', 'manager', 'staff'],
            'edit' => ['superuser', 'manager', 'staff'],
            'update' => ['superuser', 'manager', 'staff'],
            'delete' => ['superuser'],
        ];
    }
}
