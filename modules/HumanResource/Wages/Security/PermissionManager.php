<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Wages\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'list' => ['superuser', 'manager', 'staff'],
            'calculate' => ['superuser', 'manager', 'staff'],
            'save' => ['superuser', 'manager', 'staff'],
            'verify' => ['superuser', 'manager', 'staff'],
        ];
    }
}
