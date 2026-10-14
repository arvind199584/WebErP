<?php

declare(strict_types=1);

namespace App\Modules\Workshop\Machine\TurfManagement\Security;

class PermissionManager
{
    public static function getPermissions(): array
    {
        $superuser_only = ['superuser'];
        $manager_plus   = ['superuser', 'manager'];
        $staff_plus     = ['superuser', 'manager', 'staff'];
        $read_only      = ['superuser', 'admin', 'manager', 'staff', 'supervisor'];

        return [
            // --- Universal Actions ---
            'list' => $read_only,
            'index' => $read_only,
            'status' => $read_only,
            'showCreateForm' => $staff_plus,
            'showEditForm' => $staff_plus,
            'edit' => $staff_plus,
            'create' => $staff_plus,
            'createLogs' => $staff_plus,
            'validateStagedLog' => $staff_plus,
            'createBulkReceipt' => $staff_plus,
            'createBulkIndent' => $staff_plus,
            'import' => $staff_plus,
            'exportTemplate' => $read_only,
            'update' => $staff_plus,
            'delete' => $staff_plus,
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
            'addCustomServiceType' => $staff_plus,
            'addCustomConsumableItem' => $staff_plus,
            'fetchSourceOptions' => $read_only,
            'fetchSourceDetails' => $read_only,
            'getVoucherDetails' => $read_only,
            'printVoucher' => $read_only,
        ];
    }
}
