<?php
session_start();

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "sarvatantra_db";
    public $conn;

    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Check if tables exist, create them if not
function initializeDatabase($conn) {
    // Create members table
    $sql = "CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL,
        join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        profession VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active'
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating members table: " . $conn->error);
    }
    
    // Create opinions table
    $sql = "CREATE TABLE IF NOT EXISTS opinions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        category VARCHAR(50) NOT NULL,
        opinion TEXT NOT NULL,
        language VARCHAR(10) DEFAULT 'hi',
        submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'reviewed') DEFAULT 'pending'
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating opinions table: " . $conn->error);
    }
    
    // Create admin_users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        full_name VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'moderator', 'viewer') DEFAULT 'admin',
        created_by VARCHAR(50),
        created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'inactive') DEFAULT 'active'
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating admin_users table: " . $conn->error);
    }
    
    // Create default admin user if not exists
    $checkAdmin = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
    $row = $checkAdmin->fetch_assoc();
    
    if ($row['count'] == 0) {
        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
        $sql = "INSERT INTO admin_users (username, email, full_name, password, role, created_by, status) 
                VALUES ('admin', 'admin@sarvatantra.com', 'Administrator', '$hashedPassword', 'super_admin', 'system', 'active')";
        
        if (!$conn->query($sql)) {
            die("Error creating default admin: " . $conn->error);
        }
    }
}

// Initialize database on first run
initializeDatabase($conn);
?>