<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\WagesRates\Security;

/**
 * Defines permissions for the WagesRates module.
 */
class PermissionManager
{
    /**
     * @return array<string, array<string>>
     */
    public static function getPermissions(): array
    {
        return [
            // READ: Accessible to all authenticated roles
            'list' => ['superuser', 'manager', 'staff'],
            'view' => ['superuser', 'manager', 'staff'],

            // CREATE: Superuser only
            'showCreateForm' => ['superuser'],
            'create' => ['superuser'],

            // UPDATE: Superuser only
            'showEditForm' => ['superuser'],
            'update' => ['superuser'],

            // DELETE: Superuser only
            'delete' => ['superuser'],
        ];
    }
}
