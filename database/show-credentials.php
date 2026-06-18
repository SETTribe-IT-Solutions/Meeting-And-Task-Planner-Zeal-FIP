<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('Database connection not available.');
}

$query = 'SELECT id, name, email, password, role FROM users';
$result = mysqli_query($conn, $query);

echo "<h2>All Users in Database</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password</th><th>Role</th></tr>";

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
    echo "</table>";
    echo "<h3>Use these credentials to login:</h3>";
    echo "<p>Email: (from Email column above)<br>Password: (from Password column above)</p>";
} else {
    echo "<tr><td colspan='5'><strong>No users found in database</strong></td></tr>";
    echo "</table>";
    echo "<p style='color: red;'>You need to add a user first. Contact database admin or check the database setup.</p>";
}

mysqli_close($conn);
?>
