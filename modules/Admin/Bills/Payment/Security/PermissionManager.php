<?php
declare(strict_types=1);
namespace App\Modules\Admin\Bills\Payment\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'list' => ['superuser', 'manager', 'staff'],
            'index' => ['superuser', 'manager', 'staff'],
            'showCreateForm' => ['superuser', 'manager', 'staff'],
            'create' => ['superuser', 'manager', 'staff'],
            'generateBillItemsAjax' => ['superuser', 'manager', 'staff'],
            'getDailyAttendanceAjax' => ['superuser', 'manager', 'staff'],
            'updateStatus' => ['superuser', 'manager', 'staff'],
            'edit' => ['superuser', 'manager'],
            'delete' => ['superuser'],
            'print' => ['superuser', 'manager', 'staff'],
        ];
    }
}
