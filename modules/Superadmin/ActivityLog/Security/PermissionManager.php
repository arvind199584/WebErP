<?php
declare(strict_types=1);
namespace App\Modules\Superadmin\ActivityLog\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser'],
            'cloudDiff' => ['superuser'],
            'commitCloudChange' => ['superuser'],
            'cancelCloudChange' => ['superuser'],
            'backupManagement' => ['superuser'],
            'backupLocalDb' => ['superuser'],
            'restoreLocalDb' => ['superuser'],
            'refreshNeonDb' => ['superuser'],
        ];
    }
}
