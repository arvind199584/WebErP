<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\Agency\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            // ALLOWED FOR EVERYONE
            'list' => ['superuser', 'manager', 'staff'],
            'index' => ['superuser', 'manager', 'staff'],
            'showCreateForm' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],

            // RESTRICTED TO SUPERUSER ONLY
            'showEditForm' => ['superuser'],
            'update' => ['superuser'],
            'delete' => ['superuser'],
        ];
    }
}
