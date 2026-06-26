<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$dept = '';
if (isset($_GET['department'])) {
    $dept = trim($_GET['department']);
} elseif (isset($_POST['department'])) {
    $dept = trim($_POST['department']);
}

if ($dept === '') {
    echo json_encode([]);
    exit();
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT id, name, email FROM users WHERE department = ? AND isDeleted = "No" ORDER BY name');
    $stmt->bind_param('s', $dept);
    $stmt->execute();
    $res = $stmt->get_result();
    $users = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode($users);
    exit();
} catch (Exception $e) {
    echo json_encode([]);
    exit();
}
