<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\AddressBook\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
            'delete' => ['superuser', 'manager'],
        ];
    }
}
