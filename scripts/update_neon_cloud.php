<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

$neonUrl = getenv('DATABASE_URL') ?: 'postgresql://neondb_owner:npg_YoD4CLZ2TQpw@ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech/neondb?sslmode=require';
$parsed = parse_url($neonUrl);
$host = $parsed['host'] ?? 'localhost';
$port = $parsed['port'] ?? 5432;
$dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'neondb';
$user = $parsed['user'] ?? 'neondb_owner';
$pass = $parsed['pass'] ?? 'npg_YoD4CLZ2TQpw';

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
