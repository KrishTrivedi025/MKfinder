-- Simple MKfinder Database Schema
-- Create this database in phpMyAdmin

CREATE DATABASE IF NOT EXISTS mkfinder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mkfinder;

-- Species table
CREATE TABLE IF NOT EXISTS species (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    scientific_name VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO species (name, scientific_name, description) VALUES 
('American Robin', 'Turdus migratorius', 'A migratory songbird with distinctive reddish-orange breast.'),
('Blue Jay', 'Cyanocitta cristata', 'An intelligent songbird with bright blue coloration and prominent crest.'),
('Northern Cardinal', 'Cardinalis cardinalis', 'A vibrant red songbird with distinctive crest and orange bill.')
ON DUPLICATE KEY UPDATE name=name;