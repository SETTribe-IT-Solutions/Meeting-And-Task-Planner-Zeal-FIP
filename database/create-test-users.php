<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('Database connection not available.');
}

// Check if any users exist
$query = 'SELECT COUNT(*) as count FROM users';
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    // No users exist, create test users
    $testUsers = [
        ['name' => 'John Organizer', 'email' => 'organizer@test.com', 'password' => 'password123', 'role' => 'organizer'],
        ['name' => 'Jane Collector', 'email' => 'collector@test.com', 'password' => 'password123', 'role' => 'collector'],
        ['name' => 'Bob Employee', 'email' => 'employee@test.com', 'password' => 'password123', 'role' => 'employee'],
    ];
    
    echo "<h2>Creating Test Users...</h2>";
    
    foreach ($testUsers as $user) {
        $insertQuery = 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)';
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, 'ssss', $user['name'], $user['email'], $user['password'], $user['role']);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>✓ Created: " . htmlspecialchars($user['email']) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed: " . htmlspecialchars($user['email']) . " - " . mysqli_error($conn) . "</p>";
        }
    }
    
    echo "<h3>Login Credentials:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Email</th><th>Password</th><th>Role</th></tr>";
    foreach ($testUsers as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['password']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h2>Users Already Exist</h2>";
    echo "<p>Visit: database/show-credentials.php to see existing users</p>";
}

mysqli_close($conn);
?>
