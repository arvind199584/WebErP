<?php
/**
 * Built-in PHP Web Server Security Router
 * Blocks direct access to sensitive configuration files, environment variables,
 * private certificates, databases dumps, and hidden files.
 */

declare(strict_types=1);

$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = urldecode($rawPath);

// 1. Immediately block access to dotfiles (.env, .git, etc.)
if (preg_match('/(?:\/|^)\.[a-zA-Z0-9_\-]+/i', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "403 Forbidden: Access denied.";
    exit;
}

// 2. Block access to sensitive extensions
if (preg_match('/\.(?:env|pem|key|crt|sql|dump|bak|log|ini|sh|bat|yml|yaml|md|json|lock|dist)$/i', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "403 Forbidden: Access denied.";
    exit;
}

// 3. Block access to sensitive project directories
if (preg_match('/^\/(?:certificates|database|docker|schema|scripts|vendor|Setup|storage|AI|services)\b/i', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "403 Forbidden: Access denied.";
    exit;
}

// 4. Resolve file path in document root
$filePath = realpath(__DIR__ . '/..' . $path);
$docRoot  = realpath(__DIR__ . '/..');

// Prevent directory traversal
if ($filePath !== false && strpos($filePath, $docRoot) === 0 && is_file($filePath)) {
    // If it's a PHP file, execute it
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        require $filePath;
        exit;
    }
    // Return false to let the built-in server serve safe static files (CSS, JS, images)
    return false;
}

// 5. Default route to index.php
require __DIR__ . '/../index.php';
