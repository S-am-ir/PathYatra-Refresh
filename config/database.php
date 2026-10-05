<?php
/**
 * YatraPath - Database Configuration & PDO Singleton
 */
declare(strict_types=1);

class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset;

    private function __construct() {
        // Default XAMPP settings with environment variable fallbacks
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->dbname = getenv('DB_NAME') ?: 'yatra_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $this->charset = 'utf8mb4';

        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Note: In development we can provide context; in production logs are preferred.
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
