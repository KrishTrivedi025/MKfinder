-- MKfinder Database Setup for MySQL/XAMPP phpMyAdmin
-- Complete MySQL database structure for bird species identification system
-- Compatible with phpMyAdmin and XAMPP

-- Create database
CREATE DATABASE IF NOT EXISTS mkfinder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mkfinder;

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS identifications;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS species;
DROP TABLE IF EXISTS users;

-- Create users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (email),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user sessions table for authentication
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    user_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create species table
CREATE TABLE species (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    scientific_name VARCHAR(255) DEFAULT NULL,
    description TEXT,
    characteristics JSON,
    habitat TEXT,
    diet TEXT,
    behavior TEXT,
    conservation_status VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_species_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create uploads table
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    upload_id VARCHAR(255) DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'uploaded',
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_upload_id (upload_id),
    INDEX idx_uploads_time (upload_time DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create identifications table
CREATE TABLE identifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identification_id VARCHAR(255) UNIQUE,
    upload_id VARCHAR(255) DEFAULT NULL,
    filename VARCHAR(255) DEFAULT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    species_name VARCHAR(255) DEFAULT NULL,
    confidence DECIMAL(5,2) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    processing_time_ms INT DEFAULT NULL,
    identification_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifications_species (species_name),
    INDEX idx_identifications_time (identification_time DESC),
    INDEX idx_upload_id (upload_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial species data
INSERT INTO species (name, scientific_name, description, characteristics, habitat, diet, behavior, conservation_status) VALUES 
(
    'American Robin', 
    'Turdus migratorius', 
    'The American Robin is a migratory songbird of the true thrush genus and Turdidae, the wider thrush family. It is named after the European robin because of its reddish-orange breast, though the two species are not closely related.',
    '["Red-orange breast and belly", "Dark gray to black head and back", "White markings around the eyes", "Yellow beak with dark tip", "White undertail coverts", "Length: 8-11 inches", "Wingspan: 12-16 inches"]',
    'Woodlands, parks, gardens, and lawns across North America',
    'Insects, earthworms, fruits, and berries',
    'Known for pulling earthworms from lawns, territorial during breeding season, forms flocks in winter',
    'Least Concern'
),
(
    'Blue Jay', 
    'Cyanocitta cristata', 
    'The Blue Jay is a passerine bird in the family Corvidae, native to eastern North America. It is a highly intelligent and social bird known for its distinctive blue coloration and complex vocalizations.',
    '["Bright blue upper parts with white underparts", "Black necklace markings across throat", "Prominent blue crest that can be raised or lowered", "White and black barred wings and tail", "Black bill and legs", "Length: 11-12 inches", "Wingspan: 13-17 inches"]',
    'Deciduous and mixed forests, parks, and residential areas with large trees',
    'Nuts, seeds, insects, eggs, and small animals',
    'Highly social, forms complex family groups, known for mobbing predators, excellent mimics',
    'Least Concern'
),
(
    'Northern Cardinal', 
    'Cardinalis cardinalis', 
    'The Northern Cardinal is a bird in the genus Cardinalis. It is also known colloquially as the redbird, common cardinal, red cardinal, or just cardinal. Males are vibrant red while females are a warm brown with red accents.',
    '["Males: Brilliant red all over with black mask", "Females: Warm brown with red tinges on wings, tail, and crest", "Thick, orange-red, cone-shaped beak", "Prominent red crest", "Black face mask around beak and eyes (males)", "Length: 8.5-9 inches", "Wingspan: 9.8-12.2 inches"]',
    'Woodlands, gardens, shrublands, and wetlands with dense cover',
    'Seeds, grains, fruits, and insects',
    'Non-migratory, territorial, males sing to defend territory, females also sing',
    'Least Concern'
);

-- Create a test user (email: test@mkfinder.com, password: test123)
-- Password is hashed using PHP password_hash() - you should change this after first login
INSERT INTO users (user_id, email, phone_number, password_hash, is_active) VALUES 
('user_test_12345678', 'test@mkfinder.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Display success message
SELECT 'Database created successfully!' AS Status;
SELECT 'You can now use the following test account:' AS Info;
SELECT 'Email: test@mkfinder.com' AS Email, 'Password: test123' AS Password;
