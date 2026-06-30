<?php
// modules/meetings/download_attachment.php — Secure meeting attachment download.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';

$conn      = getDBConnection();
$meetingId = (int)($_GET['meeting_id'] ?? 0);

if ($meetingId <= 0) {
    $_SESSION['error'] = 'Invalid attachment request.';
    header('Location: index.php');
    exit();
}

// Fetch attachment record
$stmt = $conn->prepare(
    "SELECT ma.stored_name, ma.original_name, ma.mime_type
     FROM meeting_attachments ma
     WHERE ma.meeting_id = ?
     ORDER BY ma.uploaded_at DESC LIMIT 1"
);
$stmt->bind_param('i', $meetingId);
$stmt->execute();
$attachment = $stmt->get_result()->fetch_assoc();

if (!$attachment) {
    $_SESSION['error'] = 'Attachment not found.';
    header('Location: view.php?id=' . $meetingId);
    exit();
}

// Access check: Employees may only download attachments for meetings they attend
$role    = $_SESSION['role'];
$userId  = (int)$_SESSION['user_id'];
if ($role === 'Employee') {
    $chk = $conn->prepare(
        "SELECT 1 FROM attendance WHERE meeting_id = ? AND user_id = ? LIMIT 1"
    );
    $chk->bind_param('ii', $meetingId, $userId);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        $_SESSION['error'] = 'You do not have access to this attachment.';
        header('Location: index.php');
        exit();
    }
}

// Sanitize stored_name to prevent path traversal
$storedName = basename($attachment['stored_name']);
$filePath   = dirname(__DIR__, 2) . '/uploads/meetings/' . $storedName;

if (!is_file($filePath)) {
    $_SESSION['error'] = 'Attachment file not found on server.';
    header('Location: view.php?id=' . $meetingId);
    exit();
}

// Serve file
$mimeType    = $attachment['mime_type'];
$originalName = $attachment['original_name'];

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
readfile($filePath);
exit();
