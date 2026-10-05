<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\Utility\Security;

class PermissionManager {
    public static function getPermissions(): array {
        return [
            'index' => ['superuser', 'manager', 'staff'],
            'pendency' => ['superuser', 'manager', 'staff'],
            'budget_tracking' => ['superuser', 'manager', 'staff'], // ADDED
            'nlp' => ['superuser'],
            'train_nlp' => ['superuser'],
            'normalize_sql_ajax' => ['superuser'],
            'merge' => ['superuser', 'manager', 'staff'],
            'compress' => ['superuser', 'manager', 'staff'],
            'convert_word' => ['superuser', 'manager', 'staff'],
            'convert_excel' => ['superuser', 'manager', 'staff'],
            'compress_jpeg' => ['superuser', 'manager', 'staff'],
            'pdf_to_jpeg' => ['superuser', 'manager', 'staff'],
            'jpeg_to_pdf' => ['superuser', 'manager', 'staff'],
        ];
    }
}
