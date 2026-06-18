<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

echo "<h2>Database Diagnostics</h2>";

// Check if users table exists
$query = "SHOW TABLES LIKE 'users'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<p style='color: red;'><strong>ERROR: users table does not exist</strong></p>";
} else {
    echo "<p style='color: green;'><strong>✓ users table exists</strong></p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $query = "DESCRIBE users";
    $result = mysqli_query($conn, $query);
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show all users
    echo "<h3>All Users in Database:</h3>";
    $query = "SELECT * FROM users";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        echo "<p style='color: orange;'><strong>⚠ No users in database</strong></p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        $fields = [];
        $firstRow = true;
        while ($row = mysqli_fetch_assoc($result)) {
            if ($firstRow) {
                echo "<tr>";
                foreach ($row as $field => $value) {
                    echo "<th>" . htmlspecialchars($field) . "</th>";
                }
                echo "</tr>";
                $firstRow = false;
            }
            echo "<tr>";
            foreach ($row as $field => $value) {
                echo "<td>";
                if ($value === NULL) {
                    echo "<span style='color: red;'>NULL</span>";
                } else {
                    echo htmlspecialchars($value);
                }
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}

mysqli_close($conn);
?>
