<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function failTaskCreation(string $message, bool $isAjax): void
{
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['error'] = $message;
        header('Location: ../modules/tasks/create.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/tasks/create.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in first.';
    header('Location: ../modules/users/login.php');
    exit();
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');

// Handle Delete
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $taskId = (int)($_POST['task_id'] ?? 0);
    if ($taskId > 0) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->bind_param("i", $taskId);
            $stmt->execute();
            $_SESSION['success'] = 'Task deleted successfully.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete task.';
        }
    }
    header('Location: ../modules/tasks/index.php');
    exit();
}

$requiredFields = ['meeting_id', 'title', 'assigned_to', 'due_date', 'priority'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        failTaskCreation('Please fill in all task details.', $isAjax);
    }
}

$taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$meetingId = (int) $_POST['meeting_id'];
$title = trim($_POST['title']);
$dueDate = trim($_POST['due_date']);
$priority = trim($_POST['priority']);
$notes = trim($_POST['notes'] ?? '');

$dueDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
$dateErrors = DateTimeImmutable::getLastErrors();
$hasDateErrors = $dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);
if (!$dueDateObj || $hasDateErrors) {
    failTaskCreation('Please select a valid due date.', $isAjax);
}

if ($dueDateObj < new DateTimeImmutable('today') && $taskId === 0) {
    failTaskCreation('Past dates are not allowed. Please select today or a future date.', $isAjax);
}

// assigned_to can be array (multiple) or single
$assignedToInput = $_POST['assigned_to'] ?? [];
if (is_array($assignedToInput)) {
    $assignedIds = array_map('intval', $assignedToInput);
} else {
    $assignedIds = [(int)$assignedToInput];
}
// Primary assigned_to stored on tasks table (first selected)
$assignedTo = isset($assignedIds[0]) ? (int)$assignedIds[0] : 0;

try {
    $conn = getDBConnection();

    // Ensure task_assignments table exists
    $conn->query("CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($taskId > 0) {
        $stmt = $conn->prepare("UPDATE tasks SET meeting_id = ?, title = ?, assigned_to = ?, due_date = ?, priority = ?, notes = ? WHERE id = ?");
        $stmt->bind_param("isisssi", $meetingId, $title, $assignedTo, $dueDate, $priority, $notes, $taskId);
        $stmt->execute();
        $insertId = $taskId;
        
        $delStmt = $conn->prepare("DELETE FROM task_assignments WHERE task_id = ?");
        $delStmt->bind_param('i', $taskId);
        $delStmt->execute();
        
        $msg = 'Task updated successfully.';
    } else {
        $stmt = $conn->prepare("INSERT INTO tasks (meeting_id, title, assigned_to, due_date, priority, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisss", $meetingId, $title, $assignedTo, $dueDate, $priority, $notes);
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $msg = 'Task created successfully.';
    }

    // Insert assignments
    if (!empty($assignedIds)) {
        $insStmt = $conn->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
        foreach ($assignedIds as $uid) {
            $uid = (int)$uid;
            $insStmt->bind_param('ii', $insertId, $uid);
            $insStmt->execute();
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg, 'task_id' => $insertId]);
        exit();
    }

    $_SESSION['success'] = $msg;
    header('Location: ../modules/tasks/index.php');
    exit();
} catch (Exception $e) {
    error_log('Task action failed: ' . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unable to process task right now.'] );
        exit();
    }

    $_SESSION['error'] = 'Unable to process task right now.';
    header('Location: ../modules/tasks/create.php' . ($taskId > 0 ? '?id='.$taskId : ''));
    exit();
}
