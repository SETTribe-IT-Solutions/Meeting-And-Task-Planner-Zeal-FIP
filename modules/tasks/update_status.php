<?php
// modules/tasks/update_status.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role'])) {
    header("Location: ../users/login.php");
    exit();
}

require_once '../../config/db.php';

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId > 0) {
    try {
        // Fetch current status
        $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $taskId]);
        $task = $stmt->fetch();

        if ($task) {
            // Toggle status logic
            $newStatus = ($task['status'] === 'Completed') ? 'Pending' : 'Completed';
            
            $updateStmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
            $updateStmt->execute(['status' => $newStatus, 'id' => $taskId]);
        }
    } catch (PDOException $e) {
        error_log("Status update error: " . $e->getMessage());
    }
}

// Redirect back to main dashboard
header("Location: ../../index.php");
exit();
?>