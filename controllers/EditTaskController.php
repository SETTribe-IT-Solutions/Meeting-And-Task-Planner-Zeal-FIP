<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';

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

    // Handle optional task attachment (replace if new file uploaded)
    $uploadDir    = dirname(__DIR__) . '/uploads/tasks';
    $uploadResult = validateAndStoreUpload('task_attachment', $uploadDir);
    if ($uploadResult['success']) {
        $oldStmt = $conn->prepare("SELECT stored_name FROM task_attachments WHERE task_id = ?");
        $oldStmt->bind_param('i', $taskId);
        $oldStmt->execute();
        $old = $oldStmt->get_result()->fetch_assoc();
        if ($old) {
            deleteUploadFile($uploadDir, $old['stored_name']);
            $delA = $conn->prepare("DELETE FROM task_attachments WHERE task_id = ?");
            $delA->bind_param('i', $taskId);
            $delA->execute();
        }
        $uploaderId = (int)$_SESSION['user_id'];
        $insA = $conn->prepare(
            "INSERT INTO task_attachments (task_id, uploaded_by, original_name, stored_name, file_size, mime_type)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insA->bind_param("iissss",
            $taskId, $uploaderId,
            $uploadResult['original_name'], $uploadResult['stored_name'],
            $uploadResult['file_size'], $uploadResult['mime_type']
        );
        $insA->execute();
    }

    $_SESSION['success'] = 'Task updated successfully.';
    redirect('../modules/tasks/index.php');
} catch (Exception $e) {
    error_log('Task edit failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to update task right now.';
    redirect('../modules/tasks/edit.php?id=' . $taskId);
}
