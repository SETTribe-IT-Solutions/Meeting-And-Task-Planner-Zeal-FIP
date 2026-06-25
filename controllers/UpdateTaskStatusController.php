<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../modules/tasks/index.php');
}

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please log in first.';
    redirect('../modules/users/login.php');
}

if (($_SESSION['role'] ?? '') !== 'Organizer') {
    $_SESSION['error'] = 'You have view-only access to tasks.';
    redirect('../modules/tasks/index.php');
}

if (empty($_POST['task_id']) || empty($_POST['status'])) {
    $_SESSION['error'] = 'Invalid request parameters.';
    redirect('../modules/tasks/index.php');
}

$taskId = (int) $_POST['task_id'];
$status = sanitizeInput($_POST['status']);

try {
    $conn = getDBConnection();
    
    // Prepare and execute the update
    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $taskId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Task marked as $status.";
    } else {
        $_SESSION['error'] = "Task not found or status already set to $status.";
    }
    
    redirect('../modules/tasks/index.php');
} catch (Exception $e) {
    error_log('Task status update failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to update task status right now.';
    redirect('../modules/tasks/index.php');
}
