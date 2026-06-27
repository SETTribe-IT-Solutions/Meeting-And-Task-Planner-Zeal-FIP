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
    $_SESSION['error'] = 'Only organisers can edit meetings.';
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

// Verify the meeting exists and is still Scheduled
$conn = getDBConnection();
$chk  = $conn->prepare("SELECT id, department FROM meetings WHERE id = ? AND status = 'Scheduled'");
$chk->bind_param('i', $meetingId);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();

if (!$existing) {
    $_SESSION['error'] = 'Meeting not found or cannot be edited.';
    header('Location: ' . APP_URL . '/modules/meetings/index.php');
    exit();
}

// Required fields
$requiredFields = ['title', 'meeting_date', 'meeting_time', 'mode', 'agenda', 'department'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = 'Please fill in all required meeting details.';
        header('Location: ' . APP_URL . '/modules/meetings/edit.php?id=' . $meetingId);
        exit();
    }
}

$title       = trim($_POST['title']);
$meetingDate = trim($_POST['meeting_date']);
$meetingTime = trim($_POST['meeting_time']);
$mode        = trim($_POST['mode']);
$agenda      = trim($_POST['agenda']);
$department  = trim($_POST['department']);
$location    = trim($_POST['location']    ?? '');
$meetingUrl  = trim($_POST['meeting_url'] ?? '');
$duration    = !empty($_POST['duration']) ? (int)$_POST['duration'] : null;

// Mode-specific validation
if ($mode === 'Offline' && empty($location)) {
    $_SESSION['error'] = 'Location is required for Offline meetings.';
    header('Location: ' . APP_URL . '/modules/meetings/edit.php?id=' . $meetingId);
    exit();
}
if ($mode === 'Online' && empty($meetingUrl)) {
    $_SESSION['error'] = 'Meeting URL is required for Online meetings.';
    header('Location: ' . APP_URL . '/modules/meetings/edit.php?id=' . $meetingId);
    exit();
}
if (!in_array($mode, ['Offline', 'Online', 'Hybrid'], true)) {
    $_SESSION['error'] = 'Please select a valid meeting mode.';
    header('Location: ' . APP_URL . '/modules/meetings/edit.php?id=' . $meetingId);
    exit();
}
if (!in_array($department, getDepartments(), true)) {
    $_SESSION['error'] = 'Please select a valid department.';
    header('Location: ' . APP_URL . '/modules/meetings/edit.php?id=' . $meetingId);
    exit();
}

try {
    // Update meeting record
    $upd = $conn->prepare(
        "UPDATE meetings
         SET title = ?, meeting_date = ?, meeting_time = ?, location = ?,
             meeting_url = ?, mode = ?, duration = ?, agenda = ?, department = ?
         WHERE id = ? AND status = 'Scheduled'"
    );
    $upd->bind_param('ssssssissi',
        $title, $meetingDate, $meetingTime, $location,
        $meetingUrl, $mode, $duration, $agenda, $department, $meetingId
    );
    $upd->execute();

    // Sync attendees
    $currStmt = $conn->prepare("SELECT user_id FROM attendance WHERE meeting_id = ?");
    $currStmt->bind_param('i', $meetingId);
    $currStmt->execute();
    $currentAttendees = array_column($currStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');

    $newAttendees = array_unique(array_map('intval', $_POST['attendees'] ?? []));

    // Remove those no longer in the list
    $toRemove = array_diff($currentAttendees, $newAttendees);
    if (!empty($toRemove)) {
        $ph     = implode(',', array_fill(0, count($toRemove), '?'));
        $delStmt = $conn->prepare("DELETE FROM attendance WHERE meeting_id = ? AND user_id IN ($ph)");
        $types  = 'i' . str_repeat('i', count($toRemove));
        $params = array_merge([$meetingId], $toRemove);
        $delStmt->bind_param($types, ...$params);
        $delStmt->execute();
    }

    // Add newly invited attendees
    $toAdd = array_diff($newAttendees, $currentAttendees);
    if (!empty($toAdd)) {
        $validCheck = $conn->prepare(
            "SELECT id FROM users WHERE id = ? AND role = 'Employee' AND department = ? AND isDeleted = 'No' LIMIT 1"
        );
        $addStmt = $conn->prepare(
            "INSERT IGNORE INTO attendance (meeting_id, user_id, status) VALUES (?, ?, 'Pending')"
        );
        foreach ($toAdd as $uid) {
            $validCheck->bind_param('is', $uid, $department);
            $validCheck->execute();
            if ($validCheck->get_result()->num_rows === 0) continue;
            $addStmt->bind_param('ii', $meetingId, $uid);
            $addStmt->execute();
        }
    }

    $_SESSION['success'] = 'Meeting updated successfully.';
    header('Location: ' . APP_URL . '/modules/meetings/view.php?id=' . $meetingId);
    exit();

} catch (Exception $e) {
    error_log('Meeting update failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to update meeting. Please try again.';
    header('Location: ' . APP_URL . '/modules/meetings/edit.php?id=' . $meetingId);
    exit();
}
