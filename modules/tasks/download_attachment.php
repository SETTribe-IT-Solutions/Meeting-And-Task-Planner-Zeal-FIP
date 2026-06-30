<?php
// modules/tasks/download_attachment.php — Secure task attachment download.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';

$conn   = getDBConnection();
$taskId = (int)($_GET['task_id'] ?? 0);

if ($taskId <= 0) {
    $_SESSION['error'] = 'Invalid attachment request.';
    header('Location: index.php');
    exit();
}

// Fetch attachment record
$stmt = $conn->prepare(
    "SELECT ta.stored_name, ta.original_name, ta.mime_type
     FROM task_attachments ta
     WHERE ta.task_id = ?
     ORDER BY ta.uploaded_at DESC LIMIT 1"
);
$stmt->bind_param('i', $taskId);
$stmt->execute();
$attachment = $stmt->get_result()->fetch_assoc();

if (!$attachment) {
    $_SESSION['error'] = 'Attachment not found.';
    header('Location: index.php');
    exit();
}

// Access check: Employees may only download attachments for tasks assigned to them
$role   = $_SESSION['role'];
$userId = (int)$_SESSION['user_id'];
if ($role === 'Employee') {
    $chk = $conn->prepare(
        "SELECT 1 FROM task_assignments WHERE task_id = ? AND user_id = ? LIMIT 1"
    );
    $chk->bind_param('ii', $taskId, $userId);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        // Also check tasks.assigned_to (legacy single-assignee)
        $chk2 = $conn->prepare("SELECT 1 FROM tasks WHERE id = ? AND assigned_to = ? LIMIT 1");
        $chk2->bind_param('ii', $taskId, $userId);
        $chk2->execute();
        if ($chk2->get_result()->num_rows === 0) {
            $_SESSION['error'] = 'You do not have access to this attachment.';
            header('Location: index.php');
            exit();
        }
    }
}

// Sanitize stored_name to prevent path traversal
$storedName = basename($attachment['stored_name']);
$filePath   = dirname(__DIR__, 2) . '/uploads/tasks/' . $storedName;

if (!is_file($filePath)) {
    $_SESSION['error'] = 'Attachment file not found on server.';
    header('Location: index.php');
    exit();
}

// Serve file
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . addslashes($attachment['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
readfile($filePath);
exit();
