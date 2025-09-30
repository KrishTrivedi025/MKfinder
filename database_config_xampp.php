<?php
/**
 * Database Configuration for XAMPP
 * 
 * INSTRUCTIONS:
 * 1. Start XAMPP and make sure Apache and MySQL are running
 * 2. Open phpMyAdmin (http://localhost/phpmyadmin)
 * 3. Import the mkfinder_database.sql file
 * 4. Update the settings below if needed (default XAMPP settings should work)
 * 5. Rename this file to: database_config.php (remove _xampp)
 */

// XAMPP MySQL Database Configuration
define('DB_HOST', 'localhost');      // Usually 'localhost' for XAMPP
define('DB_PORT', '3306');           // Default MySQL port
define('DB_NAME', 'mkfinder');       // Database name from SQL file
define('DB_USER', 'root');           // Default XAMPP MySQL username
define('DB_PASS', '');               // Default XAMPP MySQL password (empty)

// Set environment variables for the application
putenv("DB_HOST=" . DB_HOST);
putenv("DB_PORT=" . DB_PORT);
putenv("DB_NAME=" . DB_NAME);
putenv("DB_USER=" . DB_USER);
putenv("DB_PASS=" . DB_PASS);

echo "Database configuration loaded successfully!<br>";
echo "Host: " . DB_HOST . "<br>";
echo "Database: " . DB_NAME . "<br>";
echo "User: " . DB_USER . "<br>";
?>
