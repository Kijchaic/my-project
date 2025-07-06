<?php
/**
 * Secure Database Configuration
 * This file handles database connections with proper security measures
 */

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    private static $instance = null;

    private function __construct() {
        // Load configuration from environment variables or config file
        $this->loadConfig();
    }

    /**
     * Singleton pattern to ensure only one database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load database configuration securely
     */
    private function loadConfig() {
        // Priority: Environment variables > Config file > Default values
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'search_products';
        $this->username = getenv('DB_USERNAME') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->charset = 'utf8mb4';
    }

    /**
     * Establish secure database connection
     */
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            return $this->pdo;

        } catch (PDOException $e) {
            // Log error securely (don't expose sensitive information)
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Execute a prepared statement securely
     */
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            throw new Exception("Database operation failed. Please try again later.");
        }
    }

    /**
     * Fetch all results from a query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single result from a query
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Insert data and return the last insert ID
     */
    public function insert($sql, $params = []) {
        $this->executeQuery($sql, $params);
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Update data and return affected rows
     */
    public function update($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data and return affected rows
     */
    public function delete($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }

    /**
     * Close database connection
     */
    public function close() {
        $this->pdo = null;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    private function __wakeup() {}
}

// Database schema creation function
function createDatabaseSchema() {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // Create products table
        $sql = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type ENUM('esim', 'hotel', 'flight', 'car') NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            data_gb INT DEFAULT NULL,
            days INT DEFAULT NULL,
            countries JSON DEFAULT NULL,
            description TEXT,
            image_url VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_price (price),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);

        // Create search_logs table for analytics
        $sql = "CREATE TABLE IF NOT EXISTS search_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            search_term VARCHAR(255),
            filters JSON,
            product_type VARCHAR(50),
            results_count INT,
            user_ip VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_type (product_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);

        return true;

    } catch (Exception $e) {
        error_log("Schema creation failed: " . $e->getMessage());
        return false;
    }
}

// Sample data insertion function
function insertSampleData() {
    try {
        $db = Database::getInstance();

        // Sample eSIM data
        $esimData = [
            ['Global Connect', 'esim', 25.00, 5, 7, json_encode(['USA', 'Canada', 'UK', 'France', 'Germany']), 'Global connectivity for major countries'],
            ['Euro Roam', 'esim', 18.00, 3, 10, json_encode(['UK', 'France', 'Germany', 'Italy', 'Spain']), 'European travel made easy'],
            ['Asia Explorer', 'esim', 30.00, 8, 14, json_encode(['Japan', 'Thailand', 'Singapore', 'South Korea']), 'Explore Asia with reliable connectivity'],
            ['Americas Plus', 'esim', 22.00, 4, 7, json_encode(['USA', 'Canada', 'Mexico', 'Brazil']), 'Connect across the Americas'],
            ['UK Premium', 'esim', 15.00, 10, 30, json_encode(['UK']), 'Long-term UK connectivity']
        ];

        foreach ($esimData as $data) {
            $sql = "INSERT INTO products (name, type, price, data_gb, days, countries, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $db->executeQuery($sql, $data);
        }

        return true;

    } catch (Exception $e) {
        error_log("Sample data insertion failed: " . $e->getMessage());
        return false;
    }
}
?> 