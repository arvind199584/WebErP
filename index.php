<?php
// index.php

$sessionPath = __DIR__ . '/sessions_new';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionPath);
}

require_once __DIR__ . '/modules/Superadmin/Users/Services/UserService.php';

// 1. Check if user is logged in
$user = \App\Modules\Superadmin\Users\Services\UserService::getCurrentUser();

if (!$user) {
    header('Location: /modules/Superadmin/Users/Controller/UserController.php?action=login');
    exit;
}

$currentUser = $user;

// 2. Set the view to the dynamic dashboard
$viewPath = __DIR__ . '/modules/AI_and_Tools/Utility/Views/dashboard.php';

// 3. Use the central layout to render everything
require_once __DIR__ . '/core/Layout.php';
