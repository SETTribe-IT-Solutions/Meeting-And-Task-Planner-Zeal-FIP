<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('Database connection not available.');
}

$query = 'SELECT id, name, email, password, role FROM users LIMIT 5';
$result = mysqli_query($conn, $query);

echo "<h2>Database Users</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password (stored)</th><th>Role</th></tr>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($user = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td><code>" . htmlspecialchars($user['password']) . "</code></td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No users found</td></tr>";
}

echo "</table>";

echo "<p><strong>To test login:</strong></p>";
echo "<ul>";
echo "<li>Use the email from the table above</li>";
echo "<li>If password starts with '$2', it's bcrypt-hashed. Use plain password that was used when account was created.</li>";
echo "<li>If it's plain text, use it directly</li>";
echo "</ul>";
?>
