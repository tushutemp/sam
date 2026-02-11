-- Create database
CREATE DATABASE IF NOT EXISTS sarvatantra_db;
USE sarvatantra_db;

-- Create members table
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profession VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_join_date (join_date)
);

-- Create opinions table (category field removed)
CREATE TABLE IF NOT EXISTS opinions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    opinion TEXT NOT NULL,
    language VARCHAR(10) DEFAULT 'hi',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed') DEFAULT 'pending',
    INDEX idx_email (email),
    INDEX idx_submission_date (submission_date)
);

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator', 'viewer') DEFAULT 'admin',
    created_by VARCHAR(50),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Insert default admin user (username: admin, password: admin)
-- Password is hashed using password_hash('admin', PASSWORD_DEFAULT)
INSERT INTO admin_users (username, email, full_name, password, role, created_by, status) 
VALUES ('admin', 'admin@sarvatantra.com', 'Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'system', 'active')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Insert sample members data
INSERT INTO members (name, email, phone, gender, profession) VALUES
('John Doe', 'john@example.com', '+1234567890', 'male', 'Engineer'),
('Jane Smith', 'jane@example.com', '+0987654321', 'female', 'Doctor'),
('Raj Kumar', 'raj@example.com', '+911234567890', 'male', 'Teacher'),
('Priya Sharma', 'priya@example.com', '+919876543210', 'female', 'Lawyer'),
('Mike Johnson', 'mike@example.com', '+441234567890', 'male', 'Business'),
('Sarah Williams', 'sarah@example.com', '+441234567891', 'female', 'Artist'),
('Amit Patel', 'amit@example.com', '+911112223333', 'male', 'Developer'),
('Sneha Singh', 'sneha@example.com', '+919998887777', 'female', 'Designer');

-- Insert sample opinions data (category field removed from INSERT statement)
INSERT INTO opinions (name, email, phone, opinion, language) VALUES
('John Doe', 'john@example.com', '+1234567890', 'This is excellent content. Very informative!', 'en'),
('Jane Smith', 'jane@example.com', '+0987654321', 'The design is clean and modern. Great work!', 'en'),
('Raj Kumar', 'raj@example.com', '+911234567890', 'Please add more language options.', 'hi'),
('Priya Sharma', 'priya@example.com', '+919876543210', 'The interface is user-friendly and intuitive.', 'hi'),
('Mike Johnson', 'mike@example.com', '+441234567890', 'The content needs more examples and case studies.', 'en'),
('Sarah Williams', 'sarah@example.com', '+441234567891', 'Color scheme could be more vibrant.', 'en');