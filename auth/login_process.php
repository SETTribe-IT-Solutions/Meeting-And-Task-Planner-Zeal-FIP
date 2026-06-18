<?php

session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die('<h3 style="color: red;">Database connection not available.</h3>' . 
        '<p>Error: ' . mysqli_connect_error() . '</p>' .
        '<p>Check config/db.php settings.</p>');
}

// Debug: Check if form data is received
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('<h3 style="color: red;">Invalid request method. Expected POST.</h3>');
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    die('<h3 style="color: red;">Email and password are required.</h3>' .
        '<p>Email: ' . htmlspecialchars($email) . '</p>' .
        '<p>Password: ' . (empty($password) ? 'EMPTY' : 'PROVIDED') . '</p>');
}

echo '<h3>Attempting login for: ' . htmlspecialchars($email) . '</h3>';

$query = 'SELECT id, name, email, password, role FROM `users` WHERE email = ? LIMIT 1';
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die('<h3 style="color: red;">Database query preparation failed</h3>' .
        '<p>Error: ' . mysqli_error($conn) . '</p>');
}

mysqli_stmt_bind_param($stmt, 's', $email);
if (!mysqli_stmt_execute($stmt)) {
    die('<h3 style="color: red;">Database query execution failed</h3>' .
        '<p>Error: ' . mysqli_stmt_error($stmt) . '</p>');
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    die('<h3 style="color: red;">Database result retrieval failed</h3>' .
        '<p>Error: ' . mysqli_error($conn) . '</p>');
}

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    $storedPassword = $user['password'];
    
    echo '<p>User found: ' . htmlspecialchars($user['name']) . '</p>';
    echo '<p>Stored password length: ' . strlen($storedPassword) . '</p>';
    echo '<p>Input password length: ' . strlen($password) . '</p>';
    
    // Plain text password comparison
    if ($password === $storedPassword) {
        echo '<p style="color: green;">✓ Password match!</p>';
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        echo '<p>Redirecting to dashboard...</p>';
        
        if ($user['role'] == 'organizer') {
            header('Location: ../dashboards/organizer.php');
            exit;
        } elseif ($user['role'] == 'collector') {
            header('Location: ../dashboards/collector.php');
            exit;
        } else {
            header('Location: ../dashboards/employee.php');
            exit;
        }
    } else {
        echo '<h3 style="color: red;">✗ Invalid Password</h3>';
        echo '<p>Stored: <code>' . htmlspecialchars($storedPassword) . '</code></p>';
        echo '<p>Input: <code>' . htmlspecialchars($password) . '</code></p>';
    }
} else {
    echo '<h3 style="color: red;">✗ User Not Found</h3>';
    echo '<p>Email searched: <code>' . htmlspecialchars($email) . '</code></p>';
    echo '<p>Users in database: ' . mysqli_num_rows($result) . '</p>';
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
