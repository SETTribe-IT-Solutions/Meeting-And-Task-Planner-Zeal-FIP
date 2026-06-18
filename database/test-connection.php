<?php
/**
 * Database Connection Test
 * Run this file to verify database connection
 * URL: http://localhost/Meeting_Task/Meeting-And-Task-Planner-Zeal-FIP/database/test-connection.php
 */

require_once '../config/db.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ <strong>Database connection successful!</strong><br>";
    echo "Database: <strong>meeting_planner</strong><br>";
    
    // Check if user table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "✅ User table exists<br>";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
        $result = $stmt->fetch();
        echo "📊 Total users: <strong>" . $result['count'] . "</strong><br><br>";
        
        // List users
        $stmt = $pdo->query("SELECT id, username, email, role FROM user");
        $users = $stmt->fetchAll();
        
        if ($users) {
            echo "<h3>Registered Users:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ Users table not found<br>";
        echo "Please run <strong><a href='setup.php'>setup.php</a></strong> first<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Database connection failed!</strong><br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "Please run <strong><a href='setup.php'>setup.php</a></strong> to create the database<br>";
}
?>
