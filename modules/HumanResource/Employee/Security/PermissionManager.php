<?php
declare(strict_types=1);
namespace App\Modules\HumanResource\Employee\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'index' => ['superuser', 'manager', 'staff'],
            'showCreateForm' => ['superuser', 'manager'],
            'create' => ['superuser', 'manager'],
            'process_batch' => ['superuser', 'manager'],
            'showEditForm' => ['superuser', 'manager'],
            'update' => ['superuser', 'manager'],
            'delete' => ['superuser', 'manager'],
            'deleteAll' => ['superuser', 'manager'],
            'downloadTemplate' => ['superuser', 'manager'],
            'import' => ['superuser', 'manager'],
        ];
    }
}
