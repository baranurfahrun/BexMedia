-- Create Database
CREATE DATABASE IF NOT EXISTS bexmedia;
USE bexmedia;

-- Create Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin User
-- Password: admin123 (Sudah di-hash menggunakan PHP password_hash)
INSERT INTO users (username, password, full_name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator BexMedia')
ON DUPLICATE KEY UPDATE username=username;
