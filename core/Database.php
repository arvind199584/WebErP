<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Manages the database connection using the Singleton pattern.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
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

        // Support standard cloud DATABASE_URL (Render, Neon, Supabase, Heroku)
        $databaseUrl = $getEnv('DATABASE_URL');
        if (!empty($databaseUrl)) {
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

        $dsn = "pgsql:host={$host};port={$port};dbname={$db_name};sslmode={$sslmode}";

        try {
            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $isDebug = ($getEnv('APP_DEBUG') === 'true');
            if ($isDebug) {
                die("Database connection failed (Host: {$host}, Port: {$port}, DB: {$db_name}): " . $e->getMessage());
            } else {
                http_response_code(500);
                die("Database connection error. Please check server logs or verify your DATABASE_URL environment variable.");
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
     * Gets the single instance of the Database class.
     *
     * @return Database
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        } else {
            if (self::$instance->connection->inTransaction()) {
                try {
                    self::$instance->connection->rollBack();
                } catch (\Throwable $e) {
                    // Ignore errors if transaction is already terminated
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
