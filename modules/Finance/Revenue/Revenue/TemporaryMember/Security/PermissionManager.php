<?php
declare(strict_types=1);
namespace App\Modules\Finance\Revenue\Revenue\TemporaryMember\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'list' => ['superuser', 'manager', 'staff'],
            'showCreate' => ['superuser', 'manager'],
            'create' => ['superuser', 'manager'],
            'showEdit' => ['superuser', 'manager'],
            'update' => ['superuser', 'manager'],
            'delete' => ['superuser', 'manager'],
        ];
    }
}
