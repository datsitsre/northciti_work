<?php
// admin/config/database.php - Updated Database Configuration

// Prevent direct access
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted');
}

class Database 
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct() 
    {
        $this->config = [
            'host' => $_ENV['DB_HOST'] ?? '10.30.252.49',
            'dbname' => $_ENV['DB_NAME'] ?? 'northcity_db_2025',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];

        $this->connect();
    }

    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() 
    {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            // Test the connection
            $this->pdo->query('SELECT 1');
            
        } catch (PDOException $e) {
            // Log the error
            error_log("Database connection failed: " . $e->getMessage());
            
            // Show user-friendly error in debug mode
            if (APP_DEBUG) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please check your configuration.");
            }
        }
    }

    public function getPdo() 
    {
        return $this->pdo;
    }

    public function query($sql, $params = []) 
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = []) 
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) 
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function execute($sql, $params = []) 
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function lastInsertId() 
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() 
    {
        return $this->pdo->beginTransaction();
    }

    public function commit() 
    {
        return $this->pdo->commit();
    }

    public function rollback() 
    {
        return $this->pdo->rollback();
    }

    public function inTransaction() 
    {
        return $this->pdo->inTransaction();
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() 
    {
        throw new Exception("Cannot unserialize singleton");
    }
}