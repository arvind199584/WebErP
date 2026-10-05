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

        $host     = getenv('DB_HOST')    ?: 'localhost';
        $port     = getenv('DB_PORT')    ?: '5432';
        $db_name  = getenv('DB_NAME')    ?: 'modpyphp';
        $username = getenv('DB_USER')    ?: 'postgres';
        $password = getenv('DB_PASS')    ?: 'daredevil';
        $sslmode  = getenv('DB_SSLMODE') ?: 'disable';

        $dsn = "pgsql:host={$host};port={$port};dbname={$db_name};sslmode={$sslmode}";

        try {
            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
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
