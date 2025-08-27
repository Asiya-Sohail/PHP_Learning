<?php
// config/database.php - Database Configuration and Connection

class Database {
    private $host = 'localhost:3307';
    private $dbname = 'blog_website';
    private $username = 'root';
    private $password = ''; // Change this to your MySQL password
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // Helper method for preparing statements
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    // Helper method for executing queries
    public function query($sql) {
        return $this->pdo->query($sql);
    }
    
    // Helper method for getting last insert ID
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    // Helper method for transactions
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
}

// Global database instance
$database = new Database();
$pdo = $database->getConnection();

// Security and Configuration Constants
define('SITE_NAME', 'My Blog');
define('SITE_URL', 'http://localhost/blog_site');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');