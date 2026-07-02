<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../modules/tasks/index.php');
}

if (!isLoggedIn()) {
    redirect('../modules/users/login.php');
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'Organizer') {
    $_SESSION['error'] = 'You are not authorized to delete tasks.';
    redirect('../modules/tasks/index.php');
}

$submitted_token = trim($_POST['csrf_token'] ?? '');
if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    $_SESSION['error'] = 'Invalid security token.';
    redirect('../modules/tasks/index.php');
}

$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId <= 0) {
    $_SESSION['error'] = 'Invalid task.';
    redirect('../modules/tasks/index.php');
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param('i', $taskId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = 'Task deleted successfully.';
    } else {
        $_SESSION['error'] = 'Task not found.';
    }
} catch (Exception $e) {
    error_log('Task delete failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to delete task right now.';
}

redirect('../modules/tasks/index.php');
