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
    $_SESSION['error'] = 'You are not authorized to edit tasks.';
    redirect('../modules/tasks/index.php');
}

$submitted_token = trim($_POST['csrf_token'] ?? '');
if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    $_SESSION['error'] = 'Invalid security token.';
    redirect('../modules/tasks/index.php');
}

$taskId   = (int)($_POST['task_id'] ?? 0);
$title    = trim($_POST['title'] ?? '');
$dueDate  = trim($_POST['due_date'] ?? '');
$priority = trim($_POST['priority'] ?? '');
$notes    = trim($_POST['notes'] ?? '');

if ($taskId <= 0 || $title === '' || $dueDate === '' || $priority === '') {
    $_SESSION['error'] = 'Please fill in all required fields.';
    redirect('../modules/tasks/edit.php?id=' . $taskId);
}

$dueDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
if (!$dueDateObj) {
    $_SESSION['error'] = 'Invalid due date.';
    redirect('../modules/tasks/edit.php?id=' . $taskId);
}

$assignedIds = array_map('intval', (array)($_POST['assigned_to'] ?? []));
$assignedIds = array_filter($assignedIds, fn($id) => $id > 0);
if (empty($assignedIds)) {
    $_SESSION['error'] = 'Please assign at least one employee.';
    redirect('../modules/tasks/edit.php?id=' . $taskId);
}
$primaryAssigned = $assignedIds[0];

try {
    $conn = getDBConnection();

    $stmt = $conn->prepare(
        "UPDATE tasks SET title = ?, assigned_to = ?, due_date = ?, priority = ?, notes = ? WHERE id = ?"
    );
    $stmt->bind_param('sisssi', $title, $primaryAssigned, $dueDate, $priority, $notes, $taskId);
    $stmt->execute();

    // Refresh task_assignments
    $delStmt = $conn->prepare("DELETE FROM task_assignments WHERE task_id = ?");
    $delStmt->bind_param('i', $taskId);
    $delStmt->execute();

    $insStmt = $conn->prepare("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)");
    foreach ($assignedIds as $uid) {
        $insStmt->bind_param('ii', $taskId, $uid);
        $insStmt->execute();
    }

    $_SESSION['success'] = 'Task updated successfully.';
    redirect('../modules/tasks/index.php');
} catch (Exception $e) {
    error_log('Task edit failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to update task right now.';
    redirect('../modules/tasks/edit.php?id=' . $taskId);
}
