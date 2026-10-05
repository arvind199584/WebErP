<?php
declare(strict_types=1);
namespace App\Modules\Finance\Budget\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager'],
            'index' => ['superuser', 'manager'],
            'showCreateForm' => ['superuser', 'manager'],
            'create' => ['superuser', 'manager'],
            'showEditForm' => ['superuser', 'manager'],
            'update' => ['superuser', 'manager'],
            'delete' => ['superuser'],
        ];
    }
}
