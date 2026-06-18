<?php
/**
 * Database Setup Script
 * Run this file in browser to create the database and tables
 * URL: http://localhost/Meeting_Task/Meeting-And-Task-Planner-Zeal-FIP/database/setup.php
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password

try {
    // Connect without database to create it
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `meeting_task_database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'meeting_task_database' created/verified<br>";
    
    // Select database
    $pdo->exec("USE `meeting_task_database`");
    
    // Create user table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) UNIQUE NOT NULL,
            `email` VARCHAR(100) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(20) DEFAULT 'user',
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table 'user' created/verified<br>";
    
    // Insert test users (password: password123)
    $testPassword = password_hash('password123', PASSWORD_BCRYPT);
    
    $pdo->prepare("
        INSERT IGNORE INTO `user` (username, email, password, role, is_active) 
        VALUES (?, ?, ?, ?, ?)
    ")->execute(['admin', 'admin@example.com', $testPassword, 'admin', 1]);
    
    $pdo->prepare("
        INSERT IGNORE INTO `user` (username, email, password, role, is_active) 
        VALUES (?, ?, ?, ?, ?)
    ")->execute(['user1', 'user1@example.com', $testPassword, 'user', 1]);
    
    echo "✅ Test users created successfully<br><br>";
    
    echo "<h3>Test Credentials:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    echo "<tr><td>admin</td><td>password123</td><td>Admin</td></tr>";
    echo "<tr><td>user1</td><td>password123</td><td>User</td></tr>";
    echo "</table><br>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>You can now <a href='../models/users/login.php'>Login Here</a></p>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    die();
}
?>
