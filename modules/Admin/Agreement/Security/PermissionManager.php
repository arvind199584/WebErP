<?php
declare(strict_types=1);
namespace App\Modules\Admin\Agreement\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'index' => ['superuser', 'manager', 'staff'],
            'showCreateForm' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
            'showEditForm' => ['superuser', 'manager'],
            'update' => ['superuser', 'manager'],
            'delete' => ['superuser'],
            'updateScope' => ['superuser', 'manager'],
        ];
    }
}
