<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\Security;

class PermissionManager
{
    public static function getPermissions(): array
    {
        $superuser_only = ['superuser'];
        $manager_plus = ['superuser', 'manager'];
        $user_plus = ['superuser', 'manager', 'user'];
        $read_only = ['superuser', 'admin', 'manager', 'user'];

        return [
            // --- Universal Actions ---
            'list' => $read_only,
            'status' => $read_only,
            'showCreateForm' => $user_plus,
            'showEditForm' => $user_plus,
            'create' => $user_plus,
            'createLogs' => $user_plus,
            'validateStagedLog' => $user_plus,
            'createBulkReceipt' => $user_plus,
            'update' => $manager_plus,
            'delete' => $superuser_only,
            'editByDate' => $manager_plus,
            'viewDetails' => $read_only,
            'jobCards' => $read_only,

            // --- Audit Actions ---
            'auditLogs' => $manager_plus,
            'applyAuditFixes' => $manager_plus,
            'showAuditScreen' => $manager_plus,
            'calculateBackfill' => $manager_plus,

            // --- AJAX/Data Actions ---
            'getFormData' => $read_only,
            'getPreviousReading' => $read_only,
            'getChartData' => $read_only,
            'getMachineLogData' => $read_only,
            'getMachineAuditData' => $read_only,
            'getOfficeMachines' => $read_only,
            'getMonthwiseMachineStats' => $read_only,
            'getSpareParts' => $read_only,
            'getMachineDetails' => $read_only,
            'addCustomServiceType' => $user_plus,
        ];
    }
}
