<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIDrafter\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'generate' => ['superuser', 'manager', 'staff'],
        ];
    }
}
