<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Manages the database connection using the Singleton pattern.
 * Supports transparent switching to the demo sandbox database (modpyphp_demo)
 * when in demo guest mode.
 */
class Database
{
    private static ?Database $instance = null;
    private static ?Database $demoInstance = null;
    private PDO $connection;
    private string $currentDbName;

    private function __construct(bool $isDemo = false)
    {
        $this->loadEnv();

        $getEnv = function(string $key): ?string {
            $val = getenv($key);
            if ($val !== false && trim((string)$val) !== '') {
                return trim((string)$val);
            }
            if (isset($_ENV[$key]) && trim((string)$_ENV[$key]) !== '') {
                return trim((string)$_ENV[$key]);
            }
            if (isset($_SERVER[$key]) && trim((string)$_SERVER[$key]) !== '') {
                return trim((string)$_SERVER[$key]);
            }
            return null;
        };

        $host     = $getEnv('DB_HOST')    ?: 'localhost';
        $port     = $getEnv('DB_PORT')    ?: '5432';
        $db_name  = $getEnv('DB_NAME')    ?: 'modpyphp';
        $username = $getEnv('DB_USER')    ?: 'postgres';
        $password = $getEnv('DB_PASS')    ?: 'daredevil';
        $sslmode  = $getEnv('DB_SSLMODE') ?: 'disable';

        // Override database if running in demo sandbox mode
        if ($isDemo) {
            $db_name = $getEnv('DB_NAME_DEMO') ?: 'modpyphp_demo';
        }

        // Support standard cloud DATABASE_URL (Render, Neon, Supabase, Heroku)
        $databaseUrl = $getEnv('DATABASE_URL');
        if (!empty($databaseUrl) && !$isDemo) {
            $parsed = parse_url($databaseUrl);
            if ($parsed !== false) {
                $host     = $parsed['host'] ?? $host;
                $port     = isset($parsed['port']) ? (string)$parsed['port'] : $port;
                $db_name  = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $db_name;
                $username = isset($parsed['user']) ? urldecode($parsed['user']) : $username;
                $password = isset($parsed['pass']) ? urldecode($parsed['pass']) : $password;
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $query);
                    if (isset($query['sslmode'])) {
                        $sslmode = $query['sslmode'];
                    }
                }
            }
        }

        $this->currentDbName = $db_name;
        $dsn = "pgsql:host={$host};port={$port};dbname={$db_name};sslmode={$sslmode}";

        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Required for cloud PostgreSQL connection poolers (Neon, Supabase, PgBouncer)
                // in transaction pooling mode to prevent prepared statement DEALLOCATE errors
                PDO::ATTR_EMULATE_PREPARES   => true,
            ];
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed ({$db_name}): " . $e->getMessage());
            $isDebug = ($getEnv('APP_DEBUG') === 'true');
            if ($isDebug) {
                die("Database connection failed (Host: {$host}, Port: {$port}, DB: {$db_name}): " . $e->getMessage());
            } else {
                http_response_code(500);
                die("Database connection error. Please check server logs or verify your database connection settings.");
            }
        }
    }

    private function loadEnv(): void
    {
        $envName = getenv('ENV_FILE') ?: '.env';
        $envFile = __DIR__ . '/../' . $envName;
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../.env';
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    /**
     * Checks if current request is in guest demo mode.
     */
    public static function isDemoMode(): bool
    {
        return (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['is_demo_guest']));
    }

    /**
     * Gets the singleton database instance.
     * Automatically routes to modpyphp_demo if in demo mode or forceDemo is true.
     *
     * @param bool|null $forceDemo Optional override flag
     * @return Database
     */
    public static function getInstance(?bool $forceDemo = null): self
    {
        $useDemo = ($forceDemo !== null) ? $forceDemo : self::isDemoMode();

        if ($useDemo) {
            if (self::$demoInstance === null) {
                self::$demoInstance = new self(true);
            } else {
                if (self::$demoInstance->connection->inTransaction()) {
                    try {
                        self::$demoInstance->connection->rollBack();
                    } catch (\Throwable $e) {
                        // Ignore transaction reset error
                    }
                }
            }
            return self::$demoInstance;
        }

        if (self::$instance === null) {
            self::$instance = new self(false);
        } else {
            if (self::$instance->connection->inTransaction()) {
                try {
                    self::$instance->connection->rollBack();
                } catch (\Throwable $e) {
                    // Ignore transaction reset error
                }
            }
        }
        return self::$instance;
    }

    /**
     * Returns the active PDO connection object.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Returns the currently connected database name.
     */
    public function getCurrentDbName(): string
    {
        return $this->currentDbName;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of the instance.
     */
    public function __wakeup()
    {
    }
}
