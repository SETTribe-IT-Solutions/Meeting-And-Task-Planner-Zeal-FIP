<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/tasks/create.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in first.';
    header('Location: ../modules/users/login.php');
    exit();
}

$requiredFields = ['meeting_id', 'title', 'assigned_to', 'due_date', 'priority'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = 'Please fill in all task details.';
        header('Location: ../modules/tasks/create.php');
        exit();
    }
}

$meetingId = (int) $_POST['meeting_id'];
$title = trim($_POST['title']);
$assignedTo = (int) $_POST['assigned_to'];
$dueDate = trim($_POST['due_date']);
$priority = trim($_POST['priority']);
$notes = trim($_POST['notes'] ?? '');

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        "INSERT INTO tasks (meeting_id, title, assigned_to, due_date, priority, notes) VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("isisss", $meetingId, $title, $assignedTo, $dueDate, $priority, $notes);
    $stmt->execute();

    $_SESSION['success'] = 'Task created successfully.';
    header('Location: ../index.php?status=success');
    exit();
} catch (Exception $e) {
    error_log('Task creation failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to create task right now.';
    header('Location: ../modules/tasks/create.php');
    exit();
}
