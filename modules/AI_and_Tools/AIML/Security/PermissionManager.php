<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIML\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'ask' => ['superuser', 'manager', 'staff'],
            'training_hub' => ['superuser'],
            'resolve_training' => ['superuser'],
            'normalize_sql_ajax' => ['superuser'],
            'get_tables_ajax' => ['superuser'], // ADDED
            'get_columns_ajax' => ['superuser'], // ADDED
            'report_wrong_ajax' => ['superuser', 'manager', 'staff'],
        ];
    }
}
