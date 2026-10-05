<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\ESIC\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
            'store' => ['superuser', 'manager', 'staff'],
            'delete' => ['superuser'],
        ];
    }
}
