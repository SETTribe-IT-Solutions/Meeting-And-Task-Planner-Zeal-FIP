<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('Database connection not available.');
}

// Get all users
$query = 'SELECT id, name, email, password, role FROM users';
$result = mysqli_query($conn, $query);

echo "<h2>Database Users - Password Debug</h2>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($user = mysqli_fetch_assoc($result)) {
        $pwd = $user['password'];
        $isBcrypt = (substr($pwd, 0, 4) === '$2y$' || substr($pwd, 0, 4) === '$2a$' || substr($pwd, 0, 4) === '$2b$');
        
        echo "<hr>";
        echo "<strong>User:</strong> " . htmlspecialchars($user['email']) . "<br>";
        echo "<strong>Name:</strong> " . htmlspecialchars($user['name']) . "<br>";
        echo "<strong>Role:</strong> " . htmlspecialchars($user['role']) . "<br>";
        echo "<strong>Password (stored):</strong> <code>" . htmlspecialchars($pwd) . "</code><br>";
        echo "<strong>Password Type:</strong> " . ($isBcrypt ? "Bcrypt Hash" : "Plain Text or Other") . "<br>";
        echo "<strong>Password Length:</strong> " . strlen($pwd) . " chars<br>";
        
        // Test with some common test passwords
        echo "<strong>Test Verification:</strong><br>";
        $testPasswords = ['password', '123456', 'test', 'admin', '12345', 'password123'];
        foreach ($testPasswords as $testPwd) {
            $match = password_verify($testPwd, $pwd) || ($testPwd === $pwd);
            echo "  - '$testPwd': " . ($match ? "<span style='color:green;'>✓ MATCH</span>" : "<span style='color:red;'>✗ No match</span>") . "<br>";
        }
    }
} else {
    echo "<p>No users found in database</p>";
}

mysqli_close($conn);
?>
