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
    $_SESSION['error'] = 'Only organisers can cancel meetings.';
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

$cancelReason = trim($_POST['cancel_reason'] ?? '');

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE meetings SET status = 'Cancelled', cancel_reason = ? WHERE id = ? AND status = 'Scheduled'");
$stmt->bind_param('si', $cancelReason, $meetingId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['success'] = 'Meeting has been cancelled successfully.';
} else {
    $_SESSION['error'] = 'This meeting could not be cancelled — it may already be completed or cancelled.';
}

header('Location: ' . APP_URL . '/modules/meetings/index.php');
exit();
