<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

function loadEnvFile(string $filepath): void {
    if (file_exists($filepath)) {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }
}

loadEnvFile(__DIR__ . '/../.env');
loadEnvFile(__DIR__ . '/../.env.neon');

$neonUrl = getenv('NEON_DATABASE_URL') ?: getenv('DATABASE_URL');
if (!$neonUrl) {
    echo "[-] ERROR: NEON_DATABASE_URL or DATABASE_URL is not set.\n";
    echo "[-] Please configure it in your .env or .env.neon file.\n";
    exit(1);
}

$parsed = parse_url($neonUrl);
$host = $parsed['host'] ?? 'localhost';
$port = $parsed['port'] ?? 5432;
$dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'neondb';
$user = isset($parsed['user']) ? urldecode($parsed['user']) : 'neondb_owner';
$pass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

try {
    echo "Connecting to Neon Cloud DB ($host / $dbname)...\n";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Ensuring 'noticeboard' table exists on Neon Cloud...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS public.noticeboard (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            badge_type VARCHAR(50) DEFAULT 'Announcement',
            priority INT DEFAULT 1,
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $count = $pdo->query("SELECT count(*) FROM public.noticeboard")->fetchColumn();
    if ((int)$count === 0) {
        echo "Seeding default notices on Neon Cloud...\n";
        $pdo->exec("
            INSERT INTO public.noticeboard (title, content, badge_type, priority, is_active) VALUES
            ('Welcome to Enterprise ERP Portal', 'Integrated portal for Works, HR, Finance, Store and Machinery Fleet operations.', 'Announcement', 10, true),
            ('Official Android Mobile App Ready', 'Download our official native Jetpack Compose APK directly from the download section below.', 'Circular', 8, true),
            ('Interactive Guest Demo Sandbox', 'Explore all features freely with full interactive access using the Launch Guest Demo button.', 'Maintenance', 5, true);
        ");
    }

    echo "Neon Cloud DB updated successfully! Notices count: " . $pdo->query("SELECT count(*) FROM public.noticeboard")->fetchColumn() . "\n";
} catch (\Throwable $e) {
    echo "Neon DB Error: " . $e->getMessage() . "\n";
    exit(1);
}
