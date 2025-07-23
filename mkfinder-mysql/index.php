<?php
/**
 * MKfinder Index - PHP Version
 * This file redirects to index.html and provides PHP fallback
 */

// Check if the HTML file exists and redirect
if (file_exists('index.html')) {
    // Read and display the HTML content
    readfile('index.html');
    exit;
}

// Fallback if index.html is missing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MKfinder - Bird Species Identification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
        .container { max-width: 600px; margin: 0 auto; }
        .status { padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐦 MKfinder - Bird Identification System</h1>
        
        <div class="status success">
            <h3>✅ PHP Server Working!</h3>
            <p>Your XAMPP PHP installation is working correctly.</p>
        </div>

        <div class="status info">
            <h3>📁 File Check</h3>
            <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
            <p><strong>Files Found:</strong></p>
            <ul style="text-align: left; display: inline-block;">
                <?php
                $files = ['index.html', 'config.php', 'database.php', 'mkfinder.sql'];
                foreach ($files as $file) {
                    $exists = file_exists($file);
                    echo "<li>" . ($exists ? "✅" : "❌") . " $file</li>";
                }
                ?>
            </ul>
        </div>

        <?php if (file_exists('config.php')): ?>
        <div class="status info">
            <h3>🔌 Database Test</h3>
            <p><a href="test-db.php">Click here to test database connection</a></p>
        </div>
        <?php endif; ?>

        <div class="status info">
            <h3>🚀 Next Steps</h3>
            <ol style="text-align: left; display: inline-block;">
                <li>Ensure all files are in <code>C:\xampp\htdocs\mkfinder\</code></li>
                <li>Create database 'mkfinder' in phpMyAdmin</li>
                <li>Import the mkfinder.sql file</li>
                <li>Access the main application</li>
            </ol>
        </div>

        <div class="status">
            <h3>📚 Documentation</h3>
            <p>
                <a href="README.md">README.md</a> | 
                <a href="XAMPP-SETUP.md">XAMPP Setup Guide</a> | 
                <a href="TROUBLESHOOTING.md">Troubleshooting</a>
            </p>
        </div>

        <hr style="margin: 40px 0;">
        <p><small>MKfinder v2.0 - MySQL/XAMPP Edition</small></p>
    </div>
</body>
</html>