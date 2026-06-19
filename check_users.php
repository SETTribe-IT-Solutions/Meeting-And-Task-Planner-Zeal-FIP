<?php
// check_users.php
require_once 'config/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, email, password, role FROM users");
    $users = $stmt->fetchAll();

    echo "<h3>Registered Users in Database:</h3>";
    if (empty($users)) {
        echo "<p style='color:red;'>No users found in the 'users' table!</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email/Username Column</th>
                    <th>Password Column Value</th>
                    <th>Role</th>
                </tr>";
        foreach ($users as $user) {
            echo "<tr>
                    <td>{$user['id']}</td>
                    <td>{$user['name']}</td>
                    <td style='background:#e0f2fe;'><b>{$user['email']}</b></td>
                    <td style='background:#fef08a;'><code>{$user['password']}</code></td>
                    <td>{$user['role']}</td>
                  </tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>