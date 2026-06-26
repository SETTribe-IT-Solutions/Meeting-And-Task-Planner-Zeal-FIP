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

// CSRF verification — same pattern used throughout the application
$submitted_token = trim($_POST['csrf_token'] ?? '');
if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    redirect('../modules/tasks/index.php');
}

if (empty($_POST['task_id']) || !isset($_POST['status'])) {
    $_SESSION['error'] = 'Invalid request parameters.';
    redirect('../modules/tasks/index.php');
}

$taskId = (int) $_POST['task_id'];

// Whitelist validation — reject any value not in the allowed set
$ALLOWED_STATUSES = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
$status = trim($_POST['status']);
if (!in_array($status, $ALLOWED_STATUSES, true)) {
    $_SESSION['error'] = 'Invalid status value.';
    redirect('../modules/tasks/index.php');
}

try {
    $conn = getDBConnection();
    $role   = $_SESSION['role'];
    $userId = (int) $_SESSION['user_id'];

    // Authorization: Organizer and Collector may update any task.
    // All other roles (Employee) may only update tasks assigned to them.
    if ($role !== 'Collector' && $role !== 'Organizer') {
        $authorized = false;

        // Check primary assigned_to column
        $authStmt = $conn->prepare("SELECT assigned_to FROM tasks WHERE id = ? LIMIT 1");
        $authStmt->bind_param("i", $taskId);
        $authStmt->execute();
        $taskRow = $authStmt->get_result()->fetch_assoc();

        if ($taskRow && (int) $taskRow['assigned_to'] === $userId) {
            $authorized = true;
        }

        // Also check task_assignments (multi-assignee) table if it exists
        if (!$authorized) {
            $tableExists = $conn->query("SHOW TABLES LIKE 'task_assignments'")->num_rows > 0;
            if ($tableExists) {
                $taStmt = $conn->prepare(
                    "SELECT 1 FROM task_assignments WHERE task_id = ? AND user_id = ? LIMIT 1"
                );
                $taStmt->bind_param("ii", $taskId, $userId);
                $taStmt->execute();
                if ($taStmt->get_result()->num_rows > 0) {
                    $authorized = true;
                }
            }
        }

        if (!$authorized) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to update this task.';
            redirect('../modules/tasks/index.php');
        }
    }

    // Perform the update
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
