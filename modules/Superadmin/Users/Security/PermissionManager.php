<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Users\Security;

/**
 * Defines permissions for the Users module.
 */
class PermissionManager
{
    /**
     * @return array<string, array<string>>
     */
    public static function getPermissions(): array
    {
        return [
            // Public actions accessible to anyone (even logged-out users)
            'login' => ['guest', 'superuser', 'manager', 'staff'],
            'logout' => ['superuser', 'manager', 'staff'],

            // Actions restricted to superuser
            'list' => ['superuser'],
            'showCreateForm' => ['superuser'],
            'create' => ['superuser'],
            'showEditForm' => ['superuser'],
            'update' => ['superuser'],
            'delete' => ['superuser'],
        ];
    }
}
