<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/modules/meetings/index.php');
    exit();
}

if (($_SESSION['role'] ?? '') !== 'Organizer') {
    $_SESSION['error'] = 'Only organisers can mark meetings as completed.';
    header('Location: ' . APP_URL . '/modules/meetings/index.php');
    exit();
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', trim($_POST['csrf_token'] ?? ''))) {
    $_SESSION['error'] = 'Security token mismatch. Please refresh and try again.';
    header('Location: ' . APP_URL . '/modules/meetings/index.php');
    exit();
}

$meetingId = (int)($_POST['meeting_id'] ?? 0);
if ($meetingId <= 0) {
    $_SESSION['error'] = 'Invalid meeting.';
    header('Location: ' . APP_URL . '/modules/meetings/index.php');
    exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE meetings SET status = 'Completed' WHERE id = ? AND status = 'Scheduled'");
$stmt->bind_param('i', $meetingId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['success'] = 'Meeting marked as Completed. You can now assign tasks.';
} else {
    $_SESSION['error'] = 'This meeting could not be marked as completed — it may already be completed or cancelled.';
}

$redirect = trim($_POST['redirect'] ?? '');
if ($redirect && preg_match('#^\.\./modules/[a-zA-Z0-9_/.\-?=&]+$#', $redirect)) {
    header('Location: ' . $redirect);
} else {
    header('Location: ' . APP_URL . '/modules/meetings/view.php?id=' . $meetingId);
}
exit();
