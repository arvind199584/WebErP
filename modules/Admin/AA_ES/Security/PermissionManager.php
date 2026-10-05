<?php
declare(strict_types=1);
namespace App\Modules\Admin\AA_ES\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'index' => ['superuser', 'manager', 'staff'],
            'showCreateForm' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
            'view' => ['superuser', 'manager', 'staff'],
            'showEditForm' => ['superuser', 'manager'],
            'approve' => ['superuser', 'manager'],
            'revert' => ['superuser'],
            'delete' => ['superuser'],
        ];
    }
}
