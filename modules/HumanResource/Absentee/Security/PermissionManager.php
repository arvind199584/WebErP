<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Absentee\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'list' => ['superuser', 'manager', 'staff'],
            'mark' => ['superuser', 'manager', 'staff'],
            'saveMark' => ['superuser', 'manager', 'staff'],
            'edit' => ['superuser', 'manager'],
            'saveSheet' => ['superuser', 'manager'],
        ];
    }
}
