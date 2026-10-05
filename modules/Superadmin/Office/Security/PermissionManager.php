<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Office\Security;

/**
 * Defines permissions for the Office module.
 * Maps actions to the roles that are allowed to perform them.
 */
class PermissionManager
{
    /**
     * @return array<string, array<string>>
     */
    public static function getPermissions(): array
    {
        return [
            // Action => [Allowed Roles]
            'list' => ['superuser', 'manager', 'staff'],
            'showEditForm' => ['superuser', 'manager'],
            'update' => ['superuser', 'manager'],
            'showCreateForm' => ['superuser', 'manager'],
            'create' => ['superuser', 'manager'],
            'delete' => ['superuser'], // Only superuser can delete offices
        ];
    }
}
