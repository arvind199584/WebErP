<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\ExtraItem\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'showCreateForm' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
        ];
    }
}
