<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

echo "<h2>Setting Up Test Users</h2>";

// Check existing users
$query = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$userCount = $row['count'];

echo "<p>Current users in database: <strong>$userCount</strong></p>";

// Create test users
$testUsers = [
    ['name' => 'Organizer User', 'email' => 'organizer@test.com', 'password' => 'password123', 'role' => 'organizer'],
    ['name' => 'Collector User', 'email' => 'collector@test.com', 'password' => 'password123', 'role' => 'collector'],
    ['name' => 'Employee User', 'email' => 'employee@test.com', 'password' => 'password123', 'role' => 'employee'],
];

echo "<h3>Inserting Test Users:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Email</th><th>Password</th><th>Role</th><th>Status</th></tr>";

foreach ($testUsers as $user) {
    $insertQuery = 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)';
    $stmt = mysqli_prepare($conn, $insertQuery);
    
    if (!$stmt) {
        echo "<tr><td>" . htmlspecialchars($user['email']) . "</td><td>" . htmlspecialchars($user['password']) . "</td><td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td style='color: red;'>Prepare failed: " . mysqli_error($conn) . "</td></tr>";
        continue;
    }
    
    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, 'ssss', $user['name'], $user['email'], $hashedPassword, $user['role']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<tr><td>" . htmlspecialchars($user['email']) . "</td><td>" . htmlspecialchars($user['password']) . "</td><td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td style='color: green;'>✓ Created</td></tr>";
    } else {
        echo "<tr><td>" . htmlspecialchars($user['email']) . "</td><td>" . htmlspecialchars($user['password']) . "</td><td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td style='color: orange;'>" . mysqli_error($conn) . "</td></tr>";
    }
    mysqli_stmt_close($stmt);
}
echo "</table>";

echo "<h3>Use These Credentials to Login:</h3>";
echo "<ul style='font-size: 16px;'>";
foreach ($testUsers as $user) {
    echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . " | <strong>Password:</strong> " . htmlspecialchars($user['password']) . " | <strong>Role:</strong> " . htmlspecialchars($user['role']) . "</li>";
}
echo "</ul>";

echo "<p><a href='../auth/login.php' style='font-size: 16px; padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Login</a></p>";

mysqli_close($conn);
?>
