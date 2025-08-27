-- Create database
CREATE DATABASE IF NOT EXISTS user_management;
USE user_management;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO users (name, email, phone, address) VALUES
('John Doe', 'john@example.com', '+1234567890', '123 Main St, City, State'),
('Jane Smith', 'jane@example.com', '+1987654321', '456 Oak Ave, City, State'),
('Mike Johnson', 'mike@example.com', '+1122334455', '789 Pine Rd, City, State');