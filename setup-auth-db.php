<?php
/**
 * Database setup for authentication system
 * Creates users table and demo admin account
 */

require_once 'config.php';
require_once 'database.php';

try {
    $db = getDatabase();
    $connection = $db->getConnection();
    
    echo "Setting up authentication database...\n";
    
    // Create users table
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            user_id VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone_number VARCHAR(20),
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP
        )
    ";
    
    $connection->exec($createUsersTable);
    echo "✓ Users table created successfully\n";
    
    // Create demo admin account
    $adminUserId = 'admin_' . uniqid();
    $adminEmail = 'admin@gmail.com';
    $adminPassword = 'admin';
    $adminPasswordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $checkAdmin = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $stmt = $connection->prepare($checkAdmin);
    $stmt->execute([$adminEmail]);
    $adminExists = $stmt->fetch()['count'] > 0;
    
    if (!$adminExists) {
        $insertAdmin = "
            INSERT INTO users (user_id, email, password_hash, phone_number, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $connection->prepare($insertAdmin);
        $stmt->execute([$adminUserId, $adminEmail, $adminPasswordHash, '+1234567890', true]);
        echo "✓ Demo admin account created (admin@gmail.com / admin)\n";
    } else {
        echo "✓ Demo admin account already exists\n";
    }
    
    // Create sessions table for session management
    $createSessionsTable = "
        CREATE TABLE IF NOT EXISTS user_sessions (
            id SERIAL PRIMARY KEY,
            session_id VARCHAR(128) UNIQUE NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ";
    
    $connection->exec($createSessionsTable);
    echo "✓ User sessions table created successfully\n";
    
    echo "✓ Authentication database setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up authentication database: " . $e->getMessage() . "\n";
    exit(1);
}
?>